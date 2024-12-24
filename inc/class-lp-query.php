<?php

/**
 * Class LP_Query
 *
 * @version 4.2.2.3
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
		add_action( 'init', array( $this, 'add_rewrite_endpoints' ) );
		add_filter( 'option_rewrite_rules', [ $this, 'update_option_rewrite_rules' ], 1 );
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
	 * Add custom rewrite tags
	 */
	public function add_rewrite_tags() {
		$tags = [
			'%course-item%'          => '([^&]+)',
			'%item-type%'            => '([^&]+)',
			'%question%'             => '([^&]+)',
			'%user%'                 => '([^/]*)',
			'%view%'                 => '([^/]*)',
			'%view_id%'              => '(.*)',
			'%section%'              => '(.*)',
			'%content-item-only%'    => '(.*)',
			'%is_single_instructor%' => '(.*)',
			'%instructor_name%'      => '(.*)',
		];

		$tags = apply_filters( 'learn-press/rewrite/tags', $tags );
		foreach ( $tags as $tag => $regex ) {
			add_rewrite_tag( $tag, $regex );
		}
	}

	/**
	 * Add custom rewrite endpoints
	 */
	public function add_rewrite_endpoints() {
		$settings = LP_Settings::instance();

		$endpoints = $settings->get_checkout_endpoints();
		if ( $endpoints ) {
			foreach ( $endpoints as $endpoint => $value ) {
				LearnPress::instance()->query_vars[ $endpoint ] = $value;
				add_rewrite_endpoint( $value, EP_PAGES );
			}
		}

		$endpoints = $settings->get_profile_endpoints();
		if ( $endpoints ) {
			foreach ( $endpoints as $endpoint => $value ) {
				LearnPress::instance()->query_vars[ $endpoint ] = $value;
				add_rewrite_endpoint( $value, EP_PAGES );
			}
		}

		$endpoints = $settings->get( 'quiz_endpoints' );
		if ( $endpoints ) {
			foreach ( $endpoints as $endpoint => $value ) {
				$endpoint                                       = preg_replace( '!_!', '-', $endpoint );
				LearnPress::instance()->query_vars[ $endpoint ] = $value;
				add_rewrite_endpoint(
					$value, /*EP_ROOT | */
					EP_PAGES
				);
			}
		}
	}

	/**
	 * Add lp rewrite rules
	 * Format [key_parent][single_key] = [rule => match]
	 *
	 * link item course
	 * link profile
	 * @return array
	 * @version 1.0.4
	 * @modify 4.2.2
	 * @since 3.0.0
	 */
	public function add_rewrite_rules(): array {
		$rules = array();

		try {
			$course_item_slugs = LP_Settings::get_course_items_slug();
			$course_slug       = LP_Settings::get_permalink_single_course();
			// For permalink has %course_category%
			if ( preg_match( '!%course_category%!', $course_slug ) ) {
				if ( ! preg_match( '!page!', LP_Helper::getUrlCurrent() ) ) {
					$course_slug = preg_replace( '!%course_category%!', '([^/]+)/([^/]+)', $course_slug );

					// Rule single course
					$rules['single-course-with-cat'][] = [
						"^{$course_slug}/?$" =>
							'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]',
					];

					// Rule single item
					foreach ( $course_item_slugs as $post_type => $course_item_slug ) {
						$rules['course-with-cat-items'][ $post_type ] = [
							"^{$course_slug}(?:/{$course_item_slug}/([^/]+))/?$" =>
								'index.php?' . LP_COURSE_CPT . '=$matches[2]&course_category=$matches[1]&course-item=$matches[3]&item-type=' . $post_type,
						];
					}
				}
			} else {
				// Rule single course
				$rules['single-course'][] = [
					"^{$course_slug}/([^/]+)/?$" =>
						'index.php?' . LP_COURSE_CPT . '=$matches[1]',
				];

				// Rule single item
				foreach ( $course_item_slugs as $post_type => $course_item_slug ) {
					$rules['course-items'][ $post_type ] = [
						"^{$course_slug}/([^/]+)(?:/{$course_item_slug}/([^/]+))/?$" =>
							'index.php?' . LP_COURSE_CPT . '=$matches[1]&course-item=$matches[2]&item-type=' . $post_type,
					];
				}
			}

			// Profile
			$this->add_rewrite_rules_profile( $rules );

			// Instructor detail
			$single_instructor_page_id       = learn_press_get_page_id( 'single_instructor' );
			$instructor_slug                 = get_post_field( 'post_name', $single_instructor_page_id );
			$rules['instructor']['has_name'] = [
				"^{$instructor_slug}/([^/]+)/?(?:page/)?([^/][0-9]*)?/?$" =>
					'index.php?page_id=' . $single_instructor_page_id . '&is_single_instructor=1&instructor_name=$matches[1]&paged=$matches[2]',
			];
			$rules['instructor']['no_name']  = [
				"^{$instructor_slug}/?$" =>
					'index.php?page_id=' . $single_instructor_page_id . '&is_single_instructor=1&paged=$matches[2]',
			];

			$rules = apply_filters( 'learn-press/rewrite/rules', $rules );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $rules;
	}

	/**
	 * Add rewrite rules for profile page.
	 *
	 * @param $rules
	 *
	 * @return void
	 */
	public function add_rewrite_rules_profile( &$rules ) {
		// Profile
		$profile_id = learn_press_get_page_id( 'profile' );
		if ( $profile_id ) {
			// Rule view profile of user (self or another)
			$page_profile_slug        = urldecode( get_post_field( 'post_name', $profile_id ) );
			$rules['profile']['user'] = [
				"^{$page_profile_slug}/([^/]*)/?$" =>
					"index.php?page_id={$profile_id}&user=" . '$matches[1]',
			];

			// Rule view profile of user (self or another) with tab
			$tabs = LP_Profile::get_tabs_arr();
			if ( $tabs ) {
				foreach ( $tabs as $tab_key => $args ) {
					$tab_slug                     = $args['slug'] ?? $tab_key;
					$rules['profile'][ $tab_key ] = [
						"^{$page_profile_slug}/([^/]*)/({$tab_slug})/?([0-9]*)/?$" =>
							'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&view_id=$matches[3]',
					];

					if ( ! empty( $args['sections'] ) ) {
						foreach ( $args['sections'] as $section_key => $section ) {
							$section_slug                     = $section['slug'] ?? $section_key;
							$rules['profile'][ $section_key ] = [
								"^{$page_profile_slug}/([^/]*)/({$tab_slug})/({$section_slug})/?([0-9]*)?$" =>
									'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&section=$matches[3]&view_id=$matches[4]',
							];
						}
					}
				}
			}

			apply_filters( 'learn-press/rewrite-rules/profile', $rules['profile'], $profile_id );
		}
	}

	/**
	 * @param string $join
	 *
	 * @return string
	 * @deprecated 4.2.6.6
	 */
	/*public function join_term( $join ) {
		global $wp_query, $wpdb;

		if ( ! empty( $wp_query->query_vars['s'] ) && ! is_admin() ) {
			if ( ! preg_match( '/' . $wpdb->term_relationships . '/', $join ) ) {
				$join .= "LEFT JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id ";
			}
			$join .= "LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id ";
			$join .= "LEFT JOIN $wpdb->terms ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id ";
		}

		return $join;
	}*/

	/**
	 * @param string $where
	 *
	 * @return string
	 * @deprecated 4.2.6.6
	 */
	/*public function add_tax_search( $where ) {
		global $wp_query, $wpdb;

		if ( ! empty( $wp_query->query_vars['s'] ) && ! is_admin() ) {
			$escaped_s = esc_sql( $wp_query->query_vars['s'] );
			$where    .= "OR $wpdb->terms.name LIKE '%{$escaped_s}%'";
		}

		return $where;
	}*/

	/**
	 * @param string $groupby
	 *
	 * @return string
	 * @deprecated 4.2.6.6
	 */
	/*public function tax_groupby( $groupby ) {
		global $wpdb;
		$groupby = "{$wpdb->posts}.ID";

		$this->remove_query_tax();

		return $groupby;
	}*/

	/**
	 * Remove filter query
	 * @deprecated 4.2.6.6
	 */
	/*public function remove_query_tax() {
		remove_filter( 'posts_where', 'learn_press_add_tax_search' );
		remove_filter( 'posts_join', 'learn_press_join_term' );
		remove_filter( 'posts_groupby', 'learn_press_tax_groupby' );
	}*/

	/**
	 * Clear cache rewrite rules when update option rewrite_rules
	 * Fixed for case: addons Certificates (v4.0.5), FE(4.0.5), Live(4.0.2), Collections(4.0.2) not installed on site client.
	 * Run only one time when reload page Frontend.
	 *
	 * @return mixed|array
	 * @since 4.2.2
	 * @version 1.0.2
	 * @see get_option() hook in this function.
	 */
	public function update_option_rewrite_rules( $wp_rules ) {
		// Check it is called from WP_Rewrite class
		$debug_backtrace = debug_backtrace();
		if ( ! isset( $debug_backtrace[4] )
			|| ! isset( $debug_backtrace[4]['class'] )
			|| $debug_backtrace[4]['class'] != WP_Rewrite::class ) {
			return $wp_rules;
		}

		static $handled = false;
		if ( $handled ) {
			return $wp_rules;
		}
		$handled = true;

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
}
