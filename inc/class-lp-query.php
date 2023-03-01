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
		// Not run in REST API LP, or heartbeat of WP
		if ( LP_Helper::isRestApiLP() || isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) {
			return;
		}

		add_action( 'init', array( $this, 'add_rewrite_tags' ), 1000, 0 );
		add_action( 'admin_init', array( $this, 'add_rewrite_rules' ), 1000, 0 );
		// Clear cache rewrite rules when update option rewrite_rules
		add_filter( 'pre_update_option', [ $this, 'update_option_rewrite_rules' ], 10, 2 );
		//add_action( 'parse_query', array( $this, 'parse_request' ), 1000, 1 );
		/**
		 * Add searching post by taxonomies
		 */
		add_action( 'pre_get_posts', array( $this, 'query_taxonomy' ) );
		// Clear cache rewrite rules when switch theme, activate, deactivate any plugins.
		add_action( 'after_switch_theme', [ $this, 'clear_cache_rewrite_rules' ] );
		add_action( 'activate_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
		add_action( 'activated_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
		add_action( 'deactivate_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
		add_action( 'deactivated_plugin', [ $this, 'clear_cache_rewrite_rules' ] );
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
	 *
	 * link item course
	 * link profile
	 * @since 3.0.0
	 * @version 1.0.1
	 * @modify 4.2.2
	 */
	public function add_rewrite_rules() {
		$lp_settings_cache = new LP_Settings_Cache( true );
		$cached_rules      = $lp_settings_cache->get_rewrite_rules();
		if ( false !== $cached_rules ) {
			return;
		}

		$rules = array();
		/*$course_type  = LP_COURSE_CPT;
		$post_types   = get_post_types( '', 'objects' );
		$slug         = preg_replace( '!^/!', '', $post_types[ $course_type ]->rewrite['slug'] );
		$has_category = false;

		if ( preg_match( '!(%?course_category%?)!', $slug ) ) {
			$slug         = preg_replace( '!(%?course_category%?)!', '(.+?)/([^/]+)', $slug );
			$has_category = true;
		}*/

		/**
		 * Set rule item course.
		 *
		 * Use urldecode to convert an encoded string to normal.
		 * This fixed the issue with custom slug of lesson/quiz in some languages
		 * Eg: урока
		 */
		$lesson_slug = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'lesson_slug', 'lessons' ) ) );
		$quiz_slug   = urldecode( sanitize_title_with_dashes( LP_Settings::get_option( 'quiz_slug', 'quizzes' ) ) );
		$course_slug = LP_Settings::get_option( 'course_base', 'courses' );
		if ( empty( $course_slug ) ) {
			$course_slug = 'courses';
		}
		$course_slug = preg_replace( '!^/!', '', $course_slug );

		if ( preg_match( '!%course_category%!', $course_slug ) ) {
			$course_slug = preg_replace( '!(%course_category%)!', '(.+)/([^/]+)', $course_slug );
			$rules[]     = array(
				"^{$course_slug}(?:/{$lesson_slug}/([^/]+))/?$",
				'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=lp_lesson',
				'top',
			);
			$rules[]     = array(
				"^{$course_slug}(?:/{$quiz_slug}/([^/]+)/?([^/]+)?)/?$",
				'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&question=$matches[4]&item-type=lp_quiz',
				'top',
			);
		} else {
			$rules[] = array(
				"^{$course_slug}/([^/]+)(?:/{$lesson_slug}/([^/]+))/?$",
				'index.php?' . LP_COURSE_CPT . '=$matches[1]&course-item=$matches[2]&item-type=lp_lesson',
				'top',
			);
			$rules[] = array(
				"^{$course_slug}/([^/]+)(?:/{$quiz_slug}/([^/]+)/?([^/]+)?)/?$",
				'index.php?' . LP_COURSE_CPT . '=$matches[1]&course-item=$matches[2]&question=$matches[3]&item-type=lp_quiz',
				'top',
			);
		}

		/*if ( ! empty( $custom_slug_quiz ) ) {
			$post_types['lp_quiz']->rewrite['slug'] = urldecode( $custom_slug_quiz );
		}*/

		/**
		 * Comment that, because it handled when register taxonomy
		 * @see LP_Course_Post_Type::register_taxonomy()
		 */
		/*if ( $has_category ) {
			$rules[] = array(
				'^' . $slug . '(?:/' . $post_types['lp_lesson']->rewrite['slug'] . '/([^/]+))/?$',
				'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=lp_lesson',
				'top',
			);

			$rules[] = array(
				'^' . $slug . '(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+)/?([^/]+)?)/?$',
				'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&question=$matches[4]&item-type=lp_quiz',
				'top',
			);

		} else {

			$rules[] = array(
				'^' . $slug . '/([^/]+)(?:/' . $post_types['lp_lesson']->rewrite['slug'] . '/([^/]+))/?$',
				'index.php?' . $course_type . '=$matches[1]&course-item=$matches[2]&item-type=lp_lesson',
				'top',
			);
			$rules[] = array(
				'^' . $slug . '/([^/]+)(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+)/?([^/]+)?)/?$',
				'index.php?' . $course_type . '=$matches[1]&course-item=$matches[2]&question=$matches[3]&item-type=lp_quiz',
				'top',
			);
		}*/

		// Profile
		$profile_id = learn_press_get_page_id( 'profile' );
		// Rule view profile of user (self or another)
		$page_profile_slug = get_post_field( 'post_name', $profile_id );
		$rules[]           = array(
			"^{$page_profile_slug}/([^/]*)/?$",
			"index.php?page_id={$profile_id}&user=" . '$matches[1]',
			'top',
		);

		// Rule view profile of user (self or another) with tab
		$profile = learn_press_get_profile();
		$tabs    = $profile->get_tabs()->get();
		if ( $tabs ) {
			foreach ( $tabs as $slug => $args ) {
				$tab_slug = $args['slug'] ?? $slug;
				$rules[]  = array(
					"^{$page_profile_slug}/([^/]*)/({$tab_slug})/?([0-9]*)/?$",
					'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&view_id=$matches[3]',
					'top',
				);

				if ( ! empty( $args['sections'] ) ) {
					foreach ( $args['sections'] as $section_slug => $section ) {
						$section_slug = $section['slug'] ?? $section_slug;
						$rules[]      = array(
							"^{$page_profile_slug}/([^/]*)/({$tab_slug})/({$section_slug})/?([0-9]*)?$",
							'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&section=$matches[3]&view_id=$matches[4]',
							'top',
						);
					}
				}
			}
		}

		// Archive course
		/*$course_page_id = learn_press_get_page_id( 'courses' );
		if ( $course_page_id ) {
		$rules[] = array(
			'^' . get_post_field( 'post_name', $course_page_id ) . '/page/([0-9]{1,})/?$',
			'index.php?pagename=' . get_post_field( 'post_name', $course_page_id ) . '&page=$matches[1]',
			'top',
		);
		}*/

		//global $wp_rewrite;

		/**
		 * Polylang compatibility
		 */
		/*if ( function_exists( 'PLL' ) ) {
		$pll           = PLL();
		$pll_languages = $pll->model->get_languages_list( array( 'fields' => 'slug' ) );

		if ( $pll->options['hide_default'] ) {
			if ( isset( $pll->options['default_lang'] ) ) {
				$pll_languages = array_diff( $pll_languages, array( $pll->options['default_lang'] ) );
			}
		}

		if ( ! empty( $pll_languages ) ) {
			$pll_languages = $wp_rewrite->root . ( $pll->options['rewrite'] ? '' : 'language/' ) . '(' . implode( '|', $pll_languages ) . ')/';
		} else {
			$pll_languages = '';
		}
		}*/

		$rules = apply_filters( 'learn-press/rewrite/rules', $rules );

		// Register rules
		foreach ( $rules as $k => $rule ) {
			call_user_func_array( 'add_rewrite_rule', $rule );

			/**
			 * Modify rewrite rule
			 */
			/*if ( isset( $pll_languages ) ) {

			$rule[0]     = $pll_languages . str_replace( $wp_rewrite->root, '', ltrim( $rule[0], '^' ) );
			$rule[1]     = str_replace(
				array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
				array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
				$rule[1]
			);
			$new_rules[] = $rule;
			call_user_func_array( 'add_rewrite_rule', $rule );
			}*/
		}

		/*$new_rules = md5( serialize( $new_rules ) );
		$old_rules = get_transient( 'lp_rewrite_rules_hash' );

		if ( $old_rules !== $new_rules ) {
		set_transient( 'lp_rewrite_rules_hash', $new_rules, DAY_IN_SECONDS );
		flush_rewrite_rules();
		}*/

		flush_rewrite_rules();
		$lp_settings_cache->set_rewrite_rules( 1 );
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
	 * @param $value
	 * @param $option
	 * @since 4.2.2
	 * @return mixed
	 */
	public function update_option_rewrite_rules( $value, $option ) {
		if ( 'rewrite_rules' === $option ) {
			static $flushed;
			if ( $flushed || is_admin() ) {
				return $value;
			}

			$flushed = 1;
			$this->clear_cache_rewrite_rules();
			$this->add_rewrite_rules();
		}

		return $value;
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
