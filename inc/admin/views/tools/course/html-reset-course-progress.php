<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.1
 */

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\Tools\AdminCourseTools;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) or die();
?>

<div id="learn-press-reset-course-users" class="card lp-max-width-768">
	<h2><?php echo esc_html__( 'Reset User Course Progress', 'learnpress' ); ?></h2>
	<p><?php echo esc_html__( 'This action will reset course progress of users who have enrolled.', 'learnpress' ); ?></p>
	<?php
	echo sprintf(
		'<button type="button"
			data-template="#lp-tmpl-select-courses-to-reset-progress"
			data-message-resetting="%1$s"
			data-message-choose="%2$s"
			class="lp-button button lp-btn-show-popup-items-to-select lp-btn-choose-courses-to-reset-progress">
			%2$s
		</button>',
		esc_html__( 'Resetting courses progress', 'learnpress' ),
		esc_html__( 'Choose data to reset progress', 'learnpress' )
	); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$html_fields        = sprintf(
		'<div class="filter-field">
				<label>%s</label>
				<input
					class="lp-seach-course lp-filter-field"
					type="text" name="lp-search-course" placeholder="%s">
			</div>
			<div class="filter-field">
				<label>%s</label>
				<input
					class="lp-search-user lp-filter-field"
					type="text" name="lp-search-user" placeholder="%s">
			</div>',
		esc_html__( 'Course', 'learnpress' ),
		esc_attr__( 'Search course by title', 'learnpress' ),
		esc_html__( 'Student', 'learnpress' ),
		esc_attr__( 'Search student by name or email', 'learnpress' ),
	);
	$html_filter_fields = AdminTemplate::html_form_filter(
		[
			'fields' => $html_fields,
			'form_classes' => 'lp-form-filter-reset-course-progress',
		]
	);

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
		'popup'                    => AdminTemplate::html_popup_items_to_select_clone(
			$tabs,
			$html_items,
			$html_filter_fields,
			[
				'classes' => 'lp-popup-select-courses-to-reset-progress',
				'btn-add-label' => __( 'Reset data progress', 'learnpress' ),
				'btn-add-classes' => 'lp-btn-reset-courses-progress',
				'btn-add-attrs' => sprintf(
					'data-message-confirm="%s"',
					esc_html__( 'Are you sure you want to reset data progress choosed?', 'learnpress' )
				),
				'btn-custom' => sprintf(
					'<button type="button"
						data-message-confirm="%s"
						class="lp-button lp-btn-reset-all-courses-progress">
						%s
					</button>',
					esc_html__(
						'Are you sure you want to reset all data progress by filter?',
						'learnpress'
					),
					__( 'Reset all data progress', 'learnpress' )
				),
			]
		),
		'wrap-script-template-end' => '</script>',
	];

	echo Template::combine_components( $section ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
