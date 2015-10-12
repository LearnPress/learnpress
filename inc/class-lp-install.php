<?php
/**
 * Install and update functions
 *
 * @author  ThimPress
 * @version 1.0
 * @see     https://codex.wordpress.org/Creating_Tables_with_Plugins
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Class LP_Install
 */
class LP_Install {

	/**
	 * DB update versions
	 *
	 * @var array
	 */
	private static $_db_updates = array();

	/**
	 * Init
	 */
	static function init() {
		add_action( 'admin_init', array( __CLASS__, 'update_version_10' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'get_update_versions' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'db_update_notices' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'update_actions' ), 5 );
		add_action( 'wp_ajax_lp_repair_database', array( __CLASS__, 'repair_database' ) );
		add_action( 'wp_ajax_lp_rollback_database', array( __CLASS__, 'rollback_database' ) );

	}

	static function update_version_10(){
		//$post_type = ! empty( $_REQUEST['post'])
		if( get_option( 'learnpress_db_version' ) == null  ){
			//wp_redirect( admin_url( 'admin.php?page=learnpress_update_10') );
		}
	}

	/**
	 * Auto get update patches from inc/updates path
	 */
	static function get_update_versions(){
		if( !$patches = get_transient( 'learnpress_update_patches' ) ){
			$patches = array();
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if (WP_Filesystem()) {
				global $wp_filesystem;

				$list = $wp_filesystem->dirlist( LP_PLUGIN_PATH . '/inc/updates');
				foreach ($list as $file) {
					if( preg_match( '!learnpress-update-([0-9.]+).php!', $file['name'], $matches ) ){
						$patches[ $matches[1] ] = $file['name'];
					}
				}
			}
			if( $patches ){
				self::$_db_updates = $patches;
			}
		}else{
			self::$_db_updates = $patches;
		}
	}

	/**
	 * Check version
	 */
	static function check_version() {
		if ( !defined( 'IFRAME_REQUEST' ) && ( get_option( 'learnpress_version' ) != LP()->version || get_option( 'learnpress_version' ) != LP()->version ) ) {
			self::install();
		}
	}

	/**
	 * Install update actions when user click update button
	 */
	static function update_actions() {
		if ( !empty( $_GET['upgrade_learnpress'] ) ) {
			self::update();
		}
	}

	/**
	 * Check for new database version and show notice
	 */
	static function db_update_notices(){
		if( get_option( 'learnpress_db_version' ) != LP()->db_version ){
			LP_Admin_Notice::add( __( '<p>LearnPress ' . LP()->version . ' need to upgrade your database.</p><p><a href="' . admin_url( 'admin.php?page=learnpress_update_10' ) . '" class="button">Update Now</a></p>', 'learn_press' ) );
		}
	}

	static function install_options() {
		$options = array(
			'_lpr_settings_general' => 'a:6:{s:8:"set_page";s:11:"lpr_profile";s:8:"currency";s:3:"USD";s:12:"currency_pos";s:15:"left_with_space";s:19:"thousands_separator";s:1:",";s:18:"decimals_separator";s:1:".";s:18:"number_of_decimals";s:1:"2";}',
			'_lpr_settings_pages'   => 'a:3:{s:7:"general";a:2:{s:15:"courses_page_id";s:0:"";s:28:"taken_course_confirm_page_id";s:0:"";}s:6:"course";a:1:{s:13:"retake_course";s:1:"0";}s:4:"quiz";a:1:{s:11:"retake_quiz";s:1:"0";}}',
			'_lpr_settings_payment' => 'a:1:{s:6:"paypal";a:9:{s:4:"type";s:5:"basic";s:12:"paypal_email";s:0:"";s:19:"paypal_api_username";s:0:"";s:19:"paypal_api_password";s:0:"";s:20:"paypal_api_signature";s:0:"";s:20:"paypal_sandbox_email";s:0:"";s:27:"paypal_sandbox_api_username";s:0:"";s:27:"paypal_sandbox_api_password";s:0:"";s:28:"paypal_sandbox_api_signature";s:0:"";}}',
			'_lpr_settings_emails'  => 'a:3:{s:16:"published_course";a:3:{s:6:"enable";s:1:"1";s:7:"subject";s:15:"Approved Course";s:7:"message";s:215:"<p><strong>Dear {user_name}</strong>,</p>
<p>Congratulation! The course you created ({course_name}) is available now.</p>
<p>Visit our website at {log_in}.</p>
<p>Best regards,</p>
<p><em>Administration</em></p>";}s:15:"enrolled_course";a:3:{s:6:"enable";s:1:"1";s:7:"subject";s:19:"Course Registration";s:7:"message";s:183:"<p><strong>Dear {user_name}</strong>,</p>
<p>You have been enrolled in {course_name}.</p>
<p>Visit our website at {log_in}.</p>
<p>Best regards,</p>
<p><em>Administration</em></p>";}s:13:"passed_course";a:3:{s:6:"enable";s:1:"1";s:7:"subject";s:18:"Course Achievement";s:7:"message";s:203:"<p><strong>Dear {user_name}</strong>,</p>
<p>You have been finished in {course_name} with {course_result}</p>
<p>Visit our website at {log_in}.</p>
<p>Best regards,</p>
<p><em>Administration</em></p>";}}'
		);
		foreach ( $options as $k => $option ) {
			if ( !get_option( $k ) ) {
				update_option( $k, maybe_unserialize( $option ) );
			}
		}
	}

