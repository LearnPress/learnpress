# PayPal Subscription Webhook Specification

> **Document Version:** 1.0
> **Date:** 2026-04-07
> **Reference:** https://developer.paypal.com/docs/subscriptions/reference/webhooks/

---

## Overview

This document defines the complete logic for handling PayPal subscription webhook callbacks in LearnPress. All events follow PayPal's official documentation and LearnPress subscription lifecycle rules.

---

## 1. Webhook Endpoint

| Item | Value |
|---|---|
| **Endpoint URL** | `/wp-json/lp/v1/gateways/paypal/subscription-webhook` |
| **Method** | `POST` |
| **Authentication** | PayPal webhook signature verification |
| **Response Status** | Always return `200 OK` for valid requests |

✅ **Important:** PayPal will retry webhook delivery **exponentially** for up to 3 days for any non-2xx response. Always return 200 even for unhandled or duplicate events.

---

## 2. Processing Pipeline

All webhook events go through this exact order:

```
1. 🔐 Signature Verification
    ├─ Extract all PayPal headers
    ├─ Call PayPal verify API endpoint
    └─ FAIL → Return 403 (do not process)

2. 🧾 Payload Validation
    ├─ Parse JSON payload
    ├─ Validate event structure
    └─ FAIL → Return 400

3. 🆔 Event Duplicate Check
    ├─ Check if event ID has been processed before
    └─ ALREADY_PROCESSED → Return 200 OK

4. 📦 Event Dispatching
    ├─ Lookup event handler from event map
    ├─ Execute handler logic
    └─ NO_HANDLER → Return 200 OK

5. ✅ Mark Processed
    └─ Store event ID as processed

6. 📨 Return Response
    └─ Always 200 OK
```

---

## 3. Event Handler Matrix

This is the official mapping of PayPal event types to LearnPress actions:

| PayPal Event Type | LearnPress Action | Order Status | Subscription Status | Notes |
|---|---|---|---|---|
| **`BILLING.SUBSCRIPTION.CREATED`** | Store PayPal subscription ID | `pending` | `pending` | First event received when user subscribes |
| **`BILLING.SUBSCRIPTION.ACTIVATED`** | Activate subscription, grant course access | `completed` | `active` | Payment successfully authorized |
| **`BILLING.SUBSCRIPTION.CANCELLED`** | Schedule cancellation at period end | `completed` | `active` | ❗ **DO NOT CANCEL IMMEDIATELY** - user paid for current period |
| **`BILLING.SUBSCRIPTION.SUSPENDED`** | Suspend subscription, revoke access | `on-hold` | `on-hold` | PayPal suspended after failed payments |
| **`BILLING.SUBSCRIPTION.EXPIRED`** | Expire subscription permanently | `completed` | `expired` | Subscription reached end date |
| **`BILLING.SUBSCRIPTION.PAYMENT.FAILED`** | Log failure, send notification | `failed` | `active` | PayPal will retry 3 times before suspending |
| **`PAYMENT.SALE.COMPLETED`** | Process renewal payment, extend subscription | `completed` | `active` | Recurring payment received successfully |
| **`PAYMENT.SALE.REFUNDED`** | Refund order, adjust access | `refunded` | *depends* | Full refund = revoke access immediately |
| **`PAYMENT.SALE.REVERSED`** | Chargeback / dispute | `refunded` | `suspended` | ❗ **IMMEDIATELY REVOKE ACCESS** |

---

## 4. State Transition Rules

### ✅ Normal Successful Flow
```
CREATED → ACTIVATED → [PAYMENT.COMPLETED] (repeat monthly) → [CANCELLED / EXPIRED]
```

### ❌ Failed Payment Flow
```
PAYMENT.FAILED → (Retry 1) → (Retry 2) → (Retry 3) → SUSPENDED
```

### ⚠️ Chargeback Flow
```
PAYMENT.REVERSED → SUSPENDED (immediately)
```

---

## 5. Idempotency Rules

All handlers **MUST** be idempotent:
1. Always check existing state before making changes
2. Never process the same event ID twice
3. Processing an event multiple times produces exactly the same result
4. No side effects on duplicate delivery

Stored event IDs are kept for **90 days** after processing.

---

## 6. Error Handling

| Scenario | Response Code | Action |
|---|---|---|
| Invalid signature | `403 Forbidden` | Log error, do not process |
| Invalid JSON payload | `400 Bad Request` | Log error |
| Missing required headers | `400 Bad Request` | Log error |
| Gateway not enabled | `404 Not Found` | Log error |
| Duplicate event | `200 OK` | Silently ignore |
| Unhandled event type | `200 OK` | Log for reference |
| Handler execution failed | `200 OK` | Log full error, PayPal will retry |

---

## 7. Logging Requirements

All webhook events **MUST** log:
- Event ID
- Event type
- PayPal subscription ID
- Processing result (success/failure)
- Full error message on failure

Logs are stored in: `wp-content/uploads/learnpress/logs/paypal-webhooks.log`

---

## 8. Testing Scenarios

Required test cases for implementation:

| Test Case | Expected Result |
|---|---|
| Valid signature | Event processed successfully |
| Invalid signature | 403 response, no changes |
| Duplicate event ID | 200 response, no changes |
| Unhandled event type | 200 response, logged |
| Order not found | 200 response, logged |
| Event processed out of order | State remains correct |

---

## Reference Documents

- [PayPal Subscription Webhooks Official Docs](https://developer.paypal.com/docs/subscriptions/reference/webhooks/)
- [PayPal Webhook Verification](https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature)
- [LearnPress Subscription Lifecycle](internal://docs/subscriptions/lifecycle.md)
