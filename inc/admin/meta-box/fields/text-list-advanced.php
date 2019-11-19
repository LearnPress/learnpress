<?php
/**
 * The text list field which allows users to enter multiple texts.
 *
 * @package Meta Box
 */

/**
 * Text list field class.
 */
class RWMB_Text_List_Advanced_Field extends RWMB_Text_List_Field {

	/**
	 * Get field HTML.
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function html( $meta, $field ) {
		if ( empty( $field['options'] ) ) {
			return '';
		}
		$html  = array();
		$input = '<label><span class="rwmb-text-list-label">%s</span> <textarea class="rwmb-text-list" name="%s" placeholder="%s">%s</textarea></label>';

		$count = 0;
		foreach ( $field['options'] as $placeholder => $label ) {
			$html[] = sprintf(
				$input,
				$label,
				$field['field_name'],
				$placeholder,
				isset( $meta[ $count ] ) ? esc_html( $meta[ $count ] ) : ''
			);
			$count ++;
		}

		return implode( ' ', $html );
	}
}
