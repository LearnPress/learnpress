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
		/**
		 * Add searching post by taxonomies
		 */
		add_action( 'pre_get_posts', array( $this, 'query_taxonomy' ) );
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
		if ( ! empty( $pathinfo ) && ! preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
			$request = $pathinfo;
		} else {
			// If the request uri is the index, blank it out so that we don't try to match it against a rule.
			if ( $req_uri == $wp_rewrite->index ) {
				$req_uri = '';
			}
			$request = $req_uri;
		}

		return $request;
	}

	/**
	 * Add custom rewrite tags
	 */
	function add_rewrite_tags() {
		add_rewrite_tag( '%course-item%', '([^&]+)' );
		add_rewrite_tag( '%item-type%', '([^&]+)' );
		//add_rewrite_tag( '%quiz%', '([^&]+)' );
		add_rewrite_tag( '%question%', '([^&]+)' );
		add_rewrite_tag( '%user%', '([^/]*)' );

		add_rewrite_tag( '%view%', '([^/]*)' );
		add_rewrite_tag( '%view_id%', '(.*)' );
		add_rewrite_tag( '%section%', '(.*)' );

		add_rewrite_tag( '%content-item-only%', '(.*)' );
		do_action( 'learn_press_add_rewrite_tags' );
	}

	/**
	 * Add more custom rewrite rules
	 */
	function add_rewrite_rules() {

		// lesson
		$course_type  = LP_COURSE_CPT;
		$post_types   = get_post_types( '', 'objects' );
		$slug         = preg_replace( '!^/!', '', $post_types[ $course_type ]->rewrite['slug'] );
		$has_category = false;

		if ( preg_match( '!(%?course_category%?)!', $slug ) ) {
			$slug         = preg_replace( '!(%?course_category%?)!', '(.+?)/([^/]+)', $slug );
			$has_category = true;
		}

		$custom_slug_lesson = sanitize_title_with_dashes( LP()->settings->get( 'lesson_slug' ) );
		$custom_slug_quiz   = sanitize_title_with_dashes( LP()->settings->get( 'quiz_slug' ) );

		/**
		 * Use urldecode to convert an encoded string to normal.
		 * This fixed the issue with custom slug of lesson/quiz in some languages
		 * Eg: урока
		 */
		if ( ! empty( $custom_slug_lesson ) ) {
			$post_types['lp_lesson']->rewrite['slug'] = urldecode( $custom_slug_lesson );
		}

		if ( ! empty( $custom_slug_quiz ) ) {
			$post_types['lp_quiz']->rewrite['slug'] = urldecode( $custom_slug_quiz );
		}

		$rules = array();

		if ( $has_category ) {
			$rules[] = array(
				'^' . $slug . '(?:/' . $post_types['lp_lesson']->rewrite['slug'] . '/([^/]+))/?$',
				'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=lp_lesson',
				'top'
			);

			$rules[] = array(
				'^' . $slug . '(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+)/?([^/]+)?)/?$',
				'index.php?' . $course_type . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&question=$matches[4]&item-type=lp_quiz',
				'top'
			);

		} else {

			$rules[] = array(
				'^' . $slug . '/([^/]+)(?:/' . $post_types['lp_lesson']->rewrite['slug'] . '/([^/]+))/?$',
				'index.php?' . $course_type . '=$matches[1]&course-item=$matches[2]&item-type=lp_lesson',
				'top'
			);
			$rules[] = array(
				'^' . $slug . '/([^/]+)(?:/' . $post_types['lp_quiz']->rewrite['slug'] . '/([^/]+)/?([^/]+)?)/?$',
				'index.php?' . $course_type . '=$matches[1]&course-item=$matches[2]&question=$matches[3]&item-type=lp_quiz',
				'top'
			);
		}

		// Profile
		if ( $profile_id = learn_press_get_page_id( 'profile' ) ) {

			$rules[] = array(
				'^' . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?$',
				'index.php?page_id=' . $profile_id . '&user=$matches[1]',
				'top'
			);

			$profile = learn_press_get_profile();
			if ( $tabs = $profile->get_tabs()->get() ) {
				foreach ( $tabs as $slug => $args ) {
					$tab_slug = isset( $args['slug'] ) ? $args['slug'] : $slug;
					$rules[]  = array(
						'^' . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?(' . $tab_slug . ')/?([0-9]*)/?$',
						'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&view_id=$matches[3]',
						'top'
					);

					if ( ! empty( $args['sections'] ) ) {
						foreach ( $args['sections'] as $section_slug => $section ) {
							$section_slug = isset( $section['slug'] ) ? $section['slug'] : $section_slug;
							$rules[]      = array(
								'^' . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?(' . $tab_slug . ')/(' . $section_slug . ')/?([0-9]*)?$',
								'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&section=$matches[3]&view_id=$matches[4]',
								'top'
							);
						}
					}
				}
			}
		}

		// Archive course
		if ( $course_page_id = learn_press_get_page_id( 'courses' ) ) {
			$rules[] = array(
				'^' . get_post_field( 'post_name', $course_page_id ) . '/page/([0-9]{1,})/?$',
				'index.php?pagename=' . get_post_field( 'post_name', $course_page_id ) . '&page=$matches[1]',
				'top'
			);
		}

		global $wp_rewrite;

		/**
		 * Polylang compatibility
		 */
		if ( function_exists( 'PLL' ) ) {
			$pll           = PLL();
			$pll_languages = $pll->model->get_languages_list( array( 'fields' => 'slug' ) );

			if ( $pll->options['hide_default'] ) {
				if ( isset( $pll->options['default_lang'] ) ) {
					$pll_languages = array_diff( $pll_languages, array( $pll->options['default_lang'] ) );
				}
			}

			if ( ! empty( $pll_languages ) ) {
				$pll_languages = $wp_rewrite->root . ( $pll->options['rewrite'] ? '' : 'language/' ) . '(' . implode( '|', $pll_languages ) . ')/';
			}else{
				$pll_languages = '';
			}

		}
		$new_rules = array();
		foreach ( $rules as $k => $rule ) {
			$new_rules[] = $rule;
			call_user_func_array( 'add_rewrite_rule', $rule );

			/**
			 * Modify rewrite rule
			 */
			if ( isset( $pll_languages ) ) {

				$rule[0]     = $pll_languages . str_replace( $wp_rewrite->root, '', ltrim( $rule[0], '^' ) );
				$rule[1]     = str_replace(
					array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
					array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
					$rule[1]
				);
				$new_rules[] = $rule;
				call_user_func_array( 'add_rewrite_rule', $rule );
			}
		}

		$new_rules = md5( serialize( $new_rules ) );
		$old_rules = get_transient( 'lp_rewrite_rules_hash' );

		if ( $old_rules !== $new_rules ) {
			set_transient( 'lp_rewrite_rules_hash', $new_rules, DAY_IN_SECONDS );
			flush_rewrite_rules();
		}

		do_action( 'learn_press_add_rewrite_rules' );

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

		add_filter( 'posts_where', array( $this, 'exclude_preview_course' ) );
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
			$where     .= "OR $wpdb->terms.name LIKE '%{$escaped_s}%'";
		}

		return $where;
	}

	/**
	 * Exclude 'Preview course' from main query.
	 *
	 * @since 3.0.0
	 *
	 * @param string $where
	 *
	 * @return string
	 */
	public function exclude_preview_course( $where ) {
		global $wpdb;

		if ( ! is_admin() && learn_press_is_courses() ) {
			if ( $ids = LP_Preview_Course::get_preview_courses() ) {
				$format = array_fill( 0, sizeof( $ids ), '%d' );
				$where  .= $wpdb->prepare( " AND {$wpdb->posts}.ID NOT IN(" . join( ',', $format ) . ") ", $ids );
			}
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
}