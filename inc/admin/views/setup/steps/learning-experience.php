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
$course_listing = LP_Settings::get_option( 'archive_courses_layout', 'list' );
$auto_enroll    = LP_Settings::get_option( 'auto_enroll', 'yes' );
$disabled_class = $is_block_theme ? ' lp-setup-choice-group--disabled' : '';
?>
<section class="lp-setup-learning-experience">
	<header class="lp-setup-section-header">
		<h2><?php esc_html_e( 'Step 1: Learning Experience', 'learnpress' ); ?></h2>
		<p><?php esc_html_e( 'Choose a few basics for how your courses look and feel.', 'learnpress' ); ?></p>
	</header>

	<fieldset class="lp-setup-choice-group<?php echo esc_attr( $disabled_class ); ?>"<?php echo $is_block_theme ? ' aria-disabled="true"' : ''; ?>>
		<legend><?php esc_html_e( 'Layout single course', 'learnpress' ); ?></legend>
		<div class="lp-setup-choice-grid">
			<label class="lp-setup-choice">
				<input type="radio" name="settings[course][layout_single_course]" value="modern"
					<?php checked( $course_layout, 'modern' ); ?><?php disabled( $is_block_theme ); ?>>
				<span class="lp-setup-choice__card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false"><path d="M4 5h16v14H4zM4 9h16M9 9v10"/></svg>
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
				<span class="lp-setup-choice__card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false"><path d="M5 4h5v16H5zM14 8h5v12h-5z"/></svg>
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
				<span class="lp-setup-choice__card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
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
				<span class="lp-setup-choice__card">
					<span class="lp-setup-choice__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false"><path d="M9 6h11M9 12h11M9 18h11M4 6h1M4 12h1M4 18h1"/></svg>
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
			<span class="lp-setup-choice__card">
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
