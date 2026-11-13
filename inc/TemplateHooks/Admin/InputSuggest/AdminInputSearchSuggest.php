<?php

namespace LearnPress\TemplateHooks\Admin\InputSuggest;

use LearnPress\Helpers\Template;
use LP_Helper;

/**
 * Base of the admin search-suggest input.
 *
 * Holds the HTML structure only: text input (type to search), hidden input (ID of the selected item)
 * and the result box. Child classes supply `html_result` (output of TemplateAJAX::load_content_via_ajax)
 * and the AJAX callback that renders the list.
 *
 * Does not use the Singleton trait: its static $instance would be shared by every child class.
 *
 * @since 4.4.14
 * @version 1.0.0
 */
class AdminInputSearchSuggest {
	/**
	 * Script handle, registered in LP_Admin_Assets.
	 */
	const SCRIPT_HANDLE = 'lp-input-search-suggest';

	/**
	 * Max items of the suggest list.
	 */
	const LIMIT = 5;

	/**
	 * Value of `type_select` to select many items (checkbox on each item, hidden value like 1,2,3).
	 */
	const TYPE_SELECT_MULTIPLE = 'multiple';

	/**
	 * HTML of the search-suggest input.
	 *
	 * Call it while rendering a full page: the script is enqueued here,
	 * so the HTML returned via AJAX has no JS unless the page already loads the script.
	 *
	 * @param array $data [
	 *  'id'          => id of the text input (for a label),
	 *  'name'        => name of the hidden input (stores the selected ID),
	 *  'value'       => selected ID (multiple: IDs separated by comma, default is the IDs of 'items_selected'),
	 *  'text'        => label of the selected item,
	 *  'type_select' => 'multiple' to select many items, default is single,
	 *  'items_selected' => multiple only: items selected [ [ 'id', 'text', 'label' ] ], listed (checked) when the input is focused,
	 *  'placeholder' => placeholder of the text input,
	 *  'class'       => extra class of the wrapper,
	 *  'html_result' => html of the result box, set by child classes only, never from request,
	 * ]
	 *
	 * @return string
	 */
	public function layout( array $data = [] ): string {
		wp_enqueue_script( self::SCRIPT_HANDLE );

		$is_multiple = self::get_type_select( $data ) === self::TYPE_SELECT_MULTIPLE;
		if ( $is_multiple && ! isset( $data['value'] ) ) {
			$data['value'] = implode( ',', array_column( (array) ( $data['items_selected'] ?? [] ), 'id' ) );
		}

		$section = [
			'wrap'         => sprintf(
				'<div class="lp-wrap-input-search-suggest %s"%s>',
				esc_attr( $data['class-wrap'] ?? '' ),
				$is_multiple ? sprintf( ' data-type-select="%s"', esc_attr( self::TYPE_SELECT_MULTIPLE ) ) : ''
			),
			'input_search' => sprintf(
				'<input type="text"
					%s
					class="lp-input-search-suggest"
					autocomplete="off"
					value="%s"
					placeholder="%s">',
				! empty( $data['id'] ) ? sprintf( ' id="%s"', esc_attr( $data['id'] ) ) : '',
				esc_attr( $data['text'] ?? '' ),
				esc_attr( $data['placeholder'] ?? '' )
			),
			'input_value'  => sprintf(
				'<input type="hidden"
					class="lp-input-search-suggest-value"
					name="%s"
					value="%s">',
				esc_attr( $data['name'] ?? '' ),
				esc_attr( $data['value'] ?? '' )
			),
			'result'       => sprintf(
				'<div class="lp-search-suggest-result%s">%s</div>',
				// Multiple: the list of selected items is printed, hidden until the input is focused.
				$is_multiple ? ' lp-hidden' : '',
				$data['html_result'] ?? ''
			),
			'wrap_end'     => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML of the suggest list, returned by the AJAX callbacks of child classes.
	 *
	 * @param array $items [ [ 'id' => int, 'text' => value set to the text input when selected, 'label' => text shown, default is 'text', 'checked' => bool, multiple only ] ]
	 * @param array $args [ 'type_select' => 'multiple': each item has a checkbox and the list has the button "Clear all", default is single ]
	 *
	 * @return string
	 */
	public static function html_list_result( array $items = [], array $args = [] ): string {
		$section     = [ 'wrap' => '<ul class="lp-search-suggest-list">' ];
		$is_multiple = self::get_type_select( $args ) === self::TYPE_SELECT_MULTIPLE;

		// Multiple: button to unselect all items, JS hides it when no item is selected.
		if ( $is_multiple ) {
			$section['actions'] = sprintf(
				'<li class="lp-search-suggest-actions">
					<button type="button" class="lp-search-suggest-clear-all">%s</button>
				</li>',
				esc_html__( 'Clear all', 'learnpress' )
			);
		}

		foreach ( $items as $key => $item ) {
			$text = (string) ( $item['text'] ?? '' );

			if ( $is_multiple ) {
				$checked                   = ! empty( $item['checked'] );
				$section[ 'item-' . $key ] = sprintf(
					'<li class="lp-search-suggest-item lp-search-suggest-item--multiple%s"
						data-id="%s"
						data-text="%s"
						data-type-select="%s">
						<input type="checkbox"
							class="lp-search-suggest-checkbox"
							tabindex="-1"
							value="%s"%s>%s (#%s)
					</li>',
					$checked ? ' is-selected' : '',
					esc_attr( $item['id'] ?? 0 ),
					esc_attr( $text ),
					esc_attr( self::TYPE_SELECT_MULTIPLE ),
					esc_attr( $item['id'] ?? 0 ),
					$checked ? ' checked' : '',
					esc_html( $item['label'] ?? $text ),
					esc_html( $item['id'] ?? 0 )
				);
				continue;
			}

			$section[ 'item-' . $key ] = sprintf(
				'<li class="lp-search-suggest-item"
					data-id="%s"
					data-text="%s">%s (#%s)
				</li>',
				esc_attr( $item['id'] ?? 0 ),
				esc_attr( $text ),
				esc_html( $item['label'] ?? $text ),
				esc_html( $item['id'] ?? 0 )
			);
		}

		if ( empty( $items ) ) {
			$section['empty'] = sprintf(
				'<li class="lp-search-suggest-empty">%s</li>',
				esc_html__( 'No results', 'learnpress' )
			);
		}

		$section['wrap_end'] = '</ul>';

		return Template::combine_components( $section );
	}

	/**
	 * Get the type select from the data of layout(), the args of html_list_result() or the data sent via AJAX.
	 *
	 * @param array $data
	 *
	 * @return string 'multiple' or '' (single), other values are ignored.
	 */
	protected static function get_type_select( array $data ): string {
		return ( $data['type_select'] ?? '' ) === self::TYPE_SELECT_MULTIPLE ? self::TYPE_SELECT_MULTIPLE : '';
	}

	/**
	 * Add the args of the multiple select to the args of TemplateAJAX::load_content_via_ajax().
	 *
	 * @param array $args args of TemplateAJAX::load_content_via_ajax()
	 * @param array $data data of layout()
	 *
	 * @return array
	 */
	protected static function args_ajax_type_select( array $args, array $data ): array {
		if ( self::get_type_select( $data ) !== self::TYPE_SELECT_MULTIPLE ) {
			return $args;
		}

		// Sent via AJAX, the callback renders the items with checkbox.
		$args['type_select'] = self::TYPE_SELECT_MULTIPLE;

		// Items selected: printed in the target, JS lists them when the input is focused.
		$items_selected = [];
		foreach ( (array) ( $data['items_selected'] ?? [] ) as $item ) {
			if ( is_array( $item ) ) {
				$item['checked']  = true;
				$items_selected[] = $item;
			}
		}

		if ( ! empty( $items_selected ) ) {
			$args['html_no_load_ajax_first'] = self::html_list_result(
				$items_selected,
				[ 'type_select' => self::TYPE_SELECT_MULTIPLE ]
			);
		}

		return $args;
	}

	/**
	 * Get the keyword from the data sent via AJAX.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	protected static function get_search( array $data ): string {
		$search = $data['search'] ?? '';
		if ( ! is_string( $search ) ) {
			return '';
		}

		return trim( (string) LP_Helper::sanitize_params_submitted( $search ) );
	}
}