	static function install() {
		self::install_options();

		// Update version
		delete_option( 'learnpress_version' );
		add_option( 'learnpress_version', LP()->version );

		$s = learn_press_admin_settings( 'emails' );
		$s->set( 'general', array(
				'from_name'  => get_option( 'blogname' ),
				'from_email' => get_option( 'admin_email' )
			)
		);
		$s->update();
	}

	static function get_posts_by_ids( $ids ){
		global $wpdb;
		$query = "SELECT * FROM {$wpdb->posts} WHERE ID IN(" . join( ',', $ids ) . ")";
		return $wpdb->get_results( $query, ARRAY_A );
	}

	static function get_post_meta( $post_id, $keys ){
		global $wpdb;

		$query = $wpdb->prepare("
			SELECT pm.*
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key in ('" . join( "','", $keys ) . "')
			AND pm.post_id = %d
		", absint( $post_id ) );
		return $wpdb->get_results( $query, ARRAY_A );
	}

	static function repair_database(){
		global $wpdb;
		$query = $wpdb->prepare("
			SELECT *
			FROM {$wpdb->posts}
			WHERE `post_type` = %s
		", 'lpr_course' );

		$new_courses = array();

		if( $old_courses = $wpdb->get_results( $query ) ){
			foreach( $old_courses as $old_course ){
				$course_args = (array) $old_course;
				$course_args['post_type'] = 'lp_course';
				unset( $course_args['ID'] );
				$new_course = wp_insert_post( $course_args );
				if( $new_course ){
					$course_args['ID'] = $new_course;
					$new_courses[$old_course->ID] = $course_args;
				}
			}
		}

		set_transient( 'learnpress_courses', $new_courses, DAY_IN_SECONDS );

		$curriculum_objects = array();
		if( $new_courses ){
			foreach( $new_courses as $old_id => $new_course ){
				$curriculum = get_post_meta( $old_id, '_lpr_course_lesson_quiz', true );
				if( $curriculum ){

					foreach( $curriculum as $order => $section ){
						$result = $wpdb->insert(
							$wpdb->prefix . 'learnpress_sections',
							array(
								'name'		=> $section['name'],
								'course_id' => $new_course['ID'],
								'order'		=> $order + 1
							),
							array( '%s', '%d', '%d' )
						);
						if( $result ) {
							$curriculum_id = $wpdb->insert_id;
							$lesson_quiz = !empty( $section['lesson_quiz'] ) ? $section['lesson_quiz'] : '';
							if( ! $lesson_quiz ) continue;
							$lesson_quiz = self::get_posts_by_ids( $lesson_quiz );
							if( ! $lesson_quiz ) continue;
							$order = 1;
							foreach( $lesson_quiz as $obj ){
								if( $obj['post_type'] == 'lpr_quiz'){
									$obj['post_type'] = 'lp_quiz';
								}else{
									$obj['post_type'] = 'lp_lesson';
								}
								$obj_id = $obj['ID'];
								unset( $obj['ID'] );
								if( $new_obj_id = wp_insert_post( $obj ) ){
									$wpdb->insert(
										$wpdb->prefix . 'learnpress_section_items',
										array(
											'section_id'	=> $curriculum_id,
											'item_id'		=> $new_obj_id,
											'order'			=> $order++
										)
									);
								}
								$curriculum_objects[$obj_id] = $new_obj_id;
							}
						}
					}
				}
				$keys = array(
					'_lpr_course_duration' 				=> '_lp_duration',
					'_lpr_course_number_student'		=> '_lp_students',
					'_lpr_max_course_number_student'	=> '_lp_max_students',
					'_lpr_retake_course'				=> '_lp_retake',
					'_lpr_course_final'					=> '_lp_final_quiz',
					'_lpr_course_condition'				=> '_lp_passing_condition',
					'_lpr_course_enrolled_require'		=> '_lp_required_enroll',
					'_lpr_course_payment'				=> '_lp_payment'
				);
				$course_meta = self::get_post_meta( $old_id, array_keys( $keys ) );
				//print_r($course_meta);
				if( $course_meta ) foreach( $course_meta as $meta ){
					add_post_meta( $new_course['ID'], $keys[ $meta['meta_key'] ], $meta['meta_value'] );
				}

				// TODO: _lpr_course_user
			}
		}
		set_transient( 'learnpress_curriculum', $curriculum_objects, DAY_IN_SECONDS );

		update_option( 'learnpress_db_version', '1.0' );
		print_r( $new_courses );
		print_r( $curriculum_objects );
		learn_press_send_json(
			array(
				'result'	=> 'success',
				'message'	=> sprintf( __( 'Congrats, database has updated! Next, where do you want to go? <a href="%s" class="button">Admin</a> or <a href="%s" class="button button-primary">Start create a course</a>'), admin_url(), admin_url( 'post-new.php?post_type=lp_course' ) )
			)
		);
	}

	static function rollback_database(){
		global $wpdb;
		$query = "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type IN('lp_course', 'lp_lesson', 'lp_quiz', 'lp_question', 'lp_assignment', 'lp_order' )
		";
		if( $ids = $wpdb->get_col($query)){
			$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN(" . join( ",", $ids ) . ")");
			$wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN(" . join( ",", $ids ) . ")");

			$wpdb->query("DELETE FROM {$wpdb->learnpress_sections}");
			$wpdb->query("DELETE FROM {$wpdb->learnpress_section_items}");
			$wpdb->query("DELETE FROM {$wpdb->learnpress_quiz_history}");
			$wpdb->query("DELETE FROM {$wpdb->learnpress_user_course}");
		}
		delete_option( 'learnpress_db_version' );
		die();
	}
}

LP_Install::init();