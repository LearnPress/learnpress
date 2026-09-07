<?php
/**
 * Template for displaying finish step.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$courses_page_id = (int) get_option( 'learn_press_courses_page_id' );
$profile_page_id = (int) get_option( 'learn_press_profile_page_id' );

$courses_page_url = $courses_page_id ? get_permalink( $courses_page_id ) : '';
$profile_page_url = $profile_page_id ? get_permalink( $profile_page_id ) : '';

$currency = learn_press_get_currency();

/**
 * Temporary summary values.
 *
 * Sau này nếu setup step trước lưu các giá trị khác,
 * có thể lấy trực tiếp từ settings.
 */
$learning_experience = __( 'Classic Single / List Listing', 'learnpress' );
$payment_gateways    = __( 'Offline, PayPal', 'learnpress' );
$email_status        = __( 'Enabled', 'learnpress' );
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

		<!-- Configuration Summary -->
		<div class="lp-setup-finish-card lp-setup-finish-summary">

			<div class="lp-setup-finish-card__heading">
				<span class="lp-setup-finish-card__heading-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24">
						<rect x="3" y="4" width="18" height="16" rx="2"></rect>
						<path d="M7 8h3"></path>
						<path d="M14 8h3"></path>
						<path d="M7 12h3"></path>
						<path d="M14 12h3"></path>
						<path d="M7 16h3"></path>
					</svg>
				</span>

				<strong>
					<?php esc_html_e( 'Configuration Summary', 'learnpress' ); ?>
				</strong>
			</div>

			<div class="lp-setup-finish-summary__list">

				<div class="lp-setup-finish-summary__item">
					<span><?php esc_html_e( 'Environment:', 'learnpress' ); ?></span>

					<strong>
						<?php esc_html_e( 'Passed checks & permalinks ready', 'learnpress' ); ?>
					</strong>
				</div>

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
						>
							<?php esc_html_e( 'View Page', 'learnpress' ); ?>

							<span aria-hidden="true">→</span>
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
						>
							<?php esc_html_e( 'View Page', 'learnpress' ); ?>

							<span aria-hidden="true">→</span>
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

		<!-- What to do next -->
		<div class="lp-setup-finish-card lp-setup-finish-next">

			<div class="lp-setup-finish-card__heading">
				<span class="lp-setup-finish-card__heading-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24">
						<path d="M6 3h9l4 4v14H6z"></path>
						<path d="M15 3v5h5"></path>
						<path d="M9 12h6"></path>
						<path d="M9 16h4"></path>
					</svg>
				</span>

				<strong>
					<?php esc_html_e( 'What to do next?', 'learnpress' ); ?>
				</strong>
			</div>

			<div class="lp-setup-finish-next__actions">

				<a
					class="lp-setup-finish__button lp-setup-finish__button--primary"
					href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lp_course' ) ); ?>"
				>
					<?php esc_html_e( 'Create Your First Course', 'learnpress' ); ?>
				</a>

				<div
					class="lp-setup-demo-course"
					id="lp-setup-demo-course"
					data-state="idle"
				>

					<!-- IDLE -->
					<button
						type="button"
						class="lp-setup-finish__button lp-setup-demo-course__install"
						id="install-sample-course"
					>
						<?php esc_html_e( 'Install Demo Course', 'learnpress' ); ?>
					</button>

					<!-- IMPORTING -->
					<div class="lp-setup-demo-course__progress">

						<div class="lp-setup-demo-course__progress-title">
							<span class="lp-setup-demo-course__spinner">
								<svg viewBox="0 0 24 24">
									<path d="M20 11a8 8 0 1 0-2.3 5.7"></path>
									<path d="M20 5v6h-6"></path>
								</svg>
							</span>

							<strong>
								<?php esc_html_e( 'Installing Demo Course...', 'learnpress' ); ?>
							</strong>
						</div>

						<div class="lp-setup-demo-course__divider"></div>

						<div class="lp-setup-demo-course__status-row">
							<strong class="lp-setup-demo-course__status-text">
								<?php esc_html_e(
									'Importing (5/6): Digital Marketing & SEO Strategy',
									'learnpress'
								); ?>
							</strong>

							<strong class="lp-setup-demo-course__percent">
								83%
							</strong>
						</div>

						<div class="lp-setup-demo-course__bar">
							<span style="width: 83%;"></span>
						</div>

					</div>

					<!-- COMPLETE -->
					<div class="lp-setup-demo-course__complete">

						<div class="lp-setup-demo-course__complete-title">
							<span>
								<svg viewBox="0 0 24 24">
									<circle cx="12" cy="12" r="9"></circle>
									<path d="m8 12 3 3 5-6"></path>
								</svg>
							</span>

							<strong>
								<?php esc_html_e(
									'6 Demo Courses Imported Successfully!',
									'learnpress'
								); ?>
							</strong>
						</div>

						<div class="lp-setup-demo-course__divider"></div>

						<div class="lp-setup-demo-course__status-row">
							<div>
								<strong>
									<?php esc_html_e(
										'Done! 6 Demo courses imported.',
										'learnpress'
									); ?>
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

						<div class="lp-setup-demo-course__bar">
							<span style="width: 100%;"></span>
						</div>

					</div>

				</div>

				<a
					class="lp-setup-finish__button"
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
				>
					<?php esc_html_e( 'Read LearnPress Documentation', 'learnpress' ); ?>
				</a>

			</div>

		</div>

	</div>

</div>