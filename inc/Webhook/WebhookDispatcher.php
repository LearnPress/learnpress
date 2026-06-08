<?php

namespace LearnPress\Webhook;

use LearnPress\Models\Webhook\WebhookModel;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Dispatch outbound LearnPress webhook events.
 */
class WebhookDispatcher {
	/**
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * Delivery keys already processed in the current request.
	 *
	 * @var array<string, bool>
	 */
	protected $processed = array();

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register LearnPress event hooks.
	 */
	protected function __construct() {
		add_action( 'learnpress/user/course-enrolled', array( $this, 'handle_course_enrolled' ), 10, 3 );
		add_action( 'learn-press/order/created', array( $this, 'handle_order_created' ), 10, 2 );
		add_action( 'learn-press/order/status-changed', array( $this, 'handle_order_status_changed' ), 10, 3 );
		add_action( 'learn-press/checkout-order-processed', array( $this, 'handle_checkout_order_processed' ), 10, 2 );
		add_action( 'learn_press_course_submit_rejected', array( $this, 'handle_course_submit_rejected' ), 10, 10 );
		add_action( 'learn_press_course_submit_approved', array( $this, 'handle_course_submit_approved' ), 10, 10 );
		add_action( 'learn_press_course_submit_for_reviewer', array( $this, 'handle_course_submit_for_review' ), 10, 10 );
		add_action( 'learn-press/user-lesson/completed', array( $this, 'handle_lesson_completed' ) );
		add_action( 'learn-press/user/quiz/started', array( $this, 'handle_quiz_started' ) );
		add_action( 'learn-press/user/quiz-finished', array( $this, 'handle_quiz_finished' ), 10, 4 );
		add_action( 'learn-press/user/quiz/retake', array( $this, 'handle_quiz_retried' ) );
		add_action( 'learn-press/user-course/finished', array( $this, 'handle_course_finished' ) );
		add_action( 'learn-press/user-item/created', array( $this, 'handle_user_item_created' ) );
		add_filter( 'retrieve_password_notification_email', array( $this, 'handle_password_reset_requested' ), 12, 4 );
		add_action( 'learn-press/become-a-teacher-sent', array( $this, 'handle_instructor_requested' ) );
		add_action( 'learn-press/user-become-a-teacher-accept', array( $this, 'handle_instructor_accepted' ) );
		add_action( 'learn-press/user-become-a-teacher-deny', array( $this, 'handle_instructor_denied' ) );
		add_action( 'learnpress/membership/order-completed', array( $this, 'handle_membership_order_completed' ), 10, 3 );
		add_action( 'learnpress/membership/subscription/renewed', array( $this, 'handle_membership_subscription_renewed' ), 10, 6 );
		add_action( 'learnpress/membership/subscription/cancelled', array( $this, 'handle_membership_subscription_cancelled' ), 10, 5 );
		add_action( 'learnpress/membership/subscription/suspended', array( $this, 'handle_membership_subscription_suspended' ), 10, 5 );
		add_action( 'learnpress/membership/subscription/expired', array( $this, 'handle_membership_subscription_expired' ), 10, 5 );
		add_action( 'learnpress/membership/subscription/payment-failed', array( $this, 'handle_membership_payment_failed' ), 10, 7 );
		add_action( 'learnpress/membership/reminder/expiring', array( $this, 'handle_membership_reminder_expiring' ) );
		add_action( 'learnpress/membership/course-resumed', array( $this, 'handle_membership_course_resumed' ), 10, 3 );
		add_action( 'learn-press/assignment/user/start', array( $this, 'handle_assignment_started' ) );
		add_action( 'learn-press/assignment/student-start-assignment', array( $this, 'handle_assignment_started_legacy' ), 10, 4 );
		add_action( 'learn-press/assignment/student-submitted', array( $this, 'handle_assignment_submitted' ), 10, 2 );
		add_action( 'learn-press/assignment/instructor-evaluated', array( $this, 'handle_assignment_evaluated' ), 10, 2 );
		add_action( 'learn-press/instructor-evaluated-assignment', array( $this, 'handle_assignment_evaluated_legacy' ), 10, 2 );
		add_action( 'learn-press/assignment/instructor-re-evaluated', array( $this, 'handle_assignment_re_evaluated' ), 10, 2 );
		add_action( 'learn-press/instructor-re-evaluated-assignment', array( $this, 'handle_assignment_re_evaluated_legacy' ), 10, 2 );
		add_action( 'learn-press/assignment/user/retake', array( $this, 'handle_assignment_retried' ) );
		add_action( 'learn-press/assignment/student-retake-assignment', array( $this, 'handle_assignment_retried_legacy' ), 10, 4 );
		add_action( 'learn-press/announcement/created', array( $this, 'handle_announcement_created' ), 10, 3 );
		add_action( 'learn-press/announcement/email-queued', array( $this, 'handle_announcement_email_queued' ), 10, 2 );
	}

	/**
	 * Dispatch enrollment events.
	 *
	 * @param int $order_id Order ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function handle_course_enrolled( $order_id, $course_id, $user_id ): void {
		$data = array(
			'order_id'  => absint( $order_id ),
			'course_id' => absint( $course_id ),
			'user_id'   => absint( $user_id ),
		);
		$key  = "{$data['order_id']}:{$data['course_id']}:{$data['user_id']}";

		$this->dispatch( 'user.course_enrolled', $data, $key );
		$this->dispatch( 'course.enrolled', $data, $key );
	}

	/**
	 * Dispatch order created event.
	 *
	 * @param int       $order_id Order ID.
	 * @param \LP_Order $order    Order object.
	 *
	 * @return void
	 */
	public function handle_order_created( $order_id, $order = null ): void {
		$order_id = absint( $order_id );
		$this->dispatch( 'order.created', $this->build_order_data( $order_id, $order ), (string) $order_id );
	}

