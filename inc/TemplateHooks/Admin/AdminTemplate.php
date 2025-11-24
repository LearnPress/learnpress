<?php
namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Template;

/**
 * Template Show list items to select in popup.
 *
 * @since 4.2.9
 * @version 1.0.1
 */
class AdminTemplate {
	/**
	 * HTML TinyMCE editor
	 *
	 * @param string $value
	 * @param string $id_name
	 * @param array $setting
	 *
	 * @return string
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public static function editor_tinymce( string $value, string $id_name, array $setting = [] ): string {
		$args = array_merge(
			[
				'media_buttons' => true,
				'editor_class'  => 'lp-editor-tinymce',
				'editor_height' => 210,
			],
			$setting
		);

		ob_start();
		wp_editor(
			$value,
			$id_name,
			$args
		);

		return ob_get_clean();
	}

	/**
	 * HTML for popup items to select.
	 *
	 * @param array $tabs [ [ key => label ] ].
	 * @param string $html_items
	 *
	 * @return string
	 */
	public static function html_popup_items_to_select_clone( array $tabs, string $html_items ): string {
		$html_tabs = '';
		$i         = 0;
		foreach ( $tabs as $key => $label ) {
			$tab_active = '';
			if ( $i === 0 ) {
				$tab_active = 'active';
				++$i;
			}

			$html_tabs .= sprintf(
				'<li data-type="%s" class="tab %s"><a href="#">%s</a></li>',
				$key,
				$tab_active,
				$label
			);
		}

		$section_header = [
			'wrap'     => '<div class="header">',
			'count'    => '<div class="header-count-items-selected lp-hidden"></div>',
			'tabs'     => sprintf(
				'<ul class="tabs">%s</ul>',
				$html_tabs
			),
			'wrap_end' => '</div>',
		];

		$section_main = [
			'wrap'                => '<div class="main">',
			'wrap_items'          => '<div class="list-items-wrap">',
			'search'              => sprintf(
				'<input class="%1$s" name="%1$s" type="text" placeholder="%2$s">',
				'lp-search-title-item',
				__( 'Type here to search for an item', 'learnpress' )
			),
			'list-items'          => $html_items,
			'wrap_items_end'      => '</div>',
			'list-items-selected' => '
				<ul class="list-items-selected lp-hidden">
					<li class="li-item-selected clone lp-hidden" data-id="" data-type="">
						<i class="dashicons dashicons-remove"></i>
						<div class="title-display"></div>
					</li>
				</ul>',
			'wrap_end'            => '</div>',
		];

		$section_footer = [
			'wrap'                 => '<div class="footer">',
			'btn-add'              => sprintf(
				'<button type="button" disabled="disabled" class="button lp-btn-add-items-selected lp-btn-edit-primary">%s</button>',
				__( 'Add', 'learnpress' )
			),
			'count-items-selected' => sprintf(
				'<button type="button" disabled="disabled" class="button lp-btn-count-items-selected">%s %s</button>',
				sprintf( __( 'Selected items', 'learnpress' ), 0 ),
				'<span class="count"></span>'
			),
			'btn-back'             => sprintf(
				'<button type="button" class="button lp-btn-back-to-select-items lp-hidden">%s</button>',
				__( 'Back', 'learnpress' )
			),
			'wrap_end'             => '</div>',
		];

		$section = [
			'wrap'     => '<div class="lp-popup-items-to-select">',
			'header'   => Template::combine_components( $section_header ),
			'main'     => Template::combine_components( $section_main ),
			'footer'   => Template::combine_components( $section_footer ),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for tom select.
	 *
	 * @param array $args {
	 *     Arguments.
	 *
	 *     @type array  $options    Options for select.
	 *     @type string $name       Name attribute for select.
	 *     @type string $class_name Class name for select.
	 * }
	 *
	 * @return string
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function html_tom_select( array $args = [] ): string {
		$html_options = '';

		$options       = $args['options'] ?? [];
		$name          = $args['name'] ?? '';
		$class_name    = $args['class_name'] ?? '';
		$default_value = $args['default_value'] ?? '';
		$multiple      = $args['multiple'] ?? false;
		$multiple      = $multiple ? 'multiple' : '';
		foreach ( $options as $key => $value ) {
			if ( is_array( $default_value ) ) {
				$selected = in_array( $key, $default_value, true ) ? 'selected' : '';
			} else {
				$selected = selected( $default_value, $key, false );
			}

			$html_options .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $selected, esc_html( $value ) );
		}

		$section = [
			'select'     => sprintf(
				'<select name="%s" class="%s lp-tom-select" %s>',
				esc_attr( $name ),
				esc_attr( $class_name ),
				$multiple
			),
			'options'    => $html_options,
			'select-end' => '</select>',
		];

		return Template::combine_components( $section );
	}
}
