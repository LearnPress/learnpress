<?php
namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Template;
use LP_Request;

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
				'default_editor' => 'tinymce',
				'media_buttons'  => true,
				'editor_class'   => 'lp-editor-tinymce',
				'editor_height'  => 210,
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

	/**
	 * Render a toggle switch (checkbox + track) for enabling or disabling a setting.
	 *
	 * @param array $data Toggle configuration. Accepts:
	 *                    - classes: CSS classes for the input.
	 *                    - name:    Input name attribute (default: 'lp_toggle_enable').
	 *                    - value:   Current value (truthy/falsy).
	 *
	 * @return string HTML markup for the toggle switch.
	 * @since 4.4.5
	 * @version 1.0.0
	 */
	public static function html_toggle_enable( array $data = [] ): string {
		$classes = $data['classes'] ?? '';
		$name    = $data['name'] ?? 'lp_toggle_enable';
		$value   = $data['value'] ?? 0;
		$checked = $value ? 'checked' : '';

		$section = [
			'wrapper'     => sprintf(
				'<label class="lp-toggle-enable %s">',
				$value ? 'is-enabled' : ''
			),
			'input'        => sprintf(
				'<input type="checkbox" class="lp-toggle-enable__input %s"
				name="%s"
				value="%s"
				data-info="%s"
				%s/>',
				esc_attr( $classes ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_attr( Template::convert_data_to_json( $data ) ),
				esc_attr( $checked )
			),
			'track'        => '<span class="lp-toggle-enable__track"></span>',
			'wrapper-end' => '</label>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML form filter
	 *
	 * @param array $data
	 * @return string
	 * @since 4.4.5
	 * @version 1.0.0
	 */
	public static function html_form_filter( array $data = [] ): string {
		$classes          = $data['classes'] ?? '';
		$id               = $data['id'] ?? '';
		$html_fields      = $data['fields'] ?? '';
		$html_btn_actions = $data['btn_actions'] ?? '';

		$sections = array(
			'wrap'   => sprintf(
				'<form class="lp-form-filter %s"%s>',
				esc_attr( $classes ),
				$id ? sprintf( ' id="%s"', esc_attr( $id ) ) : ''
			),
			'fields' => sprintf(
				'<div class="lp-form-filter__fields">%s</div>',
				$html_fields
			),
			'btn-actions'      => sprintf(
				'<div class="lp-form-filter__actions">%s</div>',
				$html_btn_actions
			),
			'wrap_end'     => '</form>',
		);

		return Template::combine_components( $sections );
	}

	/**
	 * Display content html by format WP Admin Screen
	 *
	 * @param array $data ['tabs' => [ 'tab' => 'name' ], 'content' => '', 'title' => '', 'id' => ''].
	 * @return string
	 * @since 4.4.5
	 * @version 1.0.0
	 */
	public static function html_on_wp_admin_screen( array $data = [] ): string {
		$tabs       = $data['tabs'] ?? [];
		$active_tab = LP_Request::get_param( 'tab' );
		if ( empty( $active_tab ) || ! isset( $tabs[ $active_tab ] ) ) {
			$tab_keys   = array_keys( $tabs );
			$active_tab = reset( $tab_keys );
		}
		$content = $data['content'] ?? '';
		$title   = $data['title'] ?? '';
		$id      = $data['id'] ?? '';

		$classes = array( 'wrap' );
		if ( $id ) {
			$classes[] = $id;
		}

		$html_tabs = '';
		if ( $tabs ) {
			foreach ( $tabs as $tab => $tab_title ) {
				$active_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';

				if ( $active_class ) {
					$html_tabs .= sprintf(
						'<span class="nav-tab%s">%s</span>',
						esc_attr( $active_class ),
						esc_html( $tab_title )
					);
				} else {
					$html_tabs .= sprintf(
						'<a class="nav-tab" href="?page=%s&tab=%s">%s</a>',
						esc_attr( $id ),
						esc_attr( $tab ),
						esc_html( $tab_title )
					);
				}
			}
		}

		$sections = array(
			'wrap'         => sprintf(
				'<div class="%s">',
				esc_attr( implode( ' ', $classes ) )
			),
			'heading'      => $title ? sprintf(
				'<h1 class="wp-heading-inline">%s</h1>',
				wp_kses_post( $title )
			) : '',
			'tabs'         => $tabs ? sprintf(
				'<h2 class="nav-tab-wrapper">%s</h2>',
				$html_tabs
			) : '',
			'wrap-content' => '<div class="lp-admin-tabs">',
			'content'      => $content,
			'wrap-content-end' => '</div>',
			'wrap-end'     => '</div>',
		);

		return Template::combine_components( $sections );
	}
}
