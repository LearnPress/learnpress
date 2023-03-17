<?php

/**
 * Class LP_Query
 *
 * @version 4.2.2.2
 */

defined( 'ABSPATH' ) || exit;

class LP_Query {
	/**
	 * LP_Query constructor.
	 */
	public function __construct() {
		// Not run in REST API LP, or heartbeat of WP
		if ( LP_Helper::isRestApiLP() || isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) {
			return;
		}

		add_action( 'init', array( $this, 'add_rewrite_tags' ), 1000 );
		//add_action( 'admin_init', array( $this, 'add_rewrite_rules' ), -1 );
		// Clear cache rewrite rules when update option rewrite_rules
		//add_filter( 'pre_update_option', [ $this, 'update_option_rewrite_rules' ], 1, 3 );
		add_filter( 'option_rewrite_rules', [ $this, 'update_option_rewrite_rules' ], 1 );
		//add_action( 'parse_query', array( $this, 'parse_request' ), 1000, 1 );
		/**
		 * Add searching post by taxonomies
		 */
		add_action( 'pre_get_posts', array( $this, 'query_taxonomy' ) );
		// Clear cache rewrite rules when switch theme, activate, deactivate any plugins.
		//add_action( 'after_switch_theme', [ $this, 'clear_cache_rewrite_rules' ] );
		//add_action( 'activate_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
		//add_action( 'activated_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
		//add_action( 'deactivate_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
		//add_action( 'deactivated_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
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

		do_action_ref_array( 'learn_press_parse_query', array( &$this ) );

		return $q;
	}

	/**
	 * This function is cloned from wp core function
	 *
	 * @see WP()->parse_request()
	 *
	 * @return string
	 * @deprecated 4.2.2
	 */
	/*public function get_request() {
		global $wp_rewrite;

		$pathinfo         = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo         = str_replace( '%', '%25', $pathinfo );

		list( $req_uri ) = explode( '?', esc_url_raw( $_SERVER['REQUEST_URI'] ) );
		$self            = $_SERVER['PHP_SELF'];
		$home_path       = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
		$home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

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

		if ( ! empty( $pathinfo ) && ! preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
			$request = $pathinfo;
		} else {
			if ( $req_uri == $wp_rewrite->index ) {
				$req_uri = '';
			}
			$request = $req_uri;
		}

		return $request;
	}*/

	/**
	 * Add custom rewrite tags
	 */
	function add_rewrite_tags() {
		$tags = [
			'%course-item%'       => '([^&]+)',
			'%item-type%'         => '([^&]+)',
			'%question%'          => '([^&]+)',
			'%user%'              => '([^/]*)',
			'%view%'              => '([^/]*)',
			'%view_id%'           => '(.*)',
			'%section%'           => '(.*)',
			'%content-item-only%' => '(.*)',
		];

		$tags = apply_filters( 'learn-press/rewrite/tags', $tags );
		foreach ( $tags as $tag => $regex ) {
			add_rewrite_tag( $tag, $regex );
		}
		/*add_rewrite_tag( '%course-item%', '([^&]+)' );
		add_rewrite_tag( '%item-type%', '([^&]+)' );
		// add_rewrite_tag( '%quiz%', '([^&]+)' );
		add_rewrite_tag( '%question%', '([^&]+)' );
		add_rewrite_tag( '%user%', '([^/]*)' );

		add_rewrite_tag( '%view%', '([^/]*)' );
		add_rewrite_tag( '%view_id%', '(.*)' );
		add_rewrite_tag( '%section%', '(.*)' );

		add_rewrite_tag( '%content-item-only%', '(.*)' );*/
	}

