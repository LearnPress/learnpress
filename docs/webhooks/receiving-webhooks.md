# Receiving LearnPress Webhooks

## Overview

LearnPress sends webhook events as signed HTTP `POST` requests to a configured callback URL. A receiver should:

1. Read the raw request body.
2. Verify the HMAC signature before parsing or processing the payload.
3. Reject invalid requests.
4. Deduplicate valid deliveries.
5. Return a `2xx` response quickly and process slow work asynchronously.

Configure webhooks in **LearnPress > Settings > Advanced > Webhook**:

1. Enable **Webhook Integration**.
2. Add an active webhook with an HTTPS callback URL.
3. Select the events to receive.
4. Store the generated secret securely on the receiving server.

The old secret stops working immediately after regenerating a webhook secret.

## HTTP Request Contract

LearnPress sends a blocking HTTP request with:

- Method: `POST`
- Content type: `application/json`
- Timeout: 10 seconds
- Redirects followed: none
- Automatic retries: none

The default JSON envelope is:

```json
{
  "id": "lpwh_4d874cee-f7ce-49db-8c72-e042bdfa5a2e",
  "event": "order.completed",
  "api_version": "v1",
  "created_at": "2026-06-04T09:30:00+00:00",
  "site_url": "https://learnpress.example/",
  "data": {
    "order_id": 123,
    "order_number": "123",
    "order_title": "Order on June 4, 2026",
    "status": "completed",
    "user_id": 7,
    "user_name": "Jane Doe"
  }
}
```

The `data` object depends on the event. Version `v1` keeps existing scalar ID fields and adds
human-readable aliases and compact resource snapshots when LearnPress can resolve them. Receivers
should ignore unknown fields so LearnPress can add fields without breaking the integration.

### Request Headers

| Header | Description |
|---|---|
| `X-LP-Webhook-Source` | LearnPress site URL. |
| `X-LP-Webhook-Event` | Event key, such as `order.completed`. |
| `X-LP-Webhook-ID` | ID of the configured webhook in LearnPress. |
| `X-LP-Webhook-Delivery-ID` | Unique ID for this delivery attempt. |
| `X-LP-Webhook-Signature` | Base64-encoded HMAC-SHA256 signature of the raw JSON body. |

LearnPress also sends `X-LearnPress-Webhook-Event`, `X-LearnPress-Webhook-ID`, and
`X-LearnPress-Webhook-Signature` as compatibility aliases. New receivers should use the
`X-LP-*` headers.

HTTP header names are case-insensitive.

## Verify the Signature

The signature is calculated from the exact raw request body:

```text
signature = BASE64(HMAC_SHA256(raw_request_body, webhook_secret))
```

Verification requirements:

- Read the raw body before a JSON parser changes it.
- Do not parse and re-encode JSON before calculating the signature.
- Use a constant-time comparison.
- Keep the secret on the server. Never expose it to frontend code.
- Verify that the signed payload `event` and `site_url` match the expected integration.
- Treat headers as unsigned metadata. Compare the delivery and event headers with the signed
  payload before using them.
- Optionally reject deliveries with an unexpectedly old signed `created_at` value.

If one endpoint receives multiple LearnPress webhook configurations, use `X-LP-Webhook-ID`
only to select a candidate secret. Trust the webhook ID only after that secret successfully
verifies the request body.

## Node.js Receiver Example

This Express example uses `express.raw()` on the webhook route. Register this route before
any global `express.json()` middleware.

```js
import crypto from 'node:crypto';
import express from 'express';

const app = express();
const secret = process.env.LP_WEBHOOK_SECRET;

if (!secret) {
	throw new Error('LP_WEBHOOK_SECRET is required');
}

function signaturesMatch(rawBody, signature) {
	const expected = crypto
		.createHmac('sha256', secret)
		.update(rawBody)
		.digest();
	const received = Buffer.from(signature || '', 'base64');

	return (
		received.length === expected.length &&
		crypto.timingSafeEqual(received, expected)
	);
}

app.post(
	'/webhooks/learnpress',
	express.raw({ type: 'application/json', limit: '1mb' }),
	async (req, res) => {
		const rawBody = req.body;
		const signature = req.get('X-LP-Webhook-Signature');

		if (!Buffer.isBuffer(rawBody) || !signaturesMatch(rawBody, signature)) {
			return res.status(401).send('Invalid signature');
		}

		let payload;
		try {
			payload = JSON.parse(rawBody.toString('utf8'));
		} catch {
			return res.status(400).send('Invalid JSON');
		}

		const event = req.get('X-LP-Webhook-Event');
		const deliveryId = req.get('X-LP-Webhook-Delivery-ID');

		if (!deliveryId || deliveryId !== payload.id || event !== payload.event) {
			return res.status(400).send('Invalid webhook contract');
		}

		// Persist the verified deliveryId with a unique constraint before processing.
		// Enqueue slow work here, then acknowledge the delivery.
		console.log(event, deliveryId, payload.data);

		return res.status(204).end();
	}
);

app.use(express.json());
app.listen(3000);
```