	/**
	 * Dispatch order status events.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 *
	 * @return void
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status ): void {
		$order_id   = absint( $order_id );
		$old_status = sanitize_text_field( (string) $old_status );
		$new_status = sanitize_text_field( (string) $new_status );
		$data       = $this->build_order_data(
			$order_id,
			null,
			array(
				'old_status' => $old_status,
				'new_status' => $new_status,
			)
		);

		$this->dispatch( 'order.status_changed', $data, "{$order_id}:{$old_status}:{$new_status}" );

		$status_events = array(
			'processing' => 'order.processing',
			'completed'  => 'order.completed',
			'cancelled'  => 'order.cancelled',
			'failed'     => 'order.failed',
			'refunded'   => 'order.refunded',
		);

		if ( isset( $status_events[ $new_status ] ) ) {
			$this->dispatch( $status_events[ $new_status ], $data, "{$order_id}:{$new_status}" );
		}

		$transition_events = array(
			'pending:processing' => 'order.pending_to_processing',
			'pending:completed'  => 'order.pending_to_completed',
		);
		$transition_key    = "{$old_status}:{$new_status}";
		if ( isset( $transition_events[ $transition_key ] ) ) {
			$this->dispatch( $transition_events[ $transition_key ], $data, "{$order_id}:{$transition_key}" );
		}
	}

	/**
	 * Dispatch checkout processed event.
	 *
	 * @param int          $order_id Order ID.
	 * @param \LP_Checkout $checkout Checkout object.
	 *
	 * @return void
	 */
	public function handle_checkout_order_processed( $order_id, $checkout = null ): void {
		$extra = array();
		if ( is_object( $checkout ) && method_exists( $checkout, 'get_checkout_email' ) ) {
			$extra['checkout_email'] = sanitize_email( (string) $this->call_method( $checkout, 'get_checkout_email', '' ) );
		}

		$order_id = absint( $order_id );
		$this->dispatch( 'checkout.order_processed', $this->build_order_data( $order_id, null, $extra ), (string) $order_id );
	}

	/**
	 * Dispatch course rejected event.
	 *
	 * @return void
	 */
	public function handle_course_submit_rejected(): void {
		$this->dispatch_course_submit_event( 'course.submit_rejected', func_get_args() );
	}

	/**
	 * Dispatch course approved event.
	 *
	 * @return void
	 */
	public function handle_course_submit_approved(): void {
		$this->dispatch_course_submit_event( 'course.submit_approved', func_get_args() );
	}

	/**
	 * Dispatch course submitted-for-review event.
	 *
	 * @return void
	 */
	public function handle_course_submit_for_review(): void {
		$this->dispatch_course_submit_event( 'course.submit_for_review', func_get_args() );
	}

	/**
	 * Dispatch password reset request event without exposing reset key/link.
	 *
	 * @param array    $data_mail Mail data.
	 * @param string   $key Reset key.
	 * @param string   $user_login User login.
	 * @param \WP_User $user_data User object.
	 *
	 * @return array
	 */
	public function handle_password_reset_requested( $data_mail, $key, $user_login, $user_data ): array {
		unset( $key );

		$user_id    = is_object( $user_data ) && isset( $user_data->ID ) ? absint( $user_data->ID ) : 0;
		$user_email = is_object( $user_data ) && isset( $user_data->user_email ) ? sanitize_email( $user_data->user_email ) : '';
		$data       = array(
			'user_id'    => $user_id,
			'user_login' => sanitize_user( (string) $user_login ),
			'user_email' => $user_email,
		);

		$this->dispatch( 'user.password_reset_requested', $data, $user_id > 0 ? (string) $user_id : $data['user_login'] );

		return is_array( $data_mail ) ? $data_mail : array();
	}

	/**
	 * Dispatch instructor request event.
	 *
	 * @param array $request Request data.
	 *
	 * @return void
	 */
	public function handle_instructor_requested( $request ): void {
		$data = $this->build_instructor_request_data( is_array( $request ) ? $request : array() );
		$key  = $data['user_id'] > 0 ? (string) $data['user_id'] : $data['email'];

		$this->dispatch( 'instructor.requested', $data, $key );
	}

	/**
	 * Dispatch instructor accepted event.
	 *
	 * @param string $user_email User email.
	 *
	 * @return void
	 */
	public function handle_instructor_accepted( $user_email ): void {
		$this->dispatch_instructor_status_event( 'instructor.accepted', $user_email );
	}

	/**
	 * Dispatch instructor denied event.
	 *
	 * @param string $user_email User email.
	 *
	 * @return void
	 */
	public function handle_instructor_denied( $user_email ): void {
		$this->dispatch_instructor_status_event( 'instructor.denied', $user_email );
	}

	/**
	 * Dispatch membership order completed event.
	 *
	 * @param int $order_id Order ID.
	 * @param int $user_id User ID.
	 * @param int $plan_id Membership plan ID.
	 *
	 * @return void
	 */
	public function handle_membership_order_completed( $order_id, $user_id, $plan_id ): void {
		$data = $this->build_membership_payload(
			array(
				'order_id' => $order_id,
				'user_id'  => $user_id,
				'plan_id'  => $plan_id,
				'trigger'  => 'order_completed',
			)
		);

		$this->dispatch( 'membership.order_completed', $data, $this->get_membership_object_key( $data ) );
	}

	/**
	 * Dispatch membership renewed event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $plan_id Membership plan ID.
	 * @param int    $order_id Parent order ID.
	 * @param int    $renew_order_id Renewal order ID.
	 * @param array  $webhook_data Gateway data.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return void
	 */
	public function handle_membership_subscription_renewed( $user_id, $plan_id, $order_id, $renew_order_id, $webhook_data = array(), $gateway_id = '' ): void {
		$data = $this->build_membership_payload(
			array(
				'user_id'        => $user_id,
				'plan_id'        => $plan_id,
				'order_id'       => $order_id,
				'renew_order_id' => $renew_order_id,
				'gateway_id'     => $gateway_id,
				'webhook_data'   => $webhook_data,
				'trigger'        => 'renewed',
			)
		);

		$this->dispatch( 'membership.subscription_renewed', $data, $this->get_membership_object_key( $data ) );
	}

