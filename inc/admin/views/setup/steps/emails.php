<?php
/**
 * Template for displaying email notifications step.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$email_notifications = array(
	array(
		'name'        => __( 'New Order Email', 'learnpress' ),
		'description' => __( 'Sent to admin & instructor when a new order is placed.', 'learnpress' ),
		'key'         => 'new_order',
		'enabled'     => true,
	),
	array(
		'name'        => __( 'Order Completed Email', 'learnpress' ),
		'description' => __( 'Sent to student when payment is confirmed.', 'learnpress' ),
		'key'         => 'order_completed',
		'enabled'     => true,
	),
	array(
		'name'        => __( 'Course Enrolled Email', 'learnpress' ),
		'description' => __( 'Sent to student when enrolled into a course.', 'learnpress' ),
		'key'         => 'course_enrolled',
		'enabled'     => true,
	),
	array(
		'name'        => __( 'Course Completed Email', 'learnpress' ),
		'description' => __( 'Sent to student upon finishing a course.', 'learnpress' ),
		'key'         => 'course_completed',
		'enabled'     => true,
	),
	array(
		'name'        => __( 'Become Instructor Request', 'learnpress' ),
		'description' => __( 'Sent to admin when a user applies to become a teacher.', 'learnpress' ),
		'key'         => 'become_instructor_request',
		'enabled'     => false,
	),
	array(
		'name'        => __( 'Become Instructor Accepted', 'learnpress' ),
		'description' => __( 'Sent to applicant when approved.', 'learnpress' ),
		'key'         => 'become_instructor_accepted',
		'enabled'     => false,
	),
);
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

	<div class="lp-setup-emails__master">
		<div class="lp-setup-emails__master-content">
			<span class="lp-setup-emails__mail-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24">
					<rect x="3" y="5" width="18" height="14" rx="2"></rect>
					<path d="M4 7l8 6 8-6"></path>
				</svg>
			</span>

			<strong>
				<?php esc_html_e( 'Enable All Email Notifications', 'learnpress' ); ?>
			</strong>
		</div>

		<label class="lp-setup-switch">
			<input
				type="checkbox"
				name="settings[emails][enable]"
				value="yes"
				checked
			>

			<span class="lp-setup-switch__control"></span>
		</label>
	</div>

	<div class="lp-setup-emails__table-wrap">
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
							<label class="lp-setup-switch">
								<input
									type="checkbox"
									name="settings[emails][<?php echo esc_attr( $email['key'] ); ?>]"
									value="yes"
									<?php checked( $email['enabled'] ); ?>
								>

								<span class="lp-setup-switch__control"></span>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>