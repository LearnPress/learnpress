<?php
/**
 * Template for displaying course instructor in primary-meta section.
 *
 * @version 4.0.2
 * @author  ThimPress
 * @package LearnPress/Templates
 */

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

defined( 'ABSPATH' ) || exit;

$courseModel = CourseModel::find( get_the_ID(), true );
if ( ! $courseModel ) {
	return;
}

$instructor = $courseModel->get_author_model();
if ( ! $instructor ) {
	return;
}
?>

<div class="meta-item meta-item-instructor">
	<div class="meta-item__image">
		<?php echo wp_kses_post( SingleInstructorTemplate::instance()->html_avatar( $instructor ) ); ?>
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
