<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\Tools\AdminCourseTools;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) or die();

// Cannot delete id 'learn-press-reset-course-users' - xoa la khong dc dau
?>

<div id="learn-press-reset-course-users" class="card">
	<h2><?php echo esc_html__( 'Reset Course Progress', 'learnpress' ); ?></h2>
	<p><?php echo esc_html__( 'This action will reset course progress of all users who have enrolled.', 'learnpress' ); ?></p>
	<?php
	echo sprintf(
		'<button type="button"
			data-template="#lp-tmpl-select-courses-to-reset-progress"
			class="lp-button button lp-btn-show-popup-items-to-select lp-btn-choose-courses-to-reset-progress">
			%s
		</button>',
		esc_html__( 'Choose courses to reset progress', 'learnpress' )
	); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$html_items = TemplateAJAX::load_content_via_ajax(
		[
			'id_url'             => 'courses-to-reset-progress',
			'enableScrollToView' => false,
			'paged'              => 1,
		],
		[
			/* @use AdminCourseTools::render_courses_to_reset_progress */
			'class'  => AdminCourseTools::class,
			'method' => 'render_courses_to_reset_progress',
		]
	);

	$tabs = [
		LP_COURSE_CPT => __( 'Courses', 'learnpress' ),
	];

	$section = [
		'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-select-courses-to-reset-progress">',
		'popup'                    => AdminTemplate::html_popup_items_to_select_clone( $tabs, $html_items ),
		'wrap-script-template-end' => '</script>',
	];

	echo Template::combine_components( $section ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