	/**
	 * Dispatch membership cancelled event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $plan_id Membership plan ID.
	 * @param int    $order_id Order ID.
	 * @param array  $webhook_data Gateway data.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return void
	 */
	public function handle_membership_subscription_cancelled( $user_id, $plan_id, $order_id, $webhook_data = array(), $gateway_id = '' ): void {
		$this->dispatch_membership_status_event( 'membership.subscription_cancelled', 'cancelled', $user_id, $plan_id, $order_id, $webhook_data, $gateway_id );
	}

	/**
	 * Dispatch membership suspended event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $plan_id Membership plan ID.
	 * @param int    $order_id Order ID.
	 * @param array  $webhook_data Gateway data.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return void
	 */
	public function handle_membership_subscription_suspended( $user_id, $plan_id, $order_id, $webhook_data = array(), $gateway_id = '' ): void {
		$this->dispatch_membership_status_event( 'membership.subscription_suspended', 'suspended', $user_id, $plan_id, $order_id, $webhook_data, $gateway_id );
	}

	/**
	 * Dispatch membership expired event.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $plan_id Membership plan ID.
	 * @param int    $order_id Order ID.
	 * @param array  $webhook_data Gateway data.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return void
	 */
	public function handle_membership_subscription_expired( $user_id, $plan_id = 0, $order_id = 0, $webhook_data = array(), $gateway_id = '' ): void {
		if ( is_array( $user_id ) ) {
			$data = $this->build_membership_payload( array_merge( $user_id, array( 'trigger' => 'expired' ) ) );
		} else {
			$data = $this->build_membership_payload(
				array(
					'user_id'      => $user_id,
					'plan_id'      => $plan_id,
					'order_id'     => $order_id,
					'gateway_id'   => $gateway_id,
					'webhook_data' => $webhook_data,
					'trigger'      => 'expired',
				)
			);
		}

		$this->dispatch( 'membership.subscription_expired', $data, $this->get_membership_object_key( $data ) );
	}

	/**
	 * Dispatch membership payment failed event.
	 *
	 * @param mixed  $payload_or_user_id Payload map or user ID.
	 * @param int    $plan_id Membership plan ID.
	 * @param int    $order_id Order ID.
	 * @param int    $renew_order_id Renewal order ID.
	 * @param int    $grace_days Grace days.
	 * @param array  $webhook_data Gateway data.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return void
	 */
	public function handle_membership_payment_failed( $payload_or_user_id, $plan_id = 0, $order_id = 0, $renew_order_id = 0, $grace_days = 0, $webhook_data = array(), $gateway_id = '' ): void {
		if ( is_array( $payload_or_user_id ) ) {
			$data = $this->build_membership_payload( array_merge( $payload_or_user_id, array( 'trigger' => 'payment_failed' ) ) );
		} else {
			$data = $this->build_membership_payload(
				array(
					'user_id'        => $payload_or_user_id,
					'plan_id'        => $plan_id,
					'order_id'       => $order_id,
					'renew_order_id' => $renew_order_id,
					'grace_days'     => $grace_days,
					'gateway_id'     => $gateway_id,
					'webhook_data'   => $webhook_data,
					'trigger'        => 'payment_failed',
				)
			);
		}

		$this->dispatch( 'membership.payment_failed', $data, $this->get_membership_object_key( $data ) );
	}

	/**
	 * Dispatch membership reminder event.
	 *
	 * @param array $payload Reminder payload.
	 *
	 * @return void
	 */
	public function handle_membership_reminder_expiring( $payload ): void {
		$data = $this->build_membership_payload( is_array( $payload ) ? array_merge( $payload, array( 'trigger' => 'reminder' ) ) : array() );

		$this->dispatch( 'membership.reminder_expiring', $data, $this->get_membership_object_key( $data ) );
	}

	/**
	 * Dispatch membership course resumed event.
	 *
	 * @param int $order_id Order ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function handle_membership_course_resumed( $order_id, $course_id, $user_id ): void {
		$data = array(
			'order_id'  => absint( $order_id ),
			'course_id' => absint( $course_id ),
			'user_id'   => absint( $user_id ),
		);

		$this->dispatch( 'membership.course_resumed', $data, "{$data['order_id']}:{$data['course_id']}:{$data['user_id']}" );
	}

	/**
	 * Dispatch lesson completed event.
	 *
	 * @param object $user_item User lesson model.
	 *
	 * @return void
	 */
	public function handle_lesson_completed( $user_item ): void {
		$data = $this->build_user_item_event_data( $user_item );

		$this->dispatch( 'lesson.completed', $data, $this->get_user_item_object_key( $data ) );
	}

	/**
	 * Dispatch quiz started event.
	 *
	 * @param object $user_quiz User quiz model.
	 *
	 * @return void
	 */
	public function handle_quiz_started( $user_quiz ): void {
		$data = $this->build_user_item_event_data( $user_quiz );

		$this->dispatch( 'quiz.started', $data, $this->get_user_item_object_key( $data ) );
	}

	/**
	 * Dispatch quiz finished event.
	 *
	 * @param int    $quiz_id Quiz ID.
	 * @param int    $course_id Course ID.
	 * @param int    $user_id User ID.
	 * @param object $user_quiz User quiz model.
	 *
	 * @return void
	 */
	public function handle_quiz_finished( $quiz_id, $course_id, $user_id, $user_quiz = null ): void {
		$data = array(
			'quiz_id'   => absint( $quiz_id ),
			'course_id' => absint( $course_id ),
			'user_id'   => absint( $user_id ),
		);

		if ( is_object( $user_quiz ) ) {
			$data = array_merge( $data, $this->build_user_item_event_data( $user_quiz ) );
		}

		$this->dispatch( 'quiz.finished', $data, $this->get_user_item_object_key( $data ) );
	}

