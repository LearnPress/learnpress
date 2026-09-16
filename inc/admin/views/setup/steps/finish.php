<?php
/**
 * Template for displaying finish step.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$courses_page_id = learn_press_get_page_id( 'courses' );
$profile_page_id = learn_press_get_page_id( 'profile' );

$courses_page_url = $courses_page_id ? get_permalink( $courses_page_id ) : '';
$profile_page_url = $profile_page_id ? get_permalink( $profile_page_id ) : '';

$currency = learn_press_get_currency();
$settings = LP_Settings::instance();
if ( wp_is_block_theme() ) {
	$learning_experience = __( 'Managed by Gutenberg theme', 'learnpress' );
} else {
	$course_layout       = ucfirst( $settings->get( 'layout_single_course', 'modern' ) );
	$course_listing      = ucfirst( $settings->get( 'archive_courses_layout', 'grid' ) );
	$learning_experience = sprintf(
		/* translators: 1: single-course layout, 2: course-listing layout. */
		__( '%1$s Single / %2$s Listing', 'learnpress' ),
		$course_layout,
		$course_listing
	);
}

$enabled_gateways = array();
if ( 'yes' === $settings->get( 'offline-payment.enable', 'yes' ) ) {
	$enabled_gateways[] = __( 'Offline', 'learnpress' );
}
if ( 'yes' === $settings->get( 'paypal.enable', 'no' ) ) {
	$enabled_gateways[] = __( 'PayPal', 'learnpress' );
}
$payment_gateways = $enabled_gateways ? implode( ', ', $enabled_gateways ) : __( 'Disabled', 'learnpress' );

$summary_email_ids = array(
	'new-order-admin',
	'new-order-instructor',
	'completed-order-user',
	'enrolled-course-user',
	'finished-course-user',
	'become-an-instructor',
	'instructor-accepted',
);
$enabled_email_count = 0;
foreach ( $summary_email_ids as $email_id ) {
	$email = LP_Emails::get_email( $email_id );
	if ( $email && $email->enable() ) {
		++$enabled_email_count;
	}
}
$email_status = $enabled_email_count === count( $summary_email_ids )
	? __( 'Enabled', 'learnpress' )
	: ( $enabled_email_count ? __( 'Partially enabled', 'learnpress' ) : __( 'Disabled', 'learnpress' ) );
?>

