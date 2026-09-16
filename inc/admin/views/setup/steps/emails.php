<?php
/**
 * Template for displaying email notifications step.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

use LearnPress\TemplateHooks\Admin\AdminTemplate;

defined( 'ABSPATH' ) || exit;

$email_notifications = array(
	array(
		'name'        => __( 'New Order Email', 'learnpress' ),
		'description' => __( 'Sent to admin & instructor when a new order is placed.', 'learnpress' ),
		'key'         => 'new_order',
		'email_ids'   => array( 'new-order-admin', 'new-order-instructor' ),
	),
	array(
		'name'        => __( 'Order Completed Email', 'learnpress' ),
		'description' => __( 'Sent to student when payment is confirmed.', 'learnpress' ),
		'key'         => 'order_completed',
		'email_ids'   => array( 'completed-order-user' ),
	),
	array(
		'name'        => __( 'Course Enrolled Email', 'learnpress' ),
		'description' => __( 'Sent to student when enrolled into a course.', 'learnpress' ),
		'key'         => 'course_enrolled',
		'email_ids'   => array( 'enrolled-course-user' ),
	),
	array(
		'name'        => __( 'Course Completed Email', 'learnpress' ),
		'description' => __( 'Sent to student upon finishing a course.', 'learnpress' ),
		'key'         => 'course_completed',
		'email_ids'   => array( 'finished-course-user' ),
	),
	array(
		'name'        => __( 'Become Instructor Request', 'learnpress' ),
		'description' => __( 'Sent to admin when a user applies to become a teacher.', 'learnpress' ),
		'key'         => 'become_instructor_request',
		'email_ids'   => array( 'become-an-instructor' ),
	),
	array(
		'name'        => __( 'Become Instructor Accepted', 'learnpress' ),
		'description' => __( 'Sent to applicant when approved.', 'learnpress' ),
		'key'         => 'become_instructor_accepted',
		'email_ids'   => array( 'instructor-accepted' ),
	),
);

$enabled_notifications = 0;
foreach ( $email_notifications as &$email_notification ) {
	$email_notification['enabled'] = true;
	foreach ( $email_notification['email_ids'] as $email_id ) {
		$email = LP_Emails::get_email( $email_id );
		if ( ! $email || ! $email->enable() ) {
			$email_notification['enabled'] = false;
			break;
		}
	}

	if ( $email_notification['enabled'] ) {
		++$enabled_notifications;
	}
}
unset( $email_notification );

$has_enabled_notifications = $enabled_notifications === count( $email_notifications );
?>

<div class="lp-setup-emails">
	<div class="lp-setup-section-header">
		<h2><?php esc_html_e( 'Step 4: Email Notifications', 'learnpress' ); ?></h2>

		<p>
			<?php esc_html_e(
				'Configure automated email notifications sent to students and instructors.',
				'learnpress'
			); ?>
		</p>
	</div>

	<div class="lp-setup-emails__master lp-setup-card">
		<div class="lp-setup-emails__master-content">
			<span class="lp-setup-emails__mail-icon lp-icon-envelope-o" aria-hidden="true"></span>

			<strong>
				<?php esc_html_e( 'Enable All Email Notifications', 'learnpress' ); ?>
			</strong>
		</div>

		<span class="screen-reader-text"><?php esc_html_e( 'Enable all email notifications', 'learnpress' ); ?></span>
		<?php echo AdminTemplate::html_toggle_enable( array( 'name' => 'email_notifications_master', 'value' => $has_enabled_notifications, 'classes' => 'lp-setup-email-master' ) ); ?>
	</div>

	<div class="lp-setup-emails__table-wrap lp-setup-card">
		<table class="lp-setup-emails__table">
			<thead>
				<tr>
					<th>
						<?php esc_html_e( 'Email Notification Type', 'learnpress' ); ?>
					</th>

					<th>
						<?php esc_html_e( 'Description', 'learnpress' ); ?>
					</th>

					<th>
						<?php esc_html_e( 'Status', 'learnpress' ); ?>
					</th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ( $email_notifications as $email ) : ?>
					<tr>
						<td class="lp-setup-emails__name">
							<?php echo esc_html( $email['name'] ); ?>
						</td>

						<td class="lp-setup-emails__description">
							<?php echo esc_html( $email['description'] ); ?>
						</td>

						<td class="lp-setup-emails__status">
							<span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Enable %s', 'learnpress' ), $email['name'] ) ); ?></span>
							<?php echo AdminTemplate::html_toggle_enable( array( 'name' => sprintf( 'settings[emails][notifications][%s]', $email['key'] ), 'value' => $email['enabled'], 'classes' => 'lp-setup-email-notification' ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
