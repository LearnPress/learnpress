<?php

/**
 * Class LP_Query
 */

defined( 'ABSPATH' ) || exit;

class LP_Query {
	/**
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * LP_Query constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_tags' ), 1000, 0 );
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 1000, 0 );
		add_action( 'parse_query', array( $this, 'parse_request' ), 1000, 1 );
	}

	/**
	 * Parses request params and controls page
	 *
	 * @param WP_Query $q
	 *
	 * @return mixed
	 */
	public function parse_request( $q ) {
		if ( did_action( 'learn_press_parse_query' ) ) {
			return $q;
		}
		$user    = learn_press_get_current_user();
		$request = $this->get_request();
		if ( !$request || is_admin() ) {
			return $q;
		}
		remove_filter( 'do_parse_request', array( $this, 'get_current_quiz_question' ), 1010, 3 );
		$course_type = 'lp_course';
		$post_types  = get_post_types( '', 'objects' );

		if ( empty( $post_types[$course_type] ) ) {
			return;
		}
		/********************/
		if ( empty( $q->query_vars[LP_COURSE_CPT] ) ) {
			return;
		}
		$this->query_vars['course'] = $q->query_vars[LP_COURSE_CPT];
		$course_id                  = learn_press_setup_course_data( $this->query_vars['course'] );
		$quiz_name                  = '';
		$question_name              = '';
		$lesson_name                = '';
		if ( !empty( $q->query_vars['quiz'] ) ) {
			$quiz_name = $this->query_vars['quiz'] = $q->query_vars['quiz'];
			$this->query_vars['item_type']         = LP_QUIZ_CPT;
			if ( !empty( $q->query_vars['question'] ) ) {
				$question_name = $this->query_vars['question'] = $q->query_vars['question'];
			}
		} elseif ( !empty( $q->query_vars['lesson'] ) ) {
			$lesson_name = $this->query_vars['lesson'] = $q->query_vars['lesson'];
			$this->query_vars['item_type']             = LP_LESSON_CPT;
		}
		if ( $quiz_name && $question_name ) {
			if ( $quiz = learn_press_get_post_by_name( $quiz_name, LP_QUIZ_CPT ) ) {
				if ( $user->has_quiz_status( 'completed', $quiz->ID, $course_id ) ) {
					//remove question name from uri
					$course   = learn_press_get_course( $course_id );
					$redirect = $course->get_item_link( $quiz->ID );// get_site_url() . '/' . dirname( $request_match );
					wp_redirect( $redirect );
					exit();
				}
				if ( $question = learn_press_get_post_by_name( $question_name, 'lp_question' ) ) {
					global $wpdb;
					$query = $wpdb->prepare( "
						SELECT MAX(user_item_id)
						FROM {$wpdb->prefix}learnpress_user_items
						WHERE user_id = %d AND item_id = %d AND item_type = %s and status <> %s
					", learn_press_get_current_user_id(), $quiz->ID, 'lp_quiz', 'completed' );
					if ( $history_id = $wpdb->get_var( $query ) ) {
						learn_press_update_user_item_meta( $history_id, 'current_question', $question->ID );
					}
				}
			}
		}
		$this->query_vars['course_id'] = $course_id;
		do_action_ref_array( 'learn_press_parse_query', array( &$this ) );

		return $q;
		/************************/
		$slug = preg_replace( '!^/!', '', $post_types[$course_type]->rewrite['slug'] );

		$match = '^' . $slug . '/([^/]*)/(' . $post_types['lp_quiz']->rewrite['slug'] . ')?/([^/]*)?/?([^/]*)?';

		$request_match = $request;
		$course_id     = 0;
		if ( !empty( $q->query_vars['post_type'] ) && $q->query_vars['post_type'] == LP_COURSE_CPT ) {
			if ( !empty( $q->query_vars[LP_COURSE_CPT] ) ) {
				$this->query_vars['course'] = $q->query_vars[LP_COURSE_CPT];
				$course_id                  = learn_press_setup_course_data( $this->query_vars['course'] );
			}
			if ( !empty( $q->query_vars['quiz'] ) ) {
				$this->query_vars['quiz']      = $q->query_vars['quiz'];
				$this->query_vars['item_type'] = LP_QUIZ_CPT;
			} elseif ( !empty( $q->query_vars['lesson'] ) ) {
				$this->query_vars['lesson']    = $q->query_vars['lesson'];
				$this->query_vars['item_type'] = LP_LESSON_CPT;
			}
		}

		/**
		 * Match request URI with quiz permalink
		 */
		if ( preg_match( "#^$match#", $request_match, $matches ) || preg_match( "#^$match#", urldecode( $request_match ), $matches ) ) {

			// If request URI is a quiz permalink
			if ( !empty( $matches[3] ) ) {
				if ( !$post = learn_press_get_post_by_name( $matches[3], 'lp_quiz', true ) ) {
					return $q;
				}
				// If request URI does not contains a question
				// Try to get current question of current user and put it into URI
				if ( empty( $matches[4] ) ) {
					if ( $user->has_quiz_status( 'started', $post->ID, $course_id ) && $question_id = $user->get_current_quiz_question( $post->ID, $course_id ) ) {
						$this->query_vars['question'] = $q->query_vars['question'] = get_post_field( 'post_name', $question_id );
					}
				} else {
					// If user is viewing a question then update current question for user
					$question = learn_press_get_post_by_name( $matches[4], 'lp_question' );
					/**
					 * If user has completed a quiz but they are accessing to a question inside quiz,
					 * redirect them back to quiz to show results of that quiz instead
					 */
					if ( $user->has_quiz_status( 'completed', $post->ID, $course_id ) ) {
						//remove question name from uri
						$redirect = get_site_url() . '/' . dirname( $request_match );
						wp_redirect( $redirect );
						exit();
					}
					if ( $question ) {
						global $wpdb;
						$query = $wpdb->prepare( "
							SELECT MAX(user_item_id)
							FROM {$wpdb->prefix}learnpress_user_items
							WHERE user_id = %d AND item_id = %d AND item_type = %s and status <> %s
						", learn_press_get_current_user_id(), $post->ID, 'lp_quiz', 'completed' );
						if ( $history_id = $wpdb->get_var( $query ) ) {
							learn_press_update_user_item_meta( $history_id, 'current_question', $question->ID );
						}
					}
				}
			}
		}
		$this->query_vars['course_id'] = $course_id;
		do_action_ref_array( 'learn_press_parse_query', array( &$this ) );
		return $q;
	}

	/**
	 * This function is cloned from wp core function
	 *
	 * @see WP()->parse_request()
	 *
	 * @return string
	 */
	public function get_request() {
		global $wp_rewrite;
		$pathinfo = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo = str_replace( "%", "%25", $pathinfo );

		list( $req_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
		$self            = $_SERVER['PHP_SELF'];
		$home_path       = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
		$home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

		// Trim path info from the end and the leading home path from the
		// front. For path info requests, this leaves us with the requesting
		// filename, if any. For 404 requests, this leaves us with the
		// requested permalink.
		$req_uri  = str_replace( $pathinfo, '', $req_uri );
		$req_uri  = trim( $req_uri, '/' );
		$req_uri  = preg_replace( $home_path_regex, '', $req_uri );
		$req_uri  = trim( $req_uri, '/' );
		$pathinfo = trim( $pathinfo, '/' );
		$pathinfo = preg_replace( $home_path_regex, '', $pathinfo );
		$pathinfo = trim( $pathinfo, '/' );
		$self     = trim( $self, '/' );
		$self     = preg_replace( $home_path_regex, '', $self );
		$self     = trim( $self, '/' );

		// The requested permalink is in $pathinfo for path info requests and
		//  $req_uri for other requests.
		if ( !empty( $pathinfo ) && !preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
			$request = $pathinfo;
		} else {
			// If the request uri is the index, blank it out so that we don't try to match it against a rule.
			if ( $req_uri == $wp_rewrite->index )
				$req_uri = '';
			$request = $req_uri;
		}
		return $request;
	}

	/**
	 * Add custom rewrite tags
	 */
	function add_rewrite_tags() {
		add_rewrite_tag( '%lesson%', '([^&]+)' );
		add_rewrite_tag( '%quiz%', '([^&]+)' );
		add_rewrite_tag( '%question%', '([^&]+)' );
		add_rewrite_tag( '%user%', '([^/]*)' );
		add_rewrite_tag( '%course-query-string%', '(.*)' );
		do_action( 'learn_press_add_rewrite_tags' );
	}

	/**
	 * Add more custom rewrite rules
	 */
	function add_rewrite_rules() {

		$rewrite_prefix = get_option( 'learn_press_permalink_structure' );
		// lesson
		$course_type  = 'lp_course';
		$post_types   = get_post_types( '', 'objects' );
		$slug         = preg_replace( '!^/!', '', $post_types[$course_type]->rewrite['slug'] );
		$has_category = false;
		if ( preg_match( '!(%?course_category%?)!', $slug ) ) {
			$slug         = preg_replace( '!(%?course_category%?)!', '(.+?)/([^/]+)', $slug );
			$has_category = true;
		}
		$current_url  = learn_press_get_current_url();
		$query_string = str_replace( trailingslashit( get_site_url() ), '', $current_url );

		if ( $has_category ) {
			add_rewrite_rule(
				'^' . $slug . '(?:/' . $post_types['lp_lesson']->rewrite['slug'] . '/([^/]+))/?$',
				'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&lesson=$matches[3]',
				'top'
			);
			add_rewrite_rule(
				'^' . $slug . '(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+)/?([^/]+)?)/?$',
				'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&quiz=$matches[3]&question=$matches[4]',
				'top'
			);
		} else {
			add_rewrite_rule(
				'^' . $slug . '/([^/]+)(?:/' . $post_types['lp_lesson']->rewrite['slug'] . '/([^/]+))/?$',
				'index.php?' . $course_type . '=$matches[1]&lesson=$matches[2]',
				'top'
			);
			add_rewrite_rule(
				'^' . $slug . '/([^/]+)(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+)/?([^/]+)?)/?$',
				'index.php?' . $course_type . '=$matches[1]&quiz=$matches[2]&question=$matches[3]',
				'top'
			);
		}

		if ( $profile_id = learn_press_get_page_id( 'profile' ) ) {
			add_rewrite_rule(
				'^' . $rewrite_prefix . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?([^/]*)/?([^/]*)/?([^/]*)/?([^/]*)/?',
				'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&id=$matches[3]&paged=$matches[4]',
				'top'
			);
		}
		do_action( 'learn_press_add_rewrite_rules' );
		return;

		/**
		 * Lesson permalink without category
		 */
		/*add_rewrite_rule(
			'^' . $slug . '/([^/]*)/(' . $post_types['lp_lesson']->rewrite['slug'] . ')/([^/]+)/?$',
			'index.php?' . $course_type . '=$matches[1]&lesson=$matches[3]',
			'top'
		);*/

		/**
		 * Quiz permalink with category inside
		 */
		add_rewrite_rule(
			'^course/(.+?)/([^/]+)(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+))/?$',
			'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&quiz=$matches[3]',
			'top'
		);

		/**
		 * Lesson permalink without category
		 */
		add_rewrite_rule(
			'^' . $slug . '/([^/]*)/(' . $post_types['lp_quiz']->rewrite['slug'] . ')/([^/]+)/?$',
			'index.php?' . $course_type . '=$matches[1]&quiz=$matches[3]',
			'top'
		);


		/*add_rewrite_rule(
			'^' . $slug . '/([^/]*)/(' . $post_types['lp_quiz']->rewrite['slug'] . ')?/([^/]*)?/?([^/]*)?',
			'index.php?' . $course_type . '=$matches[1]&quiz=$matches[3]&question=$matches[4]',
			'top'
		);*/


	}

	/**
	 * @param $query
	 *
	 * @return array
	 */
	function parse_course_request( $query ) {
		$return = array();
		if ( !empty( $query ) ) {
			$segments = explode( '/', $query );
			$segments = array_filter( $segments );
			if ( $segments ) {
				$ids   = array();
				$names = array();
				foreach ( $segments as $segment ) {
					if ( preg_match( '/^([0-9]+)/', $segment ) ) {
						$post_args = explode( '-', $segment, 2 );
						$ids[]     = absint( $post_args[0] );
						$names[]   = $post_args[1];
					}
				}

				if ( sizeof( $ids ) ) {
					global $wpdb;
					$ids_format   = array_fill( 0, sizeof( $ids ), '%d' );
					$names_format = array_fill( 0, sizeof( $names ), '%s' );

					$query = $wpdb->prepare( "
					SELECT ID, post_name, post_type
					FROM {$wpdb->posts}
					WHERE ID IN(" . join( ',', $ids_format ) . ")
						AND post_name IN(" . join( ',', $names_format ) . ")
					ORDER BY FIELD(ID, " . join( ',', $ids_format ) . ")
				", array_merge( $ids, $names, $ids ) );
					if ( $items = $wpdb->get_results( $query ) ) {
						$support_types = learn_press_course_get_support_item_types();
						foreach ( $items as $item ) {
							if ( array_key_exists( $item->post_type, $support_types ) ) {
								$return[] = $item;
							}
						}
					}
				}
			}
		}
		return $return;
	}

	/**
	 * This function parse query vars and put into request
	 */
	function parse_query_vars_to_request() {
		global $wp_query, $wp;
		if ( isset( $wp_query->query['user'] ) ) {
			/*if ( !get_option( 'permalink_structure' ) ) {
				$wp_query->query_vars['user']     = !empty( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;
				$wp_query->query_vars['tab']      = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : null;
				$wp_query->query_vars['order_id'] = !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : null;
				$wp_query->query['user']          = !empty( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;
				$wp_query->query['tab']           = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : null;
				$wp_query->query['order_id']      = !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : null;
			} else {
				list( $username, $tab, $id ) = explode( '/', $wp_query->query['user'] );
				$wp_query->query_vars['user']     = $username;
				$wp_query->query_vars['tab']      = $tab;
				$wp_query->query_vars['order_id'] = $id;
				$wp_query->query['user']          = $username;
				$wp_query->query['tab']           = $tab;
				$wp_query->query['order_id']      = $id;
			}*/
		}
		global $wpdb;
		// if lesson name is passed, find it's ID and put into request
		/*if ( !empty( $wp_query->query_vars['lesson'] ) ) {
			if ( $lesson_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s", $wp_query->query_vars['lesson'], LP_LESSON_CPT ) ) ) {
				$_REQUEST['lesson'] = $lesson_id;
				$_GET['lesson']     = $lesson_id;
				$_POST['lesson']    = $lesson_id;
			}
		}*/
		// if question name is passed, find it's ID and put into request
		/*if ( !empty( $wp_query->query_vars['question'] ) ) {
			if ( $question_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s", $wp_query->query_vars['question'], LP_QUESTION_CPT ) ) ) {
				$_REQUEST['question'] = $question_id;
				$_GET['question']     = $question_id;
				$_POST['question']    = $question_id;
			}
		}*/

	}

	/**
	 * Get current course user accessing
	 *
	 * @param string $return
	 *
	 * @return bool|false|int|LP_Course|mixed
	 */
	public function get_course( $return = 'id' ) {
		$course = false;
		if ( learn_press_is_course() ) {
			$course = get_the_ID();
		}
		if ( $course && $return == 'object' ) {
			$course = learn_press_get_course( $course );
		}
		return $course;
	}

	public function get_course_item( $return = 'id' ) {
		$course = $this->get_course( 'object' );
		$user   = learn_press_get_current_user();
		$item   = isset( $item ) ? $item : LP()->global['course-item'];
		if ( $item && $return == 'object' ) {
			$item = LP_Course::get_item( $item );
		}
		return $item;
	}
}