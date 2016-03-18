<?php
/**
 * @Author: ducnvtt
 * @Date:   2016-03-16 15:13:02
 * @Last Modified by:   ducnvtt
 * @Last Modified time: 2016-03-17 09:49:22
 * button-switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'RWMB_Button_Switcher_Field' ) ) {

	class RWMB_Switcher_Button_Field extends RWMB_Field {

		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts() {
			wp_enqueue_style( 'rwmb-learnpress-switchbutton', RWMB_CSS_URL . 'switchbutton/switchery.min.css', array(), RWMB_VER );
			wp_enqueue_script( 'rwmb-learnpress-switchbutton', RWMB_JS_URL . 'switchbutton/switchery.min.js', array(), RWMB_VER, true );
		}

		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param array $field
		 *
		 * @return string
		 */
		static function html( $meta, $field ) {
			$field = wp_parse_args( $field, array(
					'name' => '',
					'desc' => '',
					'id'   => '',
					'type' => '',
					'off'  => '',
					'on'   => ''
				));
			return sprintf(
				'<p><small>%s</small>&nbsp;<input name="%s" id="%s" type="checkbox" class="rwmb-learnpress-switchbutton" %s />&nbsp;<small>%s</small></p>',
				$field['off'],
				$field['field_name'],
				$field['id'],
				$meta === 'on' ? 'checked' : '',
				$field['on']
			);
		}
	}

}
