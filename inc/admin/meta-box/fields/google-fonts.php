<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Google_Fonts_Field' ) ) {
	/**
	 * Class RWMB_Yes_No_Field
	 */
	class RWMB_Google_Fonts_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param mixed $field
		 *
		 * @return string
		 */
		static function html( $meta, $field = '' ) {
			$meta = wp_parse_args(
				$meta,
				array( 'families' => '', 'subsets' => '' )
			);
			ob_start();
			echo '<label>' . __( 'Fonts', 'learnpress' ) . '</label>';
			printf(
				'<input type="text" class="rwmb-google-fonts" name="%s[families]" value="%s">',
				$field['id'],
				$meta['families']
			);
			printf(
				'<p class="description">%s</p>',
				__( 'Font families separated by |, eg: Open Sans|Roboto.', 'learnpress' )
			);
			echo '<label>' . __( 'Subset', 'learnpress' ) . '</label>';
			printf(
				'<input type="text" class="rwmb-google-fonts" name="%s[subsets]" value="%s">',
				$field['id'],
				$meta['subsets']
			);
			printf(
				'<p class="description">%s</p>',
				__( 'Font subsets separated by comma, eg: greek,latin.', 'learnpress' )
			);

			return ob_get_clean();
		}
	}
}