<div class="lp-setup-finish">

	<div class="lp-setup-finish__hero">

		<div class="lp-setup-finish__icon" aria-hidden="true">
			<svg viewBox="0 0 32 32">
				<path d="M8 24L14 9l9 9L8 24Z"></path>
				<path d="M13 11l8 8"></path>
				<path d="M18 8l1-3"></path>
				<path d="M23 11l3-2"></path>
				<path d="M24 16h3"></path>
				<path d="M10 19l4 1"></path>
				<path d="M12 15l5 1"></path>
			</svg>
		</div>

		<h2>
			<?php esc_html_e( 'Setup Complete! Your Academy Is Ready', 'learnpress' ); ?>
		</h2>

		<p>
			<?php esc_html_e( 'Congratulations! LearnPress has been configured successfully.', 'learnpress' ); ?>
			<br>
			<?php esc_html_e( 'Here is a summary of your setup and your next steps.', 'learnpress' ); ?>
		</p>

	</div>

	<div class="lp-setup-finish__grid">

		<div class="lp-setup-finish-card lp-setup-card lp-setup-finish-summary">

			<div class="lp-setup-finish-card__heading">
				<span class="lp-setup-finish-card__heading-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
						<path d="M2.91699 7.27273V3.5C2.91699 2.94772 3.36471 2.5 3.91699 2.5H16.0837C16.6359 2.5 17.0837 2.94772 17.0837 3.5V7.27273M2.91699 7.27273V16.5C2.91699 17.0523 3.36471 17.5 3.91699 17.5H16.0837C16.6359 17.5 17.0837 17.0523 17.0837 16.5V7.27273M2.91699 7.27273H17.0837M14.5079 4.88636H12.2541H10.0003M5.17078 4.88636H5.49275M8.06851 4.88636H7.74654M11.2882 11.3636H14.5079M11.2882 13.4091H13.22M6.45866 10.6818L5.49729 11.6997C5.13331 12.0851 5.13331 12.6876 5.49729 13.073L6.45866 14.0909M8.06851 10.6818L9.02988 11.6997C9.39386 12.0851 9.39386 12.6876 9.02988 13.073L8.06851 14.0909" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>

				<strong>
					<?php esc_html_e( 'Configuration Summary', 'learnpress' ); ?>
				</strong>
			</div>

			<div class="lp-setup-finish-summary__list">
				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Learning Experience:', 'learnpress' ); ?></span>

					<strong>
						<?php echo esc_html( $learning_experience ); ?>
					</strong>
				</div>

				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Courses Page:', 'learnpress' ); ?></span>

					<?php if ( $courses_page_url ) : ?>
						<a
							href="<?php echo esc_url( $courses_page_url ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php esc_html_e( 'View Page', 'learnpress' ); ?>
						</a>
					<?php else : ?>
						<strong>—</strong>
					<?php endif; ?>
				</div>

				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Profile Page:', 'learnpress' ); ?></span>

					<?php if ( $profile_page_url ) : ?>
						<a
							href="<?php echo esc_url( $profile_page_url ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php esc_html_e( 'View Page', 'learnpress' ); ?>
						</a>
					<?php else : ?>
						<strong>—</strong>
					<?php endif; ?>
				</div>

				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Currency:', 'learnpress' ); ?></span>

					<strong>
						<?php echo esc_html( $currency ); ?>
					</strong>
				</div>

				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Payment Gateways:', 'learnpress' ); ?></span>

					<strong>
						<?php echo esc_html( $payment_gateways ); ?>
					</strong>
				</div>

				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Email Notifications:', 'learnpress' ); ?></span>

					<strong>
						<?php echo esc_html( $email_status ); ?>
					</strong>
				</div>

			</div>

		</div>

		<div class="lp-setup-finish-card lp-setup-card lp-setup-finish-next">

			<div class="lp-setup-finish-card__heading">
				<span class="lp-setup-finish-card__heading-icon lp-icon-file-text-o" aria-hidden="true"></span>

				<strong>
					<?php esc_html_e( 'What to do next?', 'learnpress' ); ?>
				</strong>
			</div>

			<div class="lp-setup-finish-next__actions">

				<a
					class="button button-primary lp-setup-finish__button"
					href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lp_course' ) ); ?>"
				>
					<?php esc_html_e( 'Create Your First Course', 'learnpress' ); ?>
				</a>

				<div
					class="lp-setup-demo-course"
					id="lp-setup-demo-course"
					data-state="idle"
					data-action="lp_setup_import_demo_course"
					data-importing-label="<?php esc_attr_e( 'Importing', 'learnpress' ); ?>"
					data-finish-label="<?php esc_attr_e( 'Finish & Go to Dashboard', 'learnpress' ); ?>"
				>

					<!-- IDLE -->
					<button
						type="button"
						class="button lp-setup-finish__button lp-setup-demo-course__install"
						id="install-sample-course"
					>
						<?php esc_html_e( 'Install Demo Course', 'learnpress' ); ?>
					</button>

					<!-- IMPORTING -->
					<div class="lp-setup-demo-course__progress">

						<div class="lp-setup-demo-course__progress-title">
							<span class="lp-setup-demo-course__spinner lp-icon-spinner" aria-hidden="true"></span>

							<strong>
								<?php esc_html_e( 'Installing Demo Course...', 'learnpress' ); ?>
							</strong>
						</div>

						<div class="lp-setup-demo-course__divider"></div>

						<div class="lp-setup-demo-course__status-row">
							<strong class="lp-setup-demo-course__status-text">
								<?php esc_html_e( 'Preparing demo courses…', 'learnpress' ); ?>
							</strong>

							<strong class="lp-setup-demo-course__percent">
								0%
							</strong>
						</div>

						<div class="lp-progress-bar lp-setup-demo-course__bar">
							<span style="width: 0;"></span>
						</div>

					</div>

					<!-- COMPLETE -->
					<div class="lp-setup-demo-course__complete">

						<div class="lp-setup-demo-course__complete-title">
							<span class="lp-icon-check-circle" aria-hidden="true"></span>

							<strong>
								<span class="lp-setup-demo-course__complete-count">0</span>
								<?php esc_html_e( 'Demo Courses Imported Successfully!', 'learnpress' ); ?>
							</strong>
						</div>

						<div class="lp-setup-demo-course__divider"></div>

						<div class="lp-setup-demo-course__status-row">
							<div>
								<strong>
									<?php esc_html_e( 'Done! Demo courses are ready.', 'learnpress' ); ?>
								</strong>

								<a
									href="<?php echo esc_url(
										admin_url( 'edit.php?post_type=lp_course' )
									); ?>"
								>
									<?php esc_html_e( 'View Courses', 'learnpress' ); ?>
									<span aria-hidden="true">→</span>
								</a>
							</div>

							<strong class="lp-setup-demo-course__percent">
								100%
							</strong>
						</div>

						<div class="lp-progress-bar lp-setup-demo-course__bar">
							<span style="width: 100%;"></span>
						</div>

					</div>

					<div class="learn-press-message error lp-setup-demo-course__error" role="alert">
						<strong><?php esc_html_e( 'Demo course installation failed.', 'learnpress' ); ?></strong>
						<span class="lp-setup-demo-course__error-message"></span>
						<button type="button" class="button-link lp-setup-demo-course__retry">
							<?php esc_html_e( 'Try again', 'learnpress' ); ?>
						</button>
					</div>

				</div>

				<a
					class="button lp-setup-finish__button"
					href="<?php echo esc_url(
						admin_url( 'admin.php?page=learn-press-settings' )
					); ?>"
				>
					<?php esc_html_e( 'Advanced Settings', 'learnpress' ); ?>
				</a>

				<a
					class="lp-setup-finish__documentation"
					href="<?php echo esc_url( LearnPress::$doc_link ); ?>"
					target="_blank"
					rel="noopener noreferrer"
				>
					<?php esc_html_e( 'Documentation', 'learnpress' ); ?>
				</a>

			</div>

		</div>

	</div>

</div>
