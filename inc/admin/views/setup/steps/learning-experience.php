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
							<path d="M16.3328 4.66667H24.4995M16.3328 10.5H24.4995M16.3328 17.5H24.4995M16.3328 23.3333H24.4995M4.66618 3.5H10.4995C11.1438 3.5 11.6662 4.02233 11.6662 4.66667V10.5C11.6662 11.1443 11.1438 11.6667 10.4995 11.6667H4.66618C4.02185 11.6667 3.49951 11.1443 3.49951 10.5V4.66667C3.49951 4.02233 4.02185 3.5 4.66618 3.5ZM4.66618 16.3333H10.4995C11.1438 16.3333 11.6662 16.8557 11.6662 17.5V23.3333C11.6662 23.9777 11.1438 24.5 10.4995 24.5H4.66618C4.02185 24.5 3.49951 23.9777 3.49951 23.3333V17.5C3.49951 16.8557 4.02185 16.3333 4.66618 16.3333Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
						<svg class="lp-setup-choice__svg--solid" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" focusable="false">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 15.1667C11.1188 15.1667 11.7123 15.4125 12.1499 15.8501C12.5875 16.2877 12.8333 16.8812 12.8333 17.5V22.1667C12.8333 22.7855 12.5875 23.379 12.1499 23.8166C11.7123 24.2542 11.1188 24.5 10.5 24.5H5.83333C5.21449 24.5 4.621 24.2542 4.18342 23.8166C3.74583 23.379 3.5 22.7855 3.5 22.1667V17.5C3.5 16.8812 3.74583 16.2877 4.18342 15.8501C4.621 15.4125 5.21449 15.1667 5.83333 15.1667H10.5ZM22.1667 15.1667C22.7553 15.1665 23.3223 15.3888 23.754 15.7891C24.1856 16.1894 24.45 16.738 24.4942 17.325L24.5 17.5V22.1667C24.5002 22.7553 24.2779 23.3223 23.8776 23.754C23.4773 24.1856 22.9287 24.45 22.3417 24.4942L22.1667 24.5H17.5C16.9113 24.5002 16.3443 24.2779 15.9127 23.8776C15.481 23.4773 15.2167 22.9287 15.1725 22.3417L15.1667 22.1667V17.5C15.1665 16.9113 15.3888 16.3443 15.7891 15.9127C16.1894 15.481 16.738 15.2167 17.325 15.1725L17.5 15.1667H22.1667ZM10.5 17.5H5.83333V22.1667H10.5V17.5ZM22.1667 17.5H17.5V22.1667H22.1667V17.5ZM22.1667 3.5C22.7553 3.49981 23.3223 3.72214 23.754 4.12241C24.1856 4.52269 24.45 5.07132 24.4942 5.65833L24.5 5.83333V10.5C24.5002 11.0887 24.2779 11.6557 23.8776 12.0873C23.4773 12.519 22.9287 12.7833 22.3417 12.8275L22.1667 12.8333H17.5C16.9113 12.8335 16.3443 12.6112 15.9127 12.2109C15.481 11.8106 15.2167 11.262 15.1725 10.675L15.1667 10.5V5.83333C15.1665 5.24466 15.3888 4.67767 15.7891 4.24603C16.1894 3.81438 16.738 3.54998 17.325 3.50583L17.5 3.5H22.1667ZM10.5 3.5C11.0887 3.49981 11.6557 3.72214 12.0873 4.12241C12.519 4.52269 12.7833 5.07132 12.8275 5.65833L12.8333 5.83333V10.5C12.8335 11.0887 12.6112 11.6557 12.2109 12.0873C11.8106 12.519 11.262 12.7833 10.675 12.8275L10.5 12.8333H5.83333C5.24466 12.8335 4.67767 12.6112 4.24603 12.2109C3.81438 11.8106 3.54998 11.262 3.50583 10.675L3.5 10.5V5.83333C3.49981 5.24466 3.72214 4.67767 4.12241 4.24603C4.52269 3.81438 5.07132 3.54998 5.65833 3.50583L5.83333 3.5H10.5ZM22.1667 5.83333H17.5V10.5H22.1667V5.83333ZM10.5 5.83333H5.83333V10.5H10.5V5.83333Z" fill="currentColor"/>
						</svg>
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
						<svg class="lp-setup-choice__svg--solid" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" focusable="false">
							<path d="M5.25 20.4168C5.71413 20.4168 6.15925 20.6012 6.48744 20.9294C6.81563 21.2576 7 21.7027 7 22.1668C7 22.631 6.81563 23.0761 6.48744 23.4043C6.15925 23.7325 5.71413 23.9168 5.25 23.9168C4.78587 23.9168 4.34075 23.7325 4.01256 23.4043C3.68437 23.0761 3.5 22.631 3.5 22.1668C3.5 21.7027 3.68437 21.2576 4.01256 20.9294C4.34075 20.6012 4.78587 20.4168 5.25 20.4168ZM23.3333 21.0002C23.6428 21.0002 23.9395 21.1231 24.1583 21.3419C24.3771 21.5607 24.5 21.8574 24.5 22.1668C24.5 22.4762 24.3771 22.773 24.1583 22.9918C23.9395 23.2106 23.6428 23.3335 23.3333 23.3335H10.5C10.1906 23.3335 9.89383 23.2106 9.67504 22.9918C9.45625 22.773 9.33333 22.4762 9.33333 22.1668C9.33333 21.8574 9.45625 21.5607 9.67504 21.3419C9.89383 21.1231 10.1906 21.0002 10.5 21.0002H23.3333ZM5.25 12.2502C5.71413 12.2502 6.15925 12.4345 6.48744 12.7627C6.81563 13.0909 7 13.536 7 14.0002C7 14.4643 6.81563 14.9094 6.48744 15.2376C6.15925 15.5658 5.71413 15.7502 5.25 15.7502C4.78587 15.7502 4.34075 15.5658 4.01256 15.2376C3.68437 14.9094 3.5 14.4643 3.5 14.0002C3.5 13.536 3.68437 13.0909 4.01256 12.7627C4.34075 12.4345 4.78587 12.2502 5.25 12.2502ZM23.3333 12.8335C23.6307 12.8338 23.9167 12.9477 24.1329 13.1518C24.3492 13.356 24.4793 13.6349 24.4967 13.9318C24.5141 14.2286 24.4175 14.5209 24.2267 14.749C24.0358 14.977 23.7651 15.1235 23.4698 15.1587L23.3333 15.1668H10.5C10.2026 15.1665 9.91663 15.0526 9.7004 14.8485C9.48418 14.6444 9.35406 14.3654 9.33663 14.0685C9.31921 13.7717 9.41579 13.4794 9.60664 13.2514C9.7975 13.0233 10.0682 12.8768 10.3635 12.8417L10.5 12.8335H23.3333ZM5.25 4.0835C5.71413 4.0835 6.15925 4.26787 6.48744 4.59606C6.81563 4.92425 7 5.36937 7 5.8335C7 6.29763 6.81563 6.74274 6.48744 7.07093C6.15925 7.39912 5.71413 7.5835 5.25 7.5835C4.78587 7.5835 4.34075 7.39912 4.01256 7.07093C3.68437 6.74274 3.5 6.29763 3.5 5.8335C3.5 5.36937 3.68437 4.92425 4.01256 4.59606C4.34075 4.26787 4.78587 4.0835 5.25 4.0835ZM23.3333 4.66683C23.6307 4.66716 23.9167 4.78102 24.1329 4.98515C24.3492 5.18928 24.4793 5.46827 24.4967 5.76512C24.5141 6.06197 24.4175 6.35427 24.2267 6.5823C24.0358 6.81033 23.7651 6.95688 23.4698 6.992L23.3333 7.00016H10.5C10.2026 6.99983 9.91663 6.88597 9.7004 6.68184C9.48418 6.47771 9.35406 6.19872 9.33663 5.90187C9.31921 5.60502 9.41579 5.31272 9.60664 5.08469C9.7975 4.85666 10.0682 4.71012 10.3635 4.675L10.5 4.66683H23.3333Z" fill="currentColor"/>
						</svg>
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
