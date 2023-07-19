<?php
/**
 * Template for displaying course instructor in primary-meta section.
 *
 * @version 4.0.1
 * @author  ThimPress
 * @package LearnPress/Templates
 */

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

defined( 'ABSPATH' ) || exit;

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}

$instructor = $course->get_instructor();
if ( ! $instructor ) {
	return;
}
?>

<div class="meta-item meta-item-instructor">
	<div class="meta-item__image">
		<?php echo wp_kses_post( $instructor->get_profile_picture() ); ?>
	</div>
	<div class="meta-item__value">
		<label><?php esc_html_e( 'Instructor', 'learnpress' ); ?></label>
		<div>
			<?php
			echo wp_kses_post(
				sprintf(
					'<a href="%s">%s</a>',
					$instructor->get_url_instructor(),
					SingleInstructorTemplate::instance()->html_display_name( $instructor )
				)
			);
			?>
		</div>
	</div>
</div>