## PHP Receiver Example

```php
<?php

$secret      = getenv( 'LP_WEBHOOK_SECRET' );
$raw_body    = file_get_contents( 'php://input' );
$raw_headers = function_exists( 'getallheaders' ) ? getallheaders() : array();
$headers     = array_change_key_case( is_array( $raw_headers ) ? $raw_headers : array(), CASE_LOWER );

if ( empty( $secret ) || ! is_string( $raw_body ) ) {
	http_response_code( 500 );
	exit( 'Webhook receiver is not configured' );
}

$signature = $headers['x-lp-webhook-signature'] ?? '';
$expected  = base64_encode( hash_hmac( 'sha256', $raw_body, $secret, true ) );

if ( ! hash_equals( $expected, $signature ) ) {
	http_response_code( 401 );
	exit( 'Invalid signature' );
}

try {
	$payload = json_decode( $raw_body, true, 512, JSON_THROW_ON_ERROR );
} catch ( JsonException $error ) {
	http_response_code( 400 );
	exit( 'Invalid JSON' );
}

$event       = $headers['x-lp-webhook-event'] ?? '';
$delivery_id = $headers['x-lp-webhook-delivery-id'] ?? '';

if (
	empty( $delivery_id ) ||
	$delivery_id !== ( $payload['id'] ?? '' ) ||
	$event !== ( $payload['event'] ?? '' )
) {
	http_response_code( 400 );
	exit( 'Invalid webhook contract' );
}

// Persist the verified $delivery_id with a unique constraint before processing.
// Enqueue slow work here, then acknowledge the delivery.

http_response_code( 204 );
```

## Idempotency

After verifying the signature and confirming that `X-LP-Webhook-Delivery-ID` equals the signed
payload `id`, store the delivery ID with a unique database constraint before processing. If
the same delivery ID is received again, return `2xx` without repeating its side effects.

A business event can happen more than once with different delivery IDs. For operations such
as granting access or creating external records, also use a business idempotency key, for
example:

- `order.completed:{order_id}`
- `lesson.completed:{user_item.user_item_id}`
- `assignment.submitted:{user_id}:{assignment_id}`

## Common Event Data

| Event group | Common `data` fields |
|---|---|
| Enrollment | `order_id`, `order_number`, `course_id`, `course_name`, `user_id`, `user_name` |
| Orders | `order_id`, `order_number`, `order_title`, `status`, `user_id`, `user_name`, `total`, `subtotal`, `currency`, `created_via`, `checkout_email`, `order_item_names`, `items`, `order` |
| Order transitions | Order fields plus `old_status`, `new_status` |
| Lessons, quizzes, courses, user items | `user_id`, `user_name`, `item_id`, `item_name`, `item_type`, `course_id`, `course_name`, `ref_id`, `ref_name`, `ref_type`, `user_item` |
| Password reset requested | `user_id`, `user_name`, `user_login`, `user_email`; reset keys and reset links are not included |
| Instructor requests | `user_id`, `user_name`, `name`, `email`, `phone`, `message` |
| Assignments | `user_id`, `user_name`, `assignment_id`, `assignment_name`, `course_id`, `course_name`, `user_item_id` when available |
| Announcements | `announcement_id`, `announcement_name`, `course_ids`, `course_names`, `send_mail`, `title`, `author_id`, `author_name` |
| Membership | `order_id`, `order_number`, `user_id`, `user_name`, `plan_id`, `plan_name`, `member_id`, `renew_order_id`, `renew_order_number`, `member_status`, `days_left`, `grace_days`, `gateway_id`, `trigger`, `webhook_data` |

An order item contains:

```json
{
  "order_item_id": 456,
  "order_item_name": "Example Course",
  "item_id": 99,
  "item_name": "Example Course",
  "item_type": "lp_course",
  "quantity": 1,
  "subtotal": "99.00",
  "total": "99.00",
  "item": {
    "id": 99,
    "type": "lp_course",
    "name": "Example Course",
    "title": "Example Course",
    "slug": "example-course",
    "permalink": "https://learnpress.example/courses/example-course/"
  }
}
```