	/**
	 * Add lp rewrite rules
	 * Format [key_parent][single_key] = [rule => match]
	 *
	 * link item course
	 * link profile
	 * @since 3.0.0
	 * @version 1.0.2
	 * @modify 4.2.2
	 */
	public function add_rewrite_rules() {
		$rules = array();

		/**
		 * Set rule item course.
		 *
		 * Use urldecode to convert an encoded string to normal.
		 * This fixed the issue with custom slug of lesson/quiz in some languages
		 * Eg: урока
		 */
		$lesson_slug       = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'lesson_slug', 'lessons' ) ) );
		$quiz_slug         = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'quiz_slug', 'quizzes' ) ) );
		$course_item_slugs = apply_filters(
			'learn-press/course-item-slugs/for-rewrite-rules',
			array(
				LP_LESSON_CPT => $lesson_slug,
				LP_QUIZ_CPT   => $quiz_slug,
			)
		);

		$course_slug = LP_Settings::get_option( 'course_base', 'courses' );
		if ( empty( $course_slug ) ) {
			$course_slug = 'courses';
		}
		$course_slug = preg_replace( '!^/!', '', $course_slug );

		// For permalink has %course_category%
		if ( preg_match( '!%course_category%!', $course_slug ) ) {
			if ( ! preg_match( '!page!', LP_Helper::getUrlCurrent() ) ) {
				$course_slug = preg_replace( '!%course_category%!', '([^/]+)/([^/]+)', $course_slug );

				foreach ( $course_item_slugs as $post_type => $course_item_slug ) {
					$rules['course-with-cat-items'][ $post_type ] = [
						"^{$course_slug}(?:/{$course_item_slug}/([^/]+))?/?$" =>
							'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=' . $post_type,
					];
				}

				// Todo fix: temporary addons before addons updated, when all addons updated, this code will be removed
				if ( class_exists( 'LP_Addon_Assignment_Preload' ) ) {
					$assignment_slug                                     = urldecode(
						sanitize_title_with_dashes(
							LP_Settings::get_option( 'assignment_slug', 'assignments' )
						)
					);
					$rules['course-with-cat-items'][ LP_ASSIGNMENT_CPT ] = [
						"^{$course_slug}(?:/{$assignment_slug}/([^/]+))?/?$" =>
							'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=' . LP_ASSIGNMENT_CPT,
					];
				}
				if ( class_exists( 'LP_Addon_H5p_Preload' ) ) {
					$h5p_slug                                     = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'h5p_slug', 'h5p' ) ) );
					$rules['course-with-cat-items'][ LP_H5P_CPT ] = [
						"^{$course_slug}(?:/{$h5p_slug}/([^/]+))?/?$" =>
							'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=' . LP_H5P_CPT,
					];
				}
				// End Fixed
			}
		} else {
			foreach ( $course_item_slugs as $post_type => $course_item_slug ) {
				$rules['course-items'][ $post_type ] = [
					"^{$course_slug}/([^/]+)(?:/{$course_item_slug}/([^/]+))?/?$" =>
					'index.php?' . LP_COURSE_CPT . '=$matches[1]&course-item=$matches[2]&item-type=' . $post_type,
				];
			}

			// Todo Fix: temporary addons before addons updated, when all addons updated, this code will be removed
			if ( class_exists( 'LP_Addon_Assignment_Preload' ) ) {
				$assignment_slug                            = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'assignment_slug', 'assignments' ) ) );
				$rules['course-items'][ LP_ASSIGNMENT_CPT ] = [
					"^{$course_slug}/([^/]+)(?:/{$assignment_slug}/([^/]+))?/?$" =>
					'index.php?' . LP_COURSE_CPT . '=$matches[1]&course-item=$matches[2]&item-type=' . LP_ASSIGNMENT_CPT,
				];
			}
			if ( class_exists( 'LP_Addon_H5p_Preload' ) ) {
				$h5p_slug                            = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'h5p_slug', 'h5p' ) ) );
				$rules['course-items'][ LP_H5P_CPT ] = [
					"^{$course_slug}/([^/]+)(?:/{$h5p_slug}/([^/]+))?/?$" =>
					'index.php?' . LP_COURSE_CPT . '=$matches[1]&course-item=$matches[2]&item-type=' . LP_H5P_CPT,
				];
			}
			// End Fixed
		}

		// Profile
		$profile_id = learn_press_get_page_id( 'profile' );
		if ( $profile_id ) {
			// Rule view profile of user (self or another)
			$page_profile_slug        = get_post_field( 'post_name', $profile_id );
			$rules['profile']['user'] = [
				"^{$page_profile_slug}/([^/]*)/?$" =>
					"index.php?page_id={$profile_id}&user=" . '$matches[1]',
			];

			// Rule view profile of user (self or another) with tab
			$profile = learn_press_get_profile();
			$tabs    = $profile->get_tabs()->get();
			if ( $tabs ) {
				/**
				 * @var LP_Profile_Tab $args
				 */
				foreach ( $tabs as $tab_key => $args ) {
					$tab_slug                     = $args->get( 'slug' ) ?? $tab_key;
					$rules['profile'][ $tab_key ] = [
						"^{$page_profile_slug}/([^/]*)/({$tab_slug})/?([0-9]*)/?$" =>
							'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&view_id=$matches[3]',
					];

					if ( ! empty( $args->get( 'sections' ) ) ) {
						foreach ( $args->get( 'sections' ) as $section_key => $section ) {
							$section_slug                     = $section['slug'] ?? $section_key;
							$rules['profile'][ $section_key ] = [
								"^{$page_profile_slug}/([^/]*)/({$tab_slug})/({$section_slug})/?([0-9]*)?$" =>
									'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&section=$matches[3]&view_id=$matches[4]',
							];
						}
					}
				}
			}
		}

		return apply_filters( 'learn-press/rewrite/rules', $rules );
	}

	/**
	 * Get current course user accessing
	 *
	 * @param string $return
	 *
	 * @return bool|false|int|LP_Course|mixed
	 * @deprecated 4.2.2
	 */
	public function get_course( $return = 'id' ) {
		_deprecated_function( __FUNCTION__, '4.2.2' );
		/*$course = false;
		if ( learn_press_is_course() ) {
			$course = get_the_ID();
		}
		if ( $course && $return == 'object' ) {
			$course = learn_press_get_course( $course );
		}

		return $course;*/
	}

	/**
	 * @deprecated 4.2.2
	 */
	public function get_course_item( $return = 'id' ) {
		_deprecated_function( __FUNCTION__, '4.2.2' );
		/*$course = $this->get_course( 'object' );
		$user   = learn_press_get_current_user();
		$item   = isset( $item ) ? $item : LearnPress::instance()->global['course-item'];
		if ( $item && $return == 'object' ) {
			$item = LP_Course::get_item( $item );
		}

		return $item;*/
	}

	/**
	 * @param WP_Query $q
	 */
	public function query_taxonomy( $q ) {

		// We only want to affect the main query
		if ( ! $q->is_main_query() ) {
			return;
		}

		if ( is_search() ) {
			add_filter( 'posts_where', array( $this, 'add_tax_search' ) );
			add_filter( 'posts_join', array( $this, 'join_term' ) );
			add_filter( 'posts_groupby', array( $this, 'tax_groupby' ) );
		}
	}

	/**
	 * @param string $join
	 *
	 * @return string
	 */
	public function join_term( $join ) {
		global $wp_query, $wpdb;

		if ( ! empty( $wp_query->query_vars['s'] ) && ! is_admin() ) {
			if ( ! preg_match( '/' . $wpdb->term_relationships . '/', $join ) ) {
				$join .= "LEFT JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id ";
			}
			$join .= "LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id ";
			$join .= "LEFT JOIN $wpdb->terms ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id ";
		}

		return $join;
	}

	/**
	 * @param string $where
	 *
	 * @return string
	 */
	public function add_tax_search( $where ) {
		global $wp_query, $wpdb;

		if ( ! empty( $wp_query->query_vars['s'] ) && ! is_admin() ) {
			$escaped_s = esc_sql( $wp_query->query_vars['s'] );
			$where    .= "OR $wpdb->terms.name LIKE '%{$escaped_s}%'";
		}

		return $where;
	}

	/**
	 * @param string $groupby
	 *
	 * @return string
	 */
	public function tax_groupby( $groupby ) {
		global $wpdb;
		$groupby = "{$wpdb->posts}.ID";

		$this->remove_query_tax();

		return $groupby;
	}

	/**
	 * Remove filter query
	 */
	public function remove_query_tax() {
		remove_filter( 'posts_where', 'learn_press_add_tax_search' );
		remove_filter( 'posts_join', 'learn_press_join_term' );
		remove_filter( 'posts_groupby', 'learn_press_tax_groupby' );
	}

	/**
	 * Clear cache rewrite rules when update option rewrite_rules
	 * Fixed for case: addons Certificates (v4.0.5), FE(4.0.5), Live(4.0.2), Collections(4.0.2) not installed on site client.
	 * Run only one time when reload page Frontend.
	 *
	 * @see get_option() hook in this function.
	 * @since 4.2.2
	 * @version 1.0.1
	 * @return mixed|array
	 */
	public function update_option_rewrite_rules( $wp_rules ) {
		if ( ! is_array( $wp_rules ) ) {
			return $wp_rules;
		}

		try {
			$lp_rules = $this->add_rewrite_rules();
			foreach ( $lp_rules as $key_parent => $rules ) {
				foreach ( $rules as $key => $rule ) {
					if ( is_array( $rule ) ) {
						$wp_rules = array_merge( $rule, $wp_rules );
					}
				}
			}
		} catch ( Throwable $e ) {
			error_log( sprintf( '%s:%s:%s', __FILE__, __LINE__, $e->getMessage() ) );
		}

		return $wp_rules;
	}

	/**
	 * Clear cache lp rewrite rules
	 *
	 * @since 4.2.2
	 */
	public function clear_cache_rewrite_rules() {
		$lp_settings_cache = new LP_Settings_Cache( true );
		$lp_settings_cache->clean_lp_rewrite_rules();
	}
}
