<?php

namespace LearnPress\MetaBox;
use LearnPress\Helpers\Template;

/**
 * LP_Meta_Box_Field
 *
 * @version 1.0.0
 * @since 4.2.3.1
 */
class LPMetaBoxField {
	const TEXT     = 'text';
	const NUMBER   = 'number';
	const CHECKBOX = 'checkbox';
	const SELECT   = 'select';

	/**
	 * Extra options of field.
	 *
	 * @var string $class
	 */
	public $extra = array();

	public static function render( string $type, string $name, array $extra = [], array $el_wrapper = [] ) {
		$content = '';
		$value   = $extra['value'] ?? ( $extra['default'] ?? '' );

		switch ( $type ) {
			case self::TEXT:
			case self::NUMBER:
				$content = sprintf(
					'<input type="%s" name="%s" id="%s" value="%s" placeholder="%s" />',
					esc_attr( $type ),
					esc_attr( $name ),
					esc_attr( $extra['id'] ?? '' ),
					esc_attr( $value ),
					esc_attr( $extra['placeholder'] ?? '' )
				);
				break;
			case self::CHECKBOX:
				$content = sprintf(
					'<input type="checkbox" name="%s" id="%s" value="1" %s />',
					esc_attr( $name ),
					esc_attr( $extra['id'] ?? '' ),
					checked( $value, 1, false ) ? 'checked' : ''
				);
				break;
			case self::SELECT:
				$select = [
					sprintf(
						'<select name="%s" id="%s">',
						esc_attr( $name ),
						esc_attr( $extra['id'] ?? '' )
					) => '</select>',
				];

				$options = '';
				foreach ( $extra['options'] ?? [] as $key => $value_option ) {
					if ( $value === $key ) {
						$options .= sprintf( '<option value="%s" selected>%s</option>', esc_attr( $key ), wp_kses_post( $value_option ) );
						continue;
					}
					$options .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), wp_kses_post( $value_option ) );
				}

				$content = Template::instance()->nest_elements( $select, $options );
				break;
			case apply_filters( 'learn-press/meta-box-field-type', 'custom' ):
				$content = apply_filters( 'learn-press/meta-box-field-content', '', $type, $name, $extra );
				break;
			default:
				echo 'Not support type';
				break;
		}

		echo Template::instance()->nest_elements( $el_wrapper, $content );
	}
}
