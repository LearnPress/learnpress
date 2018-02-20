<?php

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( !class_exists( 'RWMB_Question_Field' ) ) {
	class RWMB_Question_Field extends RWMB_Field {
		public static function add_actions() {
			// Do same actions as file field
			parent::add_actions();
			add_action( 'wp_ajax_lpr_load_question_settings', array( __CLASS__, 'load_question_settings' ) );
		}

		public static function load_question_settings() {
			$type        = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : null;
			$question_id = isset( $_REQUEST['question_id'] ) ? $_REQUEST['question_id'] : null;

			$options = array(
				'ID' => $question_id
			);

			$question = LP_Question::instance( $type, $options );
			$options  = $question->get( 'options' );
			if ( isset( $options['type'] ) && $options['type'] == $type ) {

			} else {
				unset( $options['answer'] );
				$question->set( 'options', $options );
			}

			$post_options = !empty( $_REQUEST['options'] ) ? $_REQUEST['options'] : null;
			if ( $type == 'single_choice' ) {
				$selected = - 1;
				if ( $post_options && $post_options['answer'] ) foreach ( $post_options['answer'] as $k => $option ) {
					if ( !empty( $option['is_true'] ) ) $selected = $k;
					$post_options['answer'][$k]['is_true'] = 0;
				}
				if ( $selected > - 1 ) {
					$post_options['answer'][$selected]['is_true'] = 1;
				}
			}
			if ( $post_options ) $question->set( 'options', $post_options );

			$question->admin_interface();
			die();
		}

		public static function save( $new, $old, $post_id, $field ) {
		}

		public static function html( $meta, $field ) {
			global $post;
			$post_id = $post->ID;
			ob_start();

			if ( $q = LP_Question_Factory::get_question( $post_id ) ) {
				$q->admin_interface();
			}
			return ob_get_clean();
		}
	}
}