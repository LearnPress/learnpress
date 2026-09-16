<?php
/**
 * Template for displaying the Learning Experience step of the setup wizard.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.3.2
 */

use LearnPress\TemplateHooks\Admin\AdminTemplate;

defined( 'ABSPATH' ) || exit;

$is_block_theme = wp_is_block_theme();
$course_layout  = LP_Settings::get_option( 'layout_single_course', 'modern' );
$course_listing = LP_Settings::get_option( 'archive_courses_layout', 'grid' );
$auto_enroll    = LP_Settings::get_option( 'auto_enroll', 'yes' );
$disabled_class = $is_block_theme ? ' lp-setup-choice-group--disabled' : '';
?>
<section class="lp-setup-learning-experience">
	<header class="lp-setup-section-header">
		<h2><?php esc_html_e( 'Step 1: Learning Experience', 'learnpress' ); ?></h2>
		<p><?php esc_html_e( 'Choose a few basics for how your courses look and feel.', 'learnpress' ); ?></p>
	</header>

	<?php if ( $is_block_theme ) : ?>
		<div class="lp-setup-info" role="status">
			<span class="lp-setup-info__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
					<circle cx="12" cy="12" r="10" fill="currentColor" stroke="none"/>
					<path d="M12 10.75V17M12 7.25V7.5" stroke="white" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</span>
			<span class="lp-setup-info__content">
				<strong><?php esc_html_e( 'Your current theme supports Gutenberg.', 'learnpress' ); ?></strong>
				<span><?php esc_html_e( 'These settings are not available because they don’t apply to your theme.', 'learnpress' ); ?></span>
			</span>
		</div>
	<?php endif; ?>

	<fieldset class="lp-setup-choice-group<?php echo esc_attr( $disabled_class ); ?>"<?php echo $is_block_theme ? ' aria-disabled="true"' : ''; ?>>
		<legend><?php esc_html_e( 'Layout single course', 'learnpress' ); ?></legend>
		<div class="lp-setup-choice-grid">
			<label class="lp-setup-choice">
				<input type="radio" name="settings[course][layout_single_course]" value="modern"
					<?php checked( $course_layout, 'modern' ); ?><?php disabled( $is_block_theme ); ?>>
				<span class="lp-setup-choice__card lp-setup-card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none">
							<path d="M5.83333 3.5H22.1667C23.4553 3.5 24.5 4.54467 24.5 5.83333V22.1667C24.5 23.4553 23.4553 24.5 22.1667 24.5H5.83333C4.54467 24.5 3.5 23.4553 3.5 22.1667V5.83333C3.5 4.54467 4.54467 3.5 5.83333 3.5ZM10.5 3.5V24.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<span class="lp-setup-choice__content">
						<strong><?php esc_html_e( 'Modern', 'learnpress' ); ?></strong>
						<span><?php esc_html_e( 'Clean and focused course experience.', 'learnpress' ); ?></span>
					</span>
				</span>
			</label>

			<label class="lp-setup-choice">
				<input type="radio" name="settings[course][layout_single_course]" value="classic"
					<?php checked( $course_layout, 'classic' ); ?><?php disabled( $is_block_theme ); ?>>
				<span class="lp-setup-choice__card lp-setup-card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none">
							<path d="M16.3328 4.66667H24.4995M16.3328 10.5H24.4995M16.3328 17.5H24.4995M16.3328 23.3333H24.4995M4.66618 3.5H10.4995C11.1438 3.5 11.6662 4.02233 11.6662 4.66667V10.5C11.6662 11.1443 11.1438 11.6667 10.4995 11.6667H4.66618C4.02185 11.6667 3.49951 11.1443 3.49951 10.5V4.66667C3.49951 4.02233 4.02185 3.5 4.66618 3.5ZM4.66618 16.3333H10.4995C11.1438 16.3333 11.6662 16.8557 11.6662 17.5V23.3333C11.6662 23.9777 11.1438 24.5 10.4995 24.5H4.66618C4.02185 24.5 3.49951 23.9777 3.49951 23.3333V17.5C3.49951 16.8557 4.02185 16.3333 4.66618 16.3333Z" stroke="#626262" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
					<span class="lp-setup-choice__content">
						<strong><?php esc_html_e( 'Classic', 'learnpress' ); ?></strong>
						<span><?php esc_html_e( 'A traditional course layout.', 'learnpress' ); ?></span>
					</span>
				</span>
			</label>
		</div>
	</fieldset>

	<fieldset class="lp-setup-choice-group<?php echo esc_attr( $disabled_class ); ?>"<?php echo $is_block_theme ? ' aria-disabled="true"' : ''; ?>>
		<legend><?php esc_html_e( 'Course Listing', 'learnpress' ); ?></legend>
		<div class="lp-setup-choice-grid">
			<label class="lp-setup-choice">
				<input type="radio" name="settings[course][archive_courses_layout]" value="grid"
					<?php checked( $course_listing, 'grid' ); ?><?php disabled( $is_block_theme ); ?>>
				<span class="lp-setup-choice__card lp-setup-card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<span class="lp-icon-th"></span>
					</span>
					<span class="lp-setup-choice__content">
						<strong><?php esc_html_e( 'Grid', 'learnpress' ); ?></strong>
						<span><?php esc_html_e( 'Show courses in a visual grid.', 'learnpress' ); ?></span>
					</span>
				</span>
			</label>

			<label class="lp-setup-choice">
				<input type="radio" name="settings[course][archive_courses_layout]" value="list"
					<?php checked( $course_listing, 'list' ); ?><?php disabled( $is_block_theme ); ?>>
				<span class="lp-setup-choice__card lp-setup-card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<span class="lp-icon-th-list"></span>
					</span>
					<span class="lp-setup-choice__content">
						<strong><?php esc_html_e( 'List', 'learnpress' ); ?></strong>
						<span><?php esc_html_e( 'Show courses in a simple list.', 'learnpress' ); ?></span>
					</span>
				</span>
			</label>
		</div>
	</fieldset>

	<fieldset class="lp-setup-choice-group lp-setup-choice-group--enrollment">
		<legend><?php esc_html_e( 'Course Enrollment', 'learnpress' ); ?></legend>
		<div class="lp-setup-choice lp-setup-choice--wide">
			<span class="lp-setup-choice__card lp-setup-card">
				<?php echo AdminTemplate::html_toggle_enable( array( 'name' => 'settings[course][auto_enroll]', 'value' => 'yes' === $auto_enroll, 'classes' => 'lp-setup-auto-enroll' ) ); ?>
				<span class="lp-setup-choice__content">
					<strong><?php esc_html_e( 'Start courses automatically after purchase', 'learnpress' ); ?></strong>
					<span><?php esc_html_e( 'Students can start learning right away.', 'learnpress' ); ?></span>
				</span>
			</span>
		</div>
	</fieldset>

	<p class="lp-setup-learning-experience__note">
		<?php esc_html_e( 'Don’t worry, you can always change these settings later! 😊', 'learnpress' ); ?>
	</p>
</section>