	/**
	 * Dispatch quiz retried event.
	 *
	 * @param object $user_quiz User quiz model.
	 *
	 * @return void
	 */
	public function handle_quiz_retried( $user_quiz ): void {
		$data = $this->build_user_item_event_data( $user_quiz );

		$this->dispatch( 'quiz.retried', $data, $this->get_user_item_object_key( $data ) );
	}

	/**
	 * Dispatch course finished event.
	 *
	 * @param object $user_course User course model.
	 *
	 * @return void
	 */
	public function handle_course_finished( $user_course ): void {
		$data = $this->build_user_item_event_data( $user_course );

		$this->dispatch( 'course.finished', $data, $this->get_user_item_object_key( $data ) );
	}

	/**
	 * Dispatch user item created event.
	 *
	 * @param object $user_item User item model.
	 *
	 * @return void
	 */
	public function handle_user_item_created( $user_item ): void {
		$data = $this->build_user_item_event_data( $user_item );

		$this->dispatch( 'user_item.created', $data, $this->get_user_item_object_key( $data ) );
	}

	/**
	 * Dispatch assignment started event.
	 *
	 * @param object $user_assignment User assignment model.
	 *
	 * @return void
	 */
	public function handle_assignment_started( $user_assignment ): void {
		$data = $this->build_assignment_data_from_user_item( $user_assignment );

		$this->dispatch( 'assignment.started', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch assignment started event from legacy hook.
	 *
	 * @param mixed $user_item_or_id User assignment model or user item ID.
	 * @param int   $user_id User ID.
	 * @param int   $assignment_id Assignment ID.
	 * @param int   $course_id Course ID.
	 *
	 * @return void
	 */
	public function handle_assignment_started_legacy( $user_item_or_id, $user_id = 0, $assignment_id = 0, $course_id = 0 ): void {
		if ( is_object( $user_item_or_id ) ) {
			$this->handle_assignment_started( $user_item_or_id );
			return;
		}

		$data = $this->build_assignment_data( $user_id, $assignment_id, $course_id, $user_item_or_id );

		$this->dispatch( 'assignment.started', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch assignment submitted event.
	 *
	 * @param int $user_id User ID.
	 * @param int $assignment_id Assignment ID.
	 *
	 * @return void
	 */
	public function handle_assignment_submitted( $user_id, $assignment_id ): void {
		$data = $this->build_assignment_data( $user_id, $assignment_id );

		$this->dispatch( 'assignment.submitted', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch assignment evaluated event.
	 *
	 * @param int $user_id User ID.
	 * @param int $assignment_id Assignment ID.
	 *
	 * @return void
	 */
	public function handle_assignment_evaluated( $user_id, $assignment_id ): void {
		$data = $this->build_assignment_data( $user_id, $assignment_id );

		$this->dispatch( 'assignment.evaluated', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch assignment evaluated event from legacy hook.
	 *
	 * @param int $assignment_id Assignment ID.
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function handle_assignment_evaluated_legacy( $assignment_id, $user_id ): void {
		$this->handle_assignment_evaluated( $user_id, $assignment_id );
	}

	/**
	 * Dispatch assignment re-evaluated event.
	 *
	 * @param int $user_id User ID.
	 * @param int $assignment_id Assignment ID.
	 *
	 * @return void
	 */
	public function handle_assignment_re_evaluated( $user_id, $assignment_id ): void {
		$data = $this->build_assignment_data( $user_id, $assignment_id );

		$this->dispatch( 'assignment.re_evaluated', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch assignment re-evaluated event from legacy hook.
	 *
	 * @param int $assignment_id Assignment ID.
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function handle_assignment_re_evaluated_legacy( $assignment_id, $user_id ): void {
		$this->handle_assignment_re_evaluated( $user_id, $assignment_id );
	}

	/**
	 * Dispatch assignment retried event.
	 *
	 * @param object $user_assignment User assignment model.
	 *
	 * @return void
	 */
	public function handle_assignment_retried( $user_assignment ): void {
		$data = $this->build_assignment_data_from_user_item( $user_assignment );

		$this->dispatch( 'assignment.retried', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch assignment retried event from legacy hook.
	 *
	 * @param int $user_item_id User item ID.
	 * @param int $user_id User ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $course_id Course ID.
	 *
	 * @return void
	 */
	public function handle_assignment_retried_legacy( $user_item_id, $user_id, $assignment_id, $course_id ): void {
		$data = $this->build_assignment_data( $user_id, $assignment_id, $course_id, $user_item_id );

		$this->dispatch( 'assignment.retried', $data, $this->get_assignment_object_key( $data ) );
	}

	/**
	 * Dispatch announcement created event.
	 *
	 * @param int   $announcement_id Announcement ID.
	 * @param array $course_ids Course IDs.
	 * @param bool  $send_mail Whether email was requested.
	 *
	 * @return void
	 */
	public function handle_announcement_created( $announcement_id, $course_ids = array(), $send_mail = false ): void {
		$data = $this->build_announcement_data( $announcement_id, $course_ids, $send_mail );

		$this->dispatch( 'announcement.created', $data, (string) $data['announcement_id'] );
	}

	/**
	 * Dispatch announcement email queued event.
	 *
	 * @param int   $announcement_id Announcement ID.
	 * @param array $course_ids Course IDs.
	 *
	 * @return void
	 */
	public function handle_announcement_email_queued( $announcement_id, $course_ids = array() ): void {
		$data = $this->build_announcement_data( $announcement_id, $course_ids, true );

		$this->dispatch( 'announcement.email_queued', $data, (string) $data['announcement_id'] );
	}

	/**
	 * Dispatch an event to matching active webhooks.
	 *
	 * @param string $event_key Event key.
	 * @param array  $data Event data.
	 * @param string $object_key Stable object key for in-request dedupe.
	 *
	 * @return int Number of delivery attempts.
	 */
	public function dispatch( string $event_key, array $data = array(), string $object_key = '' ): int {
		if ( ! $this->is_enabled() ) {
			return 0;
		}

		$event_keys = WebhookEvents::sanitize( array( $event_key ) );
		if ( empty( $event_keys ) ) {
			return 0;
		}

		$event_key  = reset( $event_keys );
		$object_key = '' !== $object_key ? $object_key : md5( wp_json_encode( $data ) );

		try {
			$webhooks = WebhookModel::get_active_webhooks_for_event( $event_key );
		} catch ( Throwable $e ) {
			return 0;
		}

		$sent = 0;
		foreach ( $webhooks as $webhook ) {
			if ( ! $webhook instanceof WebhookModel ) {
				continue;
			}

			$dedupe_key = "{$event_key}:{$object_key}:{$webhook->get_webhook_id()}";
			if ( isset( $this->processed[ $dedupe_key ] ) ) {
				continue;
			}

			$this->processed[ $dedupe_key ] = true;
			if ( $this->deliver( $webhook, $event_key, $data ) ) {
				++$sent;
			}
		}

		return $sent;
	}

	/**
	 * Deliver a webhook HTTP request.
	 *
	 * @param WebhookModel $webhook Webhook model.
	 * @param string       $event_key Event key.
	 * @param array        $data Event data.
	 *
	 * @return bool
	 */
	protected function deliver( WebhookModel $webhook, string $event_key, array $data ): bool {
		$delivery_id = $this->generate_delivery_id();
		$payload     = WebhookResourceSerializer::instance()->build_payload( $event_key, $delivery_id, $data );

		$payload_filtered = apply_filters( 'learn-press/webhook/payload', $payload, $event_key, $webhook );
		if ( is_array( $payload_filtered ) ) {
			$payload = $payload_filtered;
		}

		$body = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $body ) || '' === $body ) {
			return false;
		}

		$args = array(
			'method'      => 'POST',
			'timeout'     => 10,
			'redirection' => 0,
			'blocking'    => true,
			'headers'     => array(
				'Content-Type'                   => 'application/json',
				'X-LP-Webhook-Source'            => home_url( '/' ),
				'X-LP-Webhook-Event'             => $event_key,
				'X-LP-Webhook-ID'                => (string) $webhook->get_webhook_id(),
				'X-LP-Webhook-Delivery-ID'       => $delivery_id,
				'X-LP-Webhook-Signature'         => self::sign_body( $body, $webhook->secret ),
				'X-LearnPress-Webhook-Event'     => $event_key,
				'X-LearnPress-Webhook-ID'        => (string) $webhook->get_webhook_id(),
				'X-LearnPress-Webhook-Signature' => self::sign_body( $body, $webhook->secret ),
			),
			'body'        => $body,
		);

		$args = apply_filters( 'learn-press/webhook/http-args', $args, $webhook, $payload );
		if ( ! is_array( $args ) ) {
			return false;
		}

		try {
			$response = wp_safe_remote_post( $webhook->delivery_url, $args );
		} catch ( Throwable $e ) {
			$response = $e;
		}

		$this->log_delivery_result( $webhook, $event_key, $delivery_id, $response );

		do_action( 'learn-press/webhook/delivered', $webhook, $payload, $response, $args );

		return $this->is_delivery_successful( $response );
	}

	/**
	 * Check whether the HTTP delivery was accepted by the receiver.
	 *
	 * @param mixed $response HTTP response, WP_Error, or Throwable.
	 *
	 * @return bool
	 */
	protected function is_delivery_successful( $response ): bool {
		if ( $response instanceof Throwable || is_wp_error( $response ) ) {
			return false;
		}

		$status_code = absint( wp_remote_retrieve_response_code( $response ) );

		return $status_code >= 200 && $status_code < 300;
	}

	/**
	 * Log failed delivery details, and successful deliveries when explicitly enabled.
	 *
	 * @param WebhookModel $webhook Webhook model.
	 * @param string       $event_key Event key.
	 * @param string       $delivery_id Delivery ID.
	 * @param mixed        $response HTTP response, WP_Error, or Throwable.
	 *
	 * @return void
	 */
	protected function log_delivery_result( WebhookModel $webhook, string $event_key, string $delivery_id, $response ): void {
		$context = array(
			'event'       => $event_key,
			'webhook_id'  => $webhook->get_webhook_id(),
			'delivery_id' => $delivery_id,
			'url'         => $this->redact_url_for_log( $webhook->delivery_url ),
		);

		if ( $response instanceof Throwable ) {
			$this->write_delivery_log(
				'error',
				array_merge(
					$context,
					array(
						'error_type'    => get_class( $response ),
						'error_message' => $response->getMessage(),
					)
				)
			);

			return;
		}

		if ( is_wp_error( $response ) ) {
			$this->write_delivery_log(
				'error',
				array_merge(
					$context,
					array(
						'error_code'    => $response->get_error_code(),
						'error_message' => $response->get_error_message(),
					)
				)
			);

			return;
		}

		$status_code = absint( wp_remote_retrieve_response_code( $response ) );
		$context     = array_merge(
			$context,
			array(
				'status_code'      => $status_code,
				'response_message' => wp_remote_retrieve_response_message( $response ),
			)
		);

		if ( $status_code < 200 || $status_code >= 300 ) {
			$this->write_delivery_log(
				'error',
				array_merge(
					$context,
					array(
						'response_body' => $this->truncate_log_value( wp_remote_retrieve_body( $response ) ),
					)
				)
			);

			return;
		}

		if ( $this->should_log_successful_delivery( $webhook, $event_key, $delivery_id, $response ) ) {
			$this->write_delivery_log( 'info', $context );
		}
	}

	/**
	 * Check whether successful deliveries should be written to error_log.
	 *
	 * @param WebhookModel $webhook Webhook model.
	 * @param string       $event_key Event key.
	 * @param string       $delivery_id Delivery ID.
	 * @param mixed        $response HTTP response.
	 *
	 * @return bool
	 */
	protected function should_log_successful_delivery( WebhookModel $webhook, string $event_key, string $delivery_id, $response ): bool {
		return (bool) apply_filters(
			'learn-press/webhook/log-successful-delivery',
			false,
			$webhook,
			$event_key,
			$delivery_id,
			$response
		);
	}

	/**
	 * Write a structured webhook delivery log entry.
	 *
	 * @param string $level Log level.
	 * @param array  $context Log context.
	 *
	 * @return void
	 */
	protected function write_delivery_log( string $level, array $context ): void {
		$message = wp_json_encode(
			array_merge(
				array(
					'level' => $level,
				),
				$context
			),
			JSON_UNESCAPED_SLASHES
		);

		error_log( '[LearnPress webhook delivery] ' . ( is_string( $message ) ? $message : print_r( $context, true ) ) );
	}

	/**
	 * Trim long response bodies before writing to error_log.
	 *
	 * @param mixed $value Log value.
	 * @param int   $limit Maximum length.
	 *
	 * @return string
	 */
	protected function truncate_log_value( $value, int $limit = 1000 ): string {
		$value = (string) $value;

		if ( strlen( $value ) <= $limit ) {
			return $value;
		}

		return substr( $value, 0, $limit ) . '...';
	}

	/**
	 * Remove query string and credentials from delivery URLs before logging.
	 *
	 * @param string $url Delivery URL.
	 *
	 * @return string
	 */
	protected function redact_url_for_log( string $url ): string {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme = ! empty( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$port   = ! empty( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';
		$path   = $parts['path'] ?? '';

		return $scheme . $parts['host'] . $port . $path;
	}

	/**
	 * Check global webhook setting.
	 *
	 * @return bool
	 */
	protected function is_enabled(): bool {
		return 'yes' === \LP_Settings::get_option( 'enable_webhook_integration', 'no' );
	}

	/**
	 * Generate HMAC SHA256 signature for request body.
	 *
	 * @param string $body Raw JSON body.
	 * @param string $secret Webhook secret.
	 *
	 * @return string
	 */
	public static function sign_body( string $body, string $secret ): string {
		return base64_encode( hash_hmac( 'sha256', $body, $secret, true ) );
	}

	/**
	 * Dispatch old course submit email hooks.
	 *
	 * @param string $event_key Event key.
	 * @param array  $args Hook args.
	 *
	 * @return void
	 */
	protected function dispatch_course_submit_event( string $event_key, array $args ): void {
		$data = array(
			'hook' => current_filter(),
			'args' => $this->sanitize_payload_value( $args ),
		);

		$object_key = '';
		if ( isset( $args[0] ) && is_scalar( $args[0] ) ) {
			$object_key = sanitize_text_field( (string) $args[0] );
		}

		$this->dispatch( $event_key, $data, $object_key );
	}

	/**
	 * Build instructor request payload data.
	 *
	 * @param array $request Request data.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_instructor_request_data( array $request ): array {
		$email = sanitize_email( (string) ( $request['bat_email'] ?? '' ) );
		$user  = $email ? get_user_by( 'email', $email ) : false;

		return array(
			'user_id' => $user instanceof \WP_User ? absint( $user->ID ) : 0,
			'name'    => sanitize_text_field( (string) ( $request['bat_name'] ?? '' ) ),
			'email'   => $email,
			'phone'   => sanitize_text_field( (string) ( $request['bat_phone'] ?? '' ) ),
			'message' => sanitize_textarea_field( (string) ( $request['bat_message'] ?? '' ) ),
		);
	}

	/**
	 * Dispatch instructor status payload by user email.
	 *
	 * @param string $event_key Event key.
	 * @param string $user_email User email.
	 *
	 * @return void
	 */
	protected function dispatch_instructor_status_event( string $event_key, string $user_email ): void {
		$user_email = sanitize_email( $user_email );
		$user       = $user_email ? get_user_by( 'email', $user_email ) : false;
		$user_id    = $user instanceof \WP_User ? absint( $user->ID ) : 0;
		$data       = array(
			'user_id'    => $user_id,
			'user_email' => $user_email,
		);

		$this->dispatch( $event_key, $data, $user_id > 0 ? (string) $user_id : $user_email );
	}

	/**
	 * Dispatch a membership status event.
	 *
	 * @param string $event_key Event key.
	 * @param string $trigger Trigger key.
	 * @param int    $user_id User ID.
	 * @param int    $plan_id Membership plan ID.
	 * @param int    $order_id Order ID.
	 * @param array  $webhook_data Gateway data.
	 * @param string $gateway_id Gateway ID.
	 *
	 * @return void
	 */
	protected function dispatch_membership_status_event( string $event_key, string $trigger, $user_id, $plan_id, $order_id, $webhook_data = array(), $gateway_id = '' ): void {
		$data = $this->build_membership_payload(
			array(
				'user_id'      => $user_id,
				'plan_id'      => $plan_id,
				'order_id'     => $order_id,
				'gateway_id'   => $gateway_id,
				'webhook_data' => $webhook_data,
				'trigger'      => $trigger,
			)
		);

		$this->dispatch( $event_key, $data, $this->get_membership_object_key( $data ) );
	}

	/**
	 * Build normalized membership payload.
	 *
	 * @param array $payload Raw payload.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_membership_payload( array $payload ): array {
		$days_left    = $payload['days_left'] ?? null;
		$grace_days   = $payload['grace_days'] ?? null;
		$webhook_data = $payload['webhook_data'] ?? array();

		return array(
			'order_id'       => absint( $payload['order_id'] ?? 0 ),
			'user_id'        => absint( $payload['user_id'] ?? 0 ),
			'plan_id'        => absint( $payload['plan_id'] ?? 0 ),
			'member_id'      => absint( $payload['member_id'] ?? 0 ),
			'renew_order_id' => absint( $payload['renew_order_id'] ?? 0 ),
			'days_left'      => null !== $days_left ? max( 0, (int) $days_left ) : null,
			'grace_days'     => null !== $grace_days ? max( 0, absint( $grace_days ) ) : null,
			'gateway_id'     => sanitize_key( (string) ( $payload['gateway_id'] ?? '' ) ),
			'trigger'        => sanitize_key( (string) ( $payload['trigger'] ?? '' ) ),
			'webhook_data'   => is_array( $webhook_data ) ? $this->sanitize_payload_value( $webhook_data ) : array(),
		);
	}

	/**
	 * Get stable membership event object key.
	 *
	 * @param array $data Payload data.
	 *
	 * @return string
	 */
	protected function get_membership_object_key( array $data ): string {
		$primary_id = absint( $data['renew_order_id'] ?? 0 );
		if ( ! $primary_id ) {
			$primary_id = absint( $data['order_id'] ?? 0 );
		}
		if ( ! $primary_id ) {
			$primary_id = absint( $data['member_id'] ?? 0 );
		}

		return sprintf(
			'%d:%d:%d:%s:%s',
			absint( $data['user_id'] ?? 0 ),
			absint( $data['plan_id'] ?? 0 ),
			$primary_id,
			sanitize_key( (string) ( $data['trigger'] ?? '' ) ),
			sanitize_text_field( (string) ( $data['days_left'] ?? '' ) )
		);
	}

	/**
	 * Build assignment payload from a user item model.
	 *
	 * @param object $user_assignment User assignment model.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_assignment_data_from_user_item( $user_assignment ): array {
		$data                  = $this->build_user_item_event_data( $user_assignment );
		$user_item             = is_array( $data['user_item'] ?? null ) ? $data['user_item'] : array();
		$data['assignment_id'] = absint( $data['item_id'] ?? 0 );
		$data['course_id']     = absint( $data['ref_id'] ?? 0 );
		$data['user_item_id']  = absint( $user_item['user_item_id'] ?? 0 );

		return $data;
	}

	/**
	 * Build assignment payload.
	 *
	 * @param int $user_id User ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $course_id Course ID.
	 * @param int $user_item_id User item ID.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_assignment_data( $user_id, $assignment_id, $course_id = 0, $user_item_id = 0 ): array {
		$assignment_id = absint( $assignment_id );
		$course_id     = absint( $course_id );

		return array(
			'user_id'       => absint( $user_id ),
			'assignment_id' => $assignment_id,
			'item_id'       => $assignment_id,
			'item_type'     => 'lp_assignment',
			'course_id'     => $course_id,
			'ref_id'        => $course_id,
			'ref_type'      => LP_COURSE_CPT,
			'user_item_id'  => absint( $user_item_id ),
		);
	}

	/**
	 * Get stable assignment event object key.
	 *
	 * @param array $data Payload data.
	 *
	 * @return string
	 */
	protected function get_assignment_object_key( array $data ): string {
		$user_item_id = absint( $data['user_item_id'] ?? 0 );
		if ( $user_item_id > 0 ) {
			return (string) $user_item_id;
		}

		return sprintf(
			'%d:%d:%d',
			absint( $data['user_id'] ?? 0 ),
			absint( $data['assignment_id'] ?? $data['item_id'] ?? 0 ),
			absint( $data['course_id'] ?? $data['ref_id'] ?? 0 )
		);
	}

	/**
	 * Build announcement payload.
	 *
	 * @param int   $announcement_id Announcement ID.
	 * @param array $course_ids Course IDs.
	 * @param bool  $send_mail Whether email was requested.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_announcement_data( $announcement_id, $course_ids = array(), bool $send_mail = false ): array {
		$announcement_id = absint( $announcement_id );
		$course_ids      = is_array( $course_ids ) ? array_values( array_filter( array_map( 'absint', $course_ids ) ) ) : array();
		$post            = $announcement_id > 0 ? get_post( $announcement_id ) : null;

		return array(
			'announcement_id' => $announcement_id,
			'course_ids'      => $course_ids,
			'send_mail'       => $send_mail,
			'title'           => $post instanceof \WP_Post ? sanitize_text_field( $post->post_title ) : '',
			'author_id'       => $post instanceof \WP_Post ? absint( $post->post_author ) : 0,
		);
	}

	/**
	 * Build normalized order payload data.
	 *
	 * @param int            $order_id Order ID.
	 * @param \LP_Order|null $order Order object.
	 * @param array          $extra Extra payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_order_data( int $order_id, $order = null, array $extra = array() ): array {
		if ( ! is_object( $order ) && function_exists( 'learn_press_get_order' ) ) {
			$order = learn_press_get_order( $order_id );
		}

		$data = array(
			'order_id' => $order_id,
		);

		if ( is_object( $order ) ) {
			$user_id = $this->call_method( $order, 'get_user_id', 0 );
			if ( is_array( $user_id ) ) {
				$user_id = array_values( array_map( 'absint', $user_id ) );
			} else {
				$user_id = absint( $user_id );
			}

			$data = array_merge(
				$data,
				array(
					'status'         => sanitize_text_field( (string) $this->call_method( $order, 'get_status', '' ) ),
					'user_id'        => $user_id,
					'total'          => $this->call_method( $order, 'get_total', 0 ),
					'subtotal'       => $this->call_method( $order, 'get_subtotal', 0 ),
					'currency'       => sanitize_text_field( (string) $this->call_method( $order, 'get_currency', '' ) ),
					'created_via'    => sanitize_text_field( (string) $this->call_method( $order, 'get_created_via', '' ) ),
					'checkout_email' => sanitize_email( (string) $this->call_method( $order, 'get_checkout_email', '' ) ),
					'items'          => $this->build_order_items_data( $order ),
				)
			);
		}

		return array_merge( $data, $extra );
	}

	/**
	 * Build normalized order item data.
	 *
	 * @param object $order Order object.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_order_items_data( $order ): array {
		$items = $this->call_method( $order, 'get_all_items', array() );
		if ( ! is_array( $items ) ) {
			return array();
		}

		$data = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				$item = (array) $item;
			}

			$data[] = array(
				'order_item_id'   => absint( $item['order_item_id'] ?? 0 ),
				'order_item_name' => sanitize_text_field( (string) ( $item['order_item_name'] ?? '' ) ),
				'item_id'         => absint( $item['item_id'] ?? 0 ),
				'item_type'       => sanitize_key( (string) ( $item['item_type'] ?? '' ) ),
				'quantity'        => absint( $item['quantity'] ?? $item['qty'] ?? 0 ),
				'subtotal'        => $item['subtotal'] ?? '',
				'total'           => $item['total'] ?? '',
			);
		}

		return $data;
	}

	/**
	 * Build normalized user item payload data.
	 *
	 * @param object $user_item User item object.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_user_item_event_data( $user_item ): array {
		$user_item_data = $this->build_user_item_data( $user_item );

		return array(
			'user_id'   => $user_item_data['user_id'],
			'item_id'   => $user_item_data['item_id'],
			'item_type' => $user_item_data['item_type'],
			'ref_id'    => $user_item_data['ref_id'],
			'ref_type'  => $user_item_data['ref_type'],
			'user_item' => $user_item_data,
		);
	}

	/**
	 * Build user item data.
	 *
	 * @param object $user_item User item object.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_user_item_data( $user_item ): array {
		return array(
			'user_item_id' => is_object( $user_item ) && method_exists( $user_item, 'get_user_item_id' ) ? absint( $this->call_method( $user_item, 'get_user_item_id', 0 ) ) : 0,
			'user_id'      => absint( $this->read_property( $user_item, 'user_id', 0 ) ),
			'item_id'      => absint( $this->read_property( $user_item, 'item_id', 0 ) ),
			'item_type'    => sanitize_key( (string) $this->read_property( $user_item, 'item_type', '' ) ),
			'status'       => sanitize_key( (string) $this->read_property( $user_item, 'status', '' ) ),
			'graduation'   => sanitize_key( (string) $this->read_property( $user_item, 'graduation', '' ) ),
			'ref_id'       => absint( $this->read_property( $user_item, 'ref_id', 0 ) ),
			'ref_type'     => sanitize_key( (string) $this->read_property( $user_item, 'ref_type', '' ) ),
			'parent_id'    => absint( $this->read_property( $user_item, 'parent_id', 0 ) ),
			'start_time'   => sanitize_text_field( (string) $this->read_property( $user_item, 'start_time', '' ) ),
			'end_time'     => sanitize_text_field( (string) $this->read_property( $user_item, 'end_time', '' ) ),
		);
	}

	/**
	 * Get stable key for user-item-like payload data.
	 *
	 * @param array $data Payload data.
	 *
	 * @return string
	 */
	protected function get_user_item_object_key( array $data ): string {
		$user_item = $data['user_item'] ?? array();
		if ( ! is_array( $user_item ) ) {
			$user_item = array();
		}

		$user_item_id = absint( $user_item['user_item_id'] ?? 0 );
		if ( $user_item_id > 0 ) {
			return (string) $user_item_id;
		}

		return sprintf(
			'%d:%d:%s:%d:%s',
			absint( $data['user_id'] ?? 0 ),
			absint( $data['item_id'] ?? 0 ),
			sanitize_key( (string) ( $data['item_type'] ?? '' ) ),
			absint( $data['ref_id'] ?? 0 ),
			sanitize_key( (string) ( $data['ref_type'] ?? '' ) )
		);
	}

	/**
	 * Sanitize nested payload values without serializing objects wholesale.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return mixed
	 */
	protected function sanitize_payload_value( $value ) {
		if ( is_null( $value ) || is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean_key           = is_int( $key ) ? $key : sanitize_key( (string) $key );
				$clean[ $clean_key ] = $this->sanitize_payload_value( $item );
			}

			return $clean;
		}

		if ( is_object( $value ) ) {
			$data = array(
				'class' => get_class( $value ),
			);
			if ( method_exists( $value, 'get_id' ) ) {
				$data['id'] = absint( $this->call_method( $value, 'get_id', 0 ) );
			}

			return $data;
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Call an object method when it is available.
	 *
	 * @param object $target Target object.
	 * @param string $method Method name.
	 * @param mixed  $fallback Fallback value.
	 *
	 * @return mixed
	 */
	protected function call_method( $target, string $method, $fallback = null ) {
		if ( ! is_object( $target ) || ! method_exists( $target, $method ) ) {
			return $fallback;
		}

		return $target->{$method}();
	}

	/**
	 * Safely read public object property.
	 *
	 * @param mixed  $target Target object.
	 * @param string $property Property name.
	 * @param mixed  $fallback Fallback value.
	 *
	 * @return mixed
	 */
	protected function read_property( $target, string $property, $fallback = null ) {
		return is_object( $target ) && isset( $target->{$property} ) ? $target->{$property} : $fallback;
	}

	/**
	 * Generate a delivery ID.
	 *
	 * @return string
	 */
	protected function generate_delivery_id(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return 'lpwh_' . wp_generate_uuid4();
		}

		return 'lpwh_' . md5( uniqid( '', true ) );
	}
}
