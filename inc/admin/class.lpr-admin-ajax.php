<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LPR_Admin_Ajax' ) ) {

	/**
	 * Class LPR_Admin_Ajax
	 */
	class LPR_Admin_Ajax {
		/**
		 * Add action ajax
		 */
		public static function init(){
			$ajaxEvents = array(
				'quick_add_lesson'      => false,
				'quick_add_quiz'        => false,
				'be_teacher'            => false,
				'custom_stats'          => false,
				'ignore_setting_up'     => false,
                'create_page'           => false,
                'get_page_permalink'    => false,
                'dummy_image'           => false,
                'update_add_on_status'  => false,
                'plugin_install'        => false
			);
			foreach ( $ajaxEvents as $ajaxEvent => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );

                // enable for non-logged in users
				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajaxEvent, array( __CLASS__, $ajaxEvent ) );
				}
			}
		}

        static function plugin_install(){
            require_once( LPR_PLUGIN_PATH . '/inc/admin/class-lpr-upgrader.php' );
            $upgrader = new LPR_Upgrader();
            global $wp_filesystem;
            $response = array();
            $plugin_name = ! empty( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';

            $package = 'http://thimpress.com/lprepo/' . $plugin_name . '.zip';

            $package = $upgrader->download_package( $package );
            if( is_wp_error( $package ) ) {
                $response['error'] = $package;
            }else {
                $working_dir = $upgrader->unpack_package($package, true, $plugin_name);
                if (is_wp_error($working_dir)){
                    $response['error'] = $working_dir;
                }else {

                    $wp_upgrader = new WP_Upgrader();
                    $options = array(
                        'source' => $working_dir,
                        'destination' => WP_PLUGIN_DIR,
                        'clear_destination' => false, // Do not overwrite files.
                        'clear_working' => true,
                        'hook_extra' => array(
                            'type' => 'plugin',
                            'action' => 'install'
                        )
                    );
                    //$response = array();
                    $result = $wp_upgrader->install_package($options);

                    if (is_wp_error($result)) {
                        $response['error'] = $result;
                    }else{
                        $response = $result;
                        $response['text'] = __( 'Installed' );
                    }
                }
            }
            learn_press_send_json( $response );
            die();
        }
        static function update_add_on_status()
        {
            $plugin = !empty($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
            $t = !empty($_REQUEST['t']) ? $_REQUEST['t'] : '';
            $response = array();
            if (!current_user_can('activate_plugins')){
                $response['error'] = __('You do not have sufficient permissions to deactivate plugins for this site.');
            }
            if( $plugin && $t ){
                if( $t == 'activate' ){
                    activate_plugin($plugin, false, is_network_admin() );
                }else{
                    deactivate_plugins( $plugin, false, is_network_admin() );
                }
                $is_activate = is_plugin_active( $plugin );
                $response['status'] = $is_activate ? 'activate' : 'deactivate';

            }
            wp_send_json( $response );
            die();
        }

        /**
         * Output the image to browser with text and params passed via $_GET
         */
        public static function dummy_image(){
            $text = ! empty( $_REQUEST['text'] ) ? $_REQUEST['text'] : '';
            learn_press_text_image( $text, $_GET );
            die();
        }

        /**
         * Get edit|view link of a page
         */
        public static function get_page_permalink(){
            $page_id = ! empty( $_REQUEST['page_id'] ) ? $_REQUEST['page_id'] : '';
            ?>
            <a href="<?php echo get_edit_post_link( $page_id );?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' );?></a>
            <a href="<?php echo get_permalink( $page_id );?>" target="_blank"><?php _e( 'View Page', 'learn_press' );?></a>
            <?php
            die();
        }

        /**
         * Create a new page with the title passed via $_REQUEST
         */
        public static function create_page(){
            $title = ! empty( $_REQUEST['title'] ) ? $_REQUEST['title'] : '';
            $response = array();
            if( $title ) {
                $args = array(
                    'post_type'     => 'page',
                    'post_title'    => $title,
                    'post_status'   => 'publish'
                );
                $page_id = wp_insert_post($args);
                $response['page'] = get_page( $page_id );
                $html = learn_press_pages_dropdown('', '', array('echo' => false));
                preg_match_all('!value=\"([0-9]+)\"!', $html, $matches);
                $response['ordering'] = $matches[1];
                $response['html'] = '<a href="' . get_edit_post_link( $page_id ) . '" target="_blank">' . __( 'Edit Page', 'learn_press' ) . '</a>&nbsp;';
                $response['html'] .= '<a href="' . get_permalink( $page_id ) . '" target="_blank">' . __( 'View Page', 'learn_press' ) . '</a>';
            }else{
                $response['error'] = __( 'Page name is empty!');
            }
            wp_send_json( $response );
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
				'post_type'   => 'lpr_lesson',
				'post_status' => 'publish'
			);

			wp_insert_post( $new_lesson );

			$args      = array(
				'numberposts' => 1,
				'post_type'   => 'lpr_lesson',
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
				'post_type'   => 'lpr_quiz',
				'post_status' => 'publish'
			);

			wp_insert_post( $new_quiz );

			$args    = array(
				'numberposts' => 1,
				'post_type'   => 'lpr_quiz',
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
LPR_Admin_Ajax::init();