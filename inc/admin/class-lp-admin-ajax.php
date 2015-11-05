<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( !class_exists( 'LP_Admin_Ajax' ) ) {

	/**
	 * Class LP_Admin_Ajax
	 */
	class LP_Admin_Ajax {
		/**
		 * Add action ajax
		 */
		public static function init() {
			$ajaxEvents = array(
				'create_page'             => false,
				'add_question'            => false,
				'convert_question_type'		=> false,
				'update_quiz_question_state' => false,
				'update_editor_hidden'		=> false,
				'update_curriculum_section_state' => false,
				'quick_add_item'		=> false,
				/////////////
				'quick_add_lesson'        => false,
				'quick_add_quiz'          => false,
				'be_teacher'              => false,
				'custom_stats'            => false,
				'ignore_setting_up'       => false,
				'get_page_permalink'      => false,
				'dummy_image'             => false,
				'update_add_on_status'    => false,
				'plugin_install'          => false,
				'bundle_activate_add_ons' => false,
				'install_sample_data'     => false
			);
			foreach ( $ajaxEvents as $ajaxEvent => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );
				// enable for non-logged in users
				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );
				}
			}
		}

		static function quick_add_item(){
			$post_type = learn_press_get_request( 'type' );
			$post_title = learn_press_get_request( 'name' );
			$response = array();
			if( $post_type && $post_title ){
				$args = compact( 'post_title', 'post_type' );
				$args['post_status'] = 'publish';
				$item_id = wp_insert_post( $args );
				if( $item_id ){
					$item = get_post( $item_id );
					$response['post'] = $item;
					$response['html'] = sprintf('<li class="" data-id="%1$d" data-type="%2$s" data-text="%3$s">
						<label>
							<input type="checkbox" value="%1$d">
							<span class="lp-item-text">%3$s</span>
						</label>
					</li>', $item->ID, $item->post_type, $item->post_title );
				}
			}
			learn_press_send_json( $response );
		}

		static function update_editor_hidden(){
			if( $id = learn_press_get_request( 'course_id' ) ) {
				if ( learn_press_get_request( 'is_hidden' ) ) {
					update_post_meta( $id, '_lp_editor_hidden', 'yes' );
				}else{
					delete_post_meta( $id, '_lp_editor_hidden' );
				}
			}
			learn_press_send_json($_POST);
		}

		static function update_quiz_question_state(){
			$hidden = learn_press_get_request( 'hidden' );
			$post = learn_press_get_request( 'quiz_id' );
			update_post_meta( $post, '_admin_hidden_questions', $hidden );
			die();
		}

		static function update_curriculum_section_state(){
			$hidden = learn_press_get_request( 'hidden' );
			$post = learn_press_get_request( 'course_id' );
			update_post_meta( $post, '_admin_hidden_sections', $hidden );
			die();
		}

		/**
		 * Create a new page with the title passed via $_REQUEST
		 */
		public static function create_page() {
			$page_name = !empty( $_REQUEST['page_name'] ) ? $_REQUEST['page_name'] : '';
			$response  = array();
			if ( $page_name ) {
				$args    = array(
					'post_type'   => 'page',
					'post_title'  => $page_name,
					'post_status' => 'publish'
				);
				$page_id = wp_insert_post( $args );

				if ( $page_id ) {
					$response['page'] = get_page( $page_id );
					$html             = learn_press_pages_dropdown( '', '', array( 'echo' => false ) );
					preg_match_all( '!value=\"([0-9]+)\"!', $html, $matches );
					$response['positions'] = $matches[1];
					$response['html']      = '<a href="' . get_edit_post_link( $page_id ) . '" target="_blank">' . __( 'Edit Page', 'learn_press' ) . '</a>&nbsp;';
					$response['html'] .= '<a href="' . get_permalink( $page_id ) . '" target="_blank">' . __( 'View Page', 'learn_press' ) . '</a>';
				} else {
					$response['error'] = __( 'Error! Can not create page. Please try again!', 'learn_press' );
				}
			} else {
				$response['error'] = __( 'Page name is empty!', 'learn_press' );
			}
			learn_press_send_json( $response );
			die();
		}

		public static function add_question() {
			$id   = learn_press_get_request( 'id' );
			$type = learn_press_get_request( 'type' );
			$name = learn_press_get_request( 'name' );
			$response = array();
			if( !$id ){
				$id = wp_insert_post(
					array(
						'post_title' => $name,
						'post_type'	=> LP()->question_post_type,
						'post_status' => 'publish'
					)
				);
				if( $id ){
					add_post_meta( $id, '_lp_type', $type );
				}
			}
			if( $id ){
				ob_start();
				$question = LP_Question_Factory::get_question( $id );
				learn_press_admin_view( 'meta-boxes/quiz/question.php', array( 'question' => $question ) );
				$response['html'] = ob_get_clean();
			}else{

			}
			learn_press_send_json( $response );
			die();
		}

		public static function convert_question_type(){
			learn_press_debug($_POST);;
			if( ( $from = learn_press_get_request( 'from' ) ) && ( $to = learn_press_get_request( 'to' ) ) && $question_id = learn_press_get_request( 'question_id' ) ){
				do_action( 'learn_press_convert_question_type', $question_id, $from, $to );
				$question = LP_Question_Factory::get_question( $question_id );
				learn_press_send_json(
					$question->admin_interface( array( 'echo' => false ) )
				);
			}else{
				throw new Exception( __( 'Convert question type must be specify the id, source and destination type', 'learn_press' ) );
			}
			die();
		}

		/*******************************************************************************************************/

		/**
		 * Install sample data or dismiss the notice depending on user's option
		 */
		static function install_sample_data() {
			$yes      = !empty( $_REQUEST['yes'] ) ? $_REQUEST['yes'] : '';
			$response = array();
			if ( 'false' == $yes ) {
				set_transient( 'learn_press_install_sample_data', 'off', 12 * HOUR_IN_SECONDS );
				$response['hide_notice'] = true;
			} else {
				$result = learn_press_install_and_active_add_on( 'learnpress-import-export' );
				if ( 'activate' == $result['status'] ) {
					if ( !class_exists( 'LPR_Import' ) ) {
						$import_file_lib = WP_PLUGIN_DIR . "/learnpress-import-export/incs/lpr-import.php";
						if ( file_exists( $import_file_lib ) ) {
							include_once WP_PLUGIN_DIR . "/learnpress-import-export/incs/lpr-import.php";
						}
					}
					if ( !class_exists( 'LPR_Import' ) ) {
						$response['error'] = __( 'Import/Export addon not found', 'learn_press' );
					} else {
						$importer      = new LPR_Import();
						$import_source = LP_PLUGIN_PATH . '/dummy-data/learnpress-how-to-use-learnpress.xml';

						$upload_dir = wp_upload_dir();

						$copy = $upload_dir['path'] . '/learnpress-how-to-use-learnpress-copy.xml';
						@copy( $import_source, $copy );
						if ( file_exists( $copy ) ) {
							$result = $importer->import( $copy );
							if ( $result == 1 ) {
								$response['success']  = __( 'Import sample data successfully. The page will reload now!', 'learn_press' );
								$response['redirect'] = admin_url( 'edit.php?post_type=lpr_course' );
							} else {
								$response['error'] = __( 'Unknown error when importing sample data. Please try again!', 'learn_press' );
							}
						} else {
							$response['error'] = __( 'Dummy sample data not found. Please try again!', 'learn_press' );
						}
					}
				} else {
					$response['error'] = __( 'Unknown error when installing/activating Import/Export addon. Please try again!', 'learn_press' );
				}
			}
			learn_press_send_json( $response );
			die();
		}

		/**
		 * Activate a bundle of add-ons, if an add-on is not installed then install it first
		 */
		static function bundle_activate_add_ons() {
			global $learn_press_add_ons;
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			$response = array( 'addons' => array() );

			if ( !current_user_can( 'activate_plugins' ) ) {
				$response['error'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.', 'learn_press' );
			} else {

				$add_ons = $learn_press_add_ons['bundle_activate'];

				if ( $add_ons ) {
					foreach ( $add_ons as $slug ) {
						$response['addons'][$slug] = learn_press_install_and_active_add_on( $slug );
					}
				}
			}
			learn_press_send_json( $response );
		}

		/**
		 * Activate a bundle of add-ons, if an add-on is not installed then install it first
		 */
		static function bundle_activate_add_on() {
			$response = array();
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			if ( !current_user_can( 'activate_plugins' ) ) {
				$response['error'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.', 'learn_press' );
			} else {
				$slug            = !empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : null;
				$response[$slug] = learn_press_install_and_active_add_on( $slug );
			}
			learn_press_send_json( $response );
		}

		static function plugin_install() {
			$plugin_name = !empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			$response    = learn_press_install_add_on( $plugin_name );
			learn_press_send_json( $response );
			die();
		}

		static function update_add_on_status() {
			$plugin   = !empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			$t        = !empty( $_REQUEST['t'] ) ? $_REQUEST['t'] : '';
			$response = array();
			if ( !current_user_can( 'activate_plugins' ) ) {
				$response['error'] = __( 'You do not have sufficient permissions to deactivate plugins for this site.', 'learn_press' );
			}
			if ( $plugin && $t ) {
				if ( $t == 'activate' ) {
					activate_plugin( $plugin, false, is_network_admin() );
				} else {
					deactivate_plugins( $plugin, false, is_network_admin() );
				}
				$is_activate        = is_plugin_active( $plugin );
				$response['status'] = $is_activate ? 'activate' : 'deactivate';

			}
			wp_send_json( $response );
			die();
		}

		/**
		 * Output the image to browser with text and params passed via $_GET
		 */
		public static function dummy_image() {
			$text = !empty( $_REQUEST['text'] ) ? $_REQUEST['text'] : '';
			learn_press_text_image( $text, $_GET );
			die();
		}

		/**
		 * Get edit|view link of a page
		 */
		public static function get_page_permalink() {
			$page_id = !empty( $_REQUEST['page_id'] ) ? $_REQUEST['page_id'] : '';
			?>
			<a href="<?php echo get_edit_post_link( $page_id ); ?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' ); ?></a>
			<a href="<?php echo get_permalink( $page_id ); ?>" target="_blank"><?php _e( 'View Page', 'learn_press' ); ?></a>
			<?php
			die();
		}


		/**
		 *
		 */
		public function custom_stats() {
			$from      = !empty( $_REQUEST['from'] ) ? $_REQUEST['from'] : 0;
			$to        = !empty( $_REQUEST['to'] ) ? $_REQUEST['to'] : 0;
			$date_diff = strtotime( $to ) - strtotime( $from );
			if ( $date_diff <= 0 || $from == 0 || $to == 0 ) {
				die();
			}
			learn_press_process_chart( learn_press_get_chart_students( $to, 'days', floor( $date_diff / ( 60 * 60 * 24 ) ) + 1 ) );
			die();
		}

		/**
		 * Quick add lesson with only title
		 */
		public static function quick_add_lesson() {

			$lesson_title = $_POST['lesson_title'];

			$new_lesson = array(
				'post_title'  => wp_strip_all_tags( $lesson_title ),
				'post_type'   => LP()->lesson_post_type,
				'post_status' => 'publish'
			);

			wp_insert_post( $new_lesson );

			$args      = array(
				'numberposts' => 1,
				'post_type'   => LP()->lesson_post_type,
				'post_status' => 'publish'
			);
			$lesson    = wp_get_recent_posts( $args );
			$lesson_id = $lesson[0]['ID'];
			$data      = array(
				'id'    => $lesson_id,
				'title' => $lesson_title
			);
			wp_send_json( $data );
			die;
		}

		/**
		 * Add a new quiz with the title only
		 */
		public static function quick_add_quiz() {
			$quiz_title = $_POST['quiz_title'];

			$new_quiz = array(
				'post_title'  => wp_strip_all_tags( $quiz_title ),
				'post_type'   => LP()->quiz_post_type,
				'post_status' => 'publish'
			);

			wp_insert_post( $new_quiz );

			$args    = array(
				'numberposts' => 1,
				'post_type'   => LP()->quiz_post_type,
				'post_status' => 'publish'
			);
			$quiz    = wp_get_recent_posts( $args );
			$quiz_id = $quiz[0]['ID'];
			$data    = array(
				'id'    => $quiz_id,
				'title' => $quiz_title
			);
			wp_send_json( $data );
			die;
		}

		public static function be_teacher() {
			$user_id    = get_current_user_id();
			$be_teacher = new WP_User( $user_id );
			$be_teacher->set_role( 'lpr_teacher' );
			die;
		}

		public static function ignore_setting_up() {
			update_option( '_lpr_ignore_setting_up', 1, true );
			die;
		}
	}
}
LP_Admin_Ajax::init();