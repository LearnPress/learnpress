<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0.0
 */

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\Tools\AdminCourseTools;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) or die();
?>

<div id="learn-press-reset-user-item" class="card lp-max-width-768">
	<h2><?php echo esc_html__( 'Reset Item Progress', 'learnpress' ); ?></h2>
	<p><?php echo esc_html__( 'This action will reset progress of a specific lesson, quiz, or other course item.', 'learnpress' ); ?></p>
	<?php
	echo sprintf(
		'<button type="button"
			data-template="#lp-tmpl-select-items-to-reset-progress"
			data-message-resetting="%1$s"
			data-message-choose="%2$s"
			class="lp-button button lp-btn-show-popup-items-to-select lp-btn-choose-item-to-reset-progress">
			%2$s
		</button>',
		esc_html__( 'Resetting item progress', 'learnpress' ),
		esc_html__( 'Choose data to reset progress', 'learnpress' )
	); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$item_types   = learn_press_course_get_support_item_types();
	$options_html = sprintf( '<option value="">%s</option>', esc_html__( 'All', 'learnpress' ) );
	foreach ( $item_types as $type_key => $type_label ) {
		$options_html .= sprintf(
			'<option value="%s">%s</option>',
			esc_attr( $type_key ),
			esc_html( $type_label )
		);
	}

	$html_fields = sprintf(
		'<div class="filter-field">
				<label>%1$s</label>
				<select
					class="lp-filter-item-type lp-filter-field"
					name="lp-item-type">
					%2$s
				</select>
			</div>
			<div class="filter-field">
				<label>%3$s</label>
				<input
					class="lp-search-item lp-filter-field"
					type="text" name="lp-search-item" placeholder="%4$s">
			</div>
			<div class="filter-field">
				<label>%5$s</label>
				<input
					class="lp-seach-course lp-filter-field"
					type="text" name="lp-search-course" placeholder="%6$s">
			</div>
			<div class="filter-field">
				<label>%7$s</label>
				<input
					class="lp-search-user lp-filter-field"
					type="text" name="lp-search-user" placeholder="%8$s">
			</div>',
		esc_html__( 'Item type', 'learnpress' ),
		$options_html,
		esc_html__( 'Item', 'learnpress' ),
		esc_attr__( 'Search item by title', 'learnpress' ),
		esc_html__( 'Course\'s Item', 'learnpress' ),
		esc_attr__( 'Search by title', 'learnpress' ),
		esc_html__( 'Student', 'learnpress' ),
		esc_attr__( 'Search by name or email', 'learnpress' ),
	);

	$html_filter_fields = AdminTemplate::html_form_filter(
		[
			'fields'       => $html_fields,
			'form_classes' => 'lp-form-filter-reset-item-progress',
		]
	);

	$html_items = TemplateAJAX::load_content_via_ajax(
		[
			'id_url'             => 'items-to-reset-progress',
			'enableScrollToView' => false,
			'paged'              => 1,
		],
		[
			/* @use AdminCourseTools::render_items_to_reset_progress */
			'class'  => AdminCourseTools::class,
			'method' => 'render_items_to_reset_progress',
		]
	);

	$tabs = [
		'items' => __( 'Items', 'learnpress' ),
	];

	$section = [
		'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-select-items-to-reset-progress">',
		'popup'                    => AdminTemplate::html_popup_items_to_select_clone(
			$tabs,
			$html_items,
			$html_filter_fields,
			[
				'classes'         => 'lp-popup-select-items-to-reset-progress',
				'btn-add-label'   => __( 'Reset data progress', 'learnpress' ),
				'btn-add-classes' => 'lp-btn-reset-items-progress',
				'btn-add-attrs'   => sprintf(
					'data-message-confirm="%s"',
					esc_html__( 'Are you sure you want to reset data progress choosed?', 'learnpress' )
				),
				'btn-custom'      => sprintf(
					'<button type="button"
						data-message-confirm="%s"
						class="lp-button lp-btn-reset-all-items-progress">
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
