<?php

namespace LearnPress\Webhook;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of webhook event keys available to administrators.
 */
class WebhookEvents {
	/**
	 * Return registered event keys and labels.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		$events = self::core_events();

		if ( self::is_plugin_active( 'learnpress-membership/learnpress-membership.php' ) ) {
			$events = array_merge( $events, self::membership_events() );
		}

		if ( self::is_plugin_active( 'learnpress-assignments/learnpress-assignments.php' ) ) {
			$events = array_merge( $events, self::assignment_events() );
		}

		if ( self::is_plugin_active( 'learnpress-announcements/learnpress-announcements.php' ) ) {
			$events = array_merge( $events, self::announcement_events() );
		}

		$filtered = apply_filters( 'learn-press/webhook/events', $events );

		return is_array( $filtered ) ? $filtered : $events;
	}

	/**
	 * Return LearnPress core webhook events.
	 *
	 * @return array<string, string>
	 */
	protected static function core_events(): array {
		return array(
			'user.course_enrolled'          => __( 'User enrolled course', 'learnpress' ),
			'course.enrolled'               => __( 'Course enrolled', 'learnpress' ),
			'order.created'                 => __( 'Order created', 'learnpress' ),
			'order.status_changed'          => __( 'Order status changed', 'learnpress' ),
			'order.pending_to_processing'   => __( 'Order pending to processing', 'learnpress' ),
			'order.pending_to_completed'    => __( 'Order pending to completed', 'learnpress' ),
			'order.processing'              => __( 'Order processing', 'learnpress' ),
			'order.completed'               => __( 'Order completed', 'learnpress' ),
			'order.cancelled'               => __( 'Order cancelled', 'learnpress' ),
			'order.failed'                  => __( 'Order failed', 'learnpress' ),
			'order.refunded'                => __( 'Order refunded', 'learnpress' ),
			'checkout.order_processed'      => __( 'Checkout order processed', 'learnpress' ),
			'course.submit_rejected'        => __( 'Course submission rejected', 'learnpress' ),
			'course.submit_approved'        => __( 'Course submission approved', 'learnpress' ),
			'course.submit_for_review'      => __( 'Course submitted for review', 'learnpress' ),
			'lesson.completed'              => __( 'Lesson completed', 'learnpress' ),
			'quiz.started'                  => __( 'Quiz started', 'learnpress' ),
			'quiz.finished'                 => __( 'Quiz finished', 'learnpress' ),
			'quiz.retried'                  => __( 'Quiz retried', 'learnpress' ),
			'course.finished'               => __( 'Course finished', 'learnpress' ),
			'user_item.created'             => __( 'User item created', 'learnpress' ),
			'user.password_reset_requested' => __( 'User password reset requested', 'learnpress' ),
			'instructor.requested'          => __( 'Instructor request submitted', 'learnpress' ),
			'instructor.accepted'           => __( 'Instructor request accepted', 'learnpress' ),
			'instructor.denied'             => __( 'Instructor request denied', 'learnpress' ),
		);
	}

	/**
	 * Return LearnPress Membership addon webhook events.
	 *
	 * @return array<string, string>
	 */
	protected static function membership_events(): array {
		return array(
			'membership.order_completed'        => __( 'Membership order completed', 'learnpress' ),
			'membership.subscription_renewed'   => __( 'Membership subscription renewed', 'learnpress' ),
			'membership.subscription_cancelled' => __( 'Membership subscription cancelled', 'learnpress' ),
			'membership.subscription_suspended' => __( 'Membership subscription suspended', 'learnpress' ),
			'membership.subscription_expired'   => __( 'Membership subscription expired', 'learnpress' ),
			'membership.payment_failed'         => __( 'Membership payment failed', 'learnpress' ),
			'membership.reminder_expiring'      => __( 'Membership reminder expiring', 'learnpress' ),
			'membership.course_resumed'         => __( 'Membership course resumed', 'learnpress' ),
		);
	}

	/**
	 * Return LearnPress Assignments addon webhook events.
	 *
	 * @return array<string, string>
	 */
	protected static function assignment_events(): array {
		return array(
			'assignment.started'      => __( 'Assignment started', 'learnpress' ),
			'assignment.submitted'    => __( 'Assignment submitted', 'learnpress' ),
			'assignment.evaluated'    => __( 'Assignment evaluated', 'learnpress' ),
			'assignment.re_evaluated' => __( 'Assignment re-evaluated', 'learnpress' ),
			'assignment.retried'      => __( 'Assignment retried', 'learnpress' ),
		);
	}

	/**
	 * Return LearnPress Announcements addon webhook events.
	 *
	 * @return array<string, string>
	 */
	protected static function announcement_events(): array {
		return array(
			'announcement.created'      => __( 'Announcement created', 'learnpress' ),
			'announcement.email_queued' => __( 'Announcement email queued', 'learnpress' ),
		);
	}

	/**
	 * Check whether a plugin basename is active.
	 *
	 * @param string $plugin_file Plugin basename.
	 *
	 * @return bool
	 */
	protected static function is_plugin_active( string $plugin_file ): bool {
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file ) ) {
			return true;
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( in_array( $plugin_file, $active_plugins, true ) ) {
			return true;
		}

		if ( is_multisite() ) {
			$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			if ( isset( $network_plugins[ $plugin_file ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Keep only registered event keys.
	 *
	 * @param array<int, mixed> $event_keys Raw event keys.
	 *
	 * @return array<int, string>
	 */
	public static function sanitize( array $event_keys ): array {
		$allowed = array_keys( self::all() );
		$valid   = array();

		foreach ( $event_keys as $event_key ) {
			$event_key = sanitize_text_field( (string) $event_key );
			if ( in_array( $event_key, $allowed, true ) ) {
				$valid[] = $event_key;
			}
		}

		return array_values( array_unique( $valid ) );
	}
}