A user item contains:

```json
{
  "user_item_id": 789,
  "user_id": 7,
  "user_name": "Jane Doe",
  "item_id": 99,
  "item_name": "Example Lesson",
  "item_type": "lp_lesson",
  "course_id": 55,
  "course_name": "Example Course",
  "status": "completed",
  "learning_status": "completed",
  "graduation": "passed",
  "ref_id": 55,
  "ref_name": "Example Course",
  "ref_type": "lp_course",
  "parent_id": 0,
  "start_time": "2026-06-04 09:00:00",
  "end_time": "2026-06-04 09:20:00",
  "started_at": "2026-06-04 09:00:00",
  "ended_at": "2026-06-04 09:20:00",
  "duration_seconds": 1200,
  "user": {},
  "item": {},
  "course": {},
  "ref": {},
  "result": {}
}
```

When the user item is a course (`item_type: "lp_course"`), `user_item.result` includes the
course evaluation summary and `user_item.progress.sections` includes curriculum sections and
items with item names, learning status, user-item data, and safe score summaries. Quiz and
assignment result snapshots do not include answers, notes, submissions, or files.

## Available Events

### LearnPress Core

- `user.course_enrolled`
- `course.enrolled`
- `order.created`
- `order.status_changed`
- `order.pending_to_processing`
- `order.pending_to_completed`
- `order.processing`
- `order.completed`
- `order.cancelled`
- `order.failed`
- `order.refunded`
- `checkout.order_processed`
- `course.submit_rejected`
- `course.submit_approved`
- `course.submit_for_review`
- `lesson.completed`
- `quiz.started`
- `quiz.finished`
- `quiz.retried`
- `course.finished`
- `user_item.created`
- `user.password_reset_requested`
- `instructor.requested`
- `instructor.accepted`
- `instructor.denied`

### LearnPress Membership

These events are available only while the LearnPress Membership add-on is active:

- `membership.order_completed`
- `membership.subscription_renewed`
- `membership.subscription_cancelled`
- `membership.subscription_suspended`
- `membership.subscription_expired`
- `membership.payment_failed`
- `membership.reminder_expiring`
- `membership.course_resumed`

### LearnPress Assignments

These events are available only while the LearnPress Assignments add-on is active:

- `assignment.started`
- `assignment.submitted`
- `assignment.evaluated`
- `assignment.re_evaluated`
- `assignment.retried`

### LearnPress Announcements

These events are available only while the LearnPress Announcements add-on is active:

- `announcement.created`
- `announcement.email_queued`

## Current Delivery Behavior

- LearnPress sends webhooks synchronously during the triggering request.
- LearnPress waits up to 10 seconds for the receiver.
- Redirect responses are not followed.
- There is no queue or automatic retry mechanism.
- The receiver response body is only logged for non-2xx responses, with a short truncated preview.
- The current dispatcher treats `WP_Error`, thrown errors, and non-2xx HTTP responses as failed deliveries.
- Duplicate events are suppressed only within the same LearnPress request.

Because delivery happens synchronously, acknowledge valid webhooks quickly and move slow work
to a queue on the receiving server.

## LearnPress Extension Hooks

LearnPress developers can customize outbound webhooks with:

| Hook | Purpose |
|---|---|
| `learn-press/webhook/events` | Register or filter selectable event keys. |
| `learn-press/webhook/payload` | Modify the payload before it is encoded and signed. |
| `learn-press/webhook/http-args` | Modify the outbound HTTP request arguments. |
| `learn-press/webhook/delivered` | Inspect the response after a delivery attempt. |
| `learn-press/webhook/log-successful-delivery` | Return `true` to write successful delivery summaries to `error_log` while debugging. |

When `learn-press/webhook/payload` changes the payload, the signature is calculated from the
modified raw JSON body.

## Troubleshooting

### Signature verification fails

- Confirm that the receiver uses the current webhook secret.
- Verify the signature against the raw body, not parsed JSON.
- Ensure middleware, proxies, or frameworks do not modify the body before verification.
- Use the Base64 binary HMAC digest, not a hexadecimal digest.

### LearnPress does not send the webhook

- Enable **Webhook Integration**.
- Ensure the webhook status is active.
- Ensure the event is selected.
- Confirm that the related add-on is active for add-on events.

### The callback receives no request

- Use a publicly reachable HTTPS endpoint.
- Avoid callback URLs that redirect, including HTTP-to-HTTPS and trailing-slash redirects.
- Ensure the endpoint responds within 10 seconds.
