<?php
/**
 * @author  leehld
 * @package LearnPress/Classes
 * @version 2.0.8
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
			echo '<p>' . __( 'Fonts', 'learnpress' ) . '</p>';
			printf(
				'<input type="text" class="rwmb-google-fonts" name="%s[families]" value="%s">',
				$field['id'],
				$meta['families']
			);
			echo '<p>' . __( 'Subset', 'learnpress' ) . '</p>';
			printf(
				'<input type="text" class="rwmb-google-fonts" name="%s[subsets]" value="%s">',
				$field['id'],
				$meta['subsets']
			);

			return ob_get_clean();
		}
	}
}