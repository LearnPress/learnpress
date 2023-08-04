<?php

/**
 * @class LP_Polylang
 *
 * @since 4.1.5
 * @version 1.0.0
 * @author tungnx
 */

use LearnPress\Helpers\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Polylang {
	use Singleton;

	public function init() {
		$this->hooks();
	}

	public function hooks() {
		add_filter( 'learn-press/general-settings-fields', [ $this, 'general_settings_fields' ], 10, 2 );
		add_filter( 'learn_press_get_page_id', [ $this, 'get_page_id' ], 10, 2 );
		add_filter( 'lp/template/archive-course/skeleton/args', [ $this, 'wpml_arg_query_course' ] );
		add_filter( 'lp/profile/args/user_courses_attend', [ $this, 'wpml_arg_query_course' ] );
		add_filter( 'lp/profile/args/user_courses_created', [ $this, 'wpml_arg_query_course' ] );
		add_filter( 'lp/profile/args/user_courses_statistic', [ $this, 'wpml_arg_query_course' ] );
		add_filter( 'learnpress/rest/frontend/profile/course_tab/query', [ $this, 'args_query_user_courses' ], 10, 2 );
		add_filter( 'lp/course/query/filter', [ $this, 'filter_query_courses' ], 10 );
		add_filter( 'lp/user/course/query/filter', [ $this, 'filter_query_user_courses' ], 10 );
		add_filter( 'pll_the_language_link', [ $this, 'get_link_switcher' ], 10, 3 );
		add_filter( 'learn-press/rewrite/rules', [ $this, 'pll_rewrite_rules' ] );
	}

	/**
	 * Get page_id config on LP Settings
	 *
	 * @param int $page_id
	 * @param string $name
	 *
	 * @return int
	 */
	public function get_page_id( int $page_id = 0, string $name = '' ): int {
		if ( ! is_callable( 'pll_default_language' ) ) {
			return $page_id;
		}

		$lang_default = pll_default_language();
		$lang_current = pll_current_language();
		if ( ! $lang_current ) {
			$lang_current = LP_Request::get_param( 'pll-current-lang' ); // Param this send via lp_archive_skeleton_get_args
		}

		if ( $lang_current && $lang_current != $lang_default ) {
			$page_id = absint( LP_Settings::get_option( "{$name}_page_id_{$lang_current}" ) );
		}

		return $page_id;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function wpml_arg_query_course( array $args ): array {
		if ( ! function_exists( 'pll_current_language' ) ) {
			return $args;
		}

		$args['pll-current-lang'] = pll_current_language();

		return $args;
	}

	/**
	 * Query get course by current language
	 *
	 * @param LP_Course_Filter $filter
	 *
	 * @since 4.1.4
	 * @version 1.0.1
	 * @return LP_Course_Filter
	 */
	public function filter_query_courses( LP_Course_Filter $filter ): LP_Course_Filter {
		$pll_current_lang = pll_current_language(); // For query load page
		if ( ! $pll_current_lang ) { // For query call API
			$pll_current_lang = LP_Request::get_param( 'pll-current-lang' );
		}

		if ( empty( $pll_current_lang ) ) {
			return $filter;
		}

		$lp_db          = LP_Database::getInstance();
		$filter->join[] = "INNER JOIN $lp_db->tb_term_relationships AS r_term ON p.ID = r_term.object_id";
		$filter->join[] = "INNER JOIN $lp_db->tb_terms AS term ON r_term.term_taxonomy_id = term.term_id";
		if ( empty( LP_Request::get_param( 'term_id' ) ) ) {
			$filter->where[] = $lp_db->wpdb->prepare( 'AND term.slug = %s', $pll_current_lang );
		}

		return $filter;
	}

	/**
	 * Profile filter query get user course by current language
	 *
	 * @param LP_User_Items_Filter $filter
	 *
	 * @return LP_User_Items_Filter
	 */
	public function filter_query_user_courses( LP_User_Items_Filter $filter ): LP_User_Items_Filter {
		$pll_current_lang = pll_current_language(); // For query load page
		if ( ! $pll_current_lang ) { // For query call API
			$pll_current_lang = LP_Request::get_param( 'pll-current-lang' );
		}

		if ( empty( $pll_current_lang ) ) {
			return $filter;
		}

		$lp_db = LP_Database::getInstance();

		$filter->join[]  = "INNER JOIN $lp_db->tb_term_relationships AS r_term ON ui.item_id = r_term.object_id";
		$filter->join[]  = "INNER JOIN $lp_db->tb_terms AS term ON r_term.term_taxonomy_id = term.term_id";
		$filter->where[] = $lp_db->wpdb->prepare( 'AND term.slug = %s', $pll_current_lang );

		return $filter;
	}

	/**
	 * Profile filter query get course by current language
	 *
	 * @param LP_Course_Filter $filter
	 * @param array $args
	 *
	 * @return LP_Course_Filter
	 */
	/*public function profile_filter_query_courses( LP_Course_Filter $filter, array $args ): LP_Course_Filter {
		$pll_current_lang = $args['pll-current-lang'] ?? '';

		if ( empty( $pll_current_lang ) ) {
			return $filter;
		}

		$lp_db = LP_Database::getInstance();

		$filter->join[]  = "INNER JOIN $lp_db->tb_term_relationships AS r_term ON p.ID = r_term.object_id";
		$filter->join[]  = "INNER JOIN $lp_db->tb_terms AS term ON r_term.term_taxonomy_id = term.term_id";
		$filter->where[] = $lp_db->wpdb->prepare( 'AND term.slug = %s', $pll_current_lang );

		return $filter;
	}*/

	/**
	 * Args get course by current language
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function args_query_user_courses( array $args, WP_REST_Request $request ): array {
		$pll_current_lang = LP_Helper::sanitize_params_submitted( $request['pll-current-lang'] ?? '' );

		if ( empty( $pll_current_lang ) ) {
			return $args;
		}

		$args['pll-current-lang'] = $pll_current_lang;

		return $args;
	}

	/**
	 * Create new fields of languages not default
	 */
	public function general_settings_fields( array $fields ): array {
		$default_lang = pll_default_language();
		$current_lang = pll_current_language();

		$field_arr = [ 'courses_page_id', 'profile_page_id', 'checkout_page_id', 'become_a_teacher_page_id', 'term_conditions_page_id', 'instructors_page_id', 'single_instructor_page_id' ];
		$field_arr = apply_filters( 'lp_pll/settings/name-fields', $field_arr );

		if ( $default_lang != $current_lang ) {
			// Set name field with lang
			foreach ( $fields as $k => $field ) {
				if ( isset( $field['id'] ) && in_array( $field['id'], $field_arr ) ) {
					$fields[ $k ]['id'] = $field['id'] . '_' . $current_lang;
				}
			}
		}

		return $fields;
	}

	/**
	 * Handle link swither
	 *
	 * @param $url
	 * @param $slug
	 * @param $locale
	 *
	 * @since 4.1.5
	 * @version 1.0.0
	 * @return false|mixed|string
	 */
	public function get_link_switcher( $url, $slug, $locale ) {
		$slug_lang = '';
		if ( $slug !== pll_default_language() ) {
			$slug_lang = '_' . $slug;
		}

		$arr_page = array( LP_PAGE_COURSES, LP_PAGE_PROFILE );
		if ( in_array( LP_Page_Controller::page_current(), $arr_page ) ) {
			$name_page = str_replace( 'lp_page_', '', LP_Page_Controller::page_current() );
			$url       = get_permalink( LP_Settings::get_option( $name_page . '_page_id' . $slug_lang ) );
		}

		return $url;
	}

	/**
	 * Rewrite url with polylang
	 *
	 * @param array $rules
	 *
	 * @uses LP_Query::add_rewrite_rules
	 * @since 4.2.3.3
	 * @version 1.0.0
	 * @return array
	 */
	public function pll_rewrite_rules( array $rules ): array {
		if ( ! is_callable( 'pll_default_language' ) || ! is_callable( 'pll_current_language' ) ) {
			return $rules;
		}

		$rules_old = $rules;

		$lang_default = pll_default_language();
		$pll_options  = $this->get_pll_options();
		$all_lang     = pll_languages_list();
		foreach ( $all_lang as $lang ) {
			$lang_slug = '';
			if ( 0 == $pll_options['force_lang'] ) { // Pll not change link by language

			} elseif ( 1 == $pll_options['force_lang'] ) { // Pll add slug language to link
				if ( $pll_options['rewrite'] == 0 ) {
					$lang_slug .= 'language/';
				}

				$lang_slug .= $lang . '/';
			}
			$lang_get_option = '';
			if ( $lang != $lang_default ) {
				$lang_get_option = '_' . $lang;
			}

			// Add language to rewrite url exits
			foreach ( $rules_old as $key_group => $group_rule ) {
				foreach ( $group_rule as $key => $rule ) {
					$new_key                                   = array_key_first( $rule );
					$value                                     = $rule[ $new_key ];
					$new_key                                   = substr_replace( $new_key, '', 0, 1 );
					$new_key                                   = '^' . $lang_slug . $new_key;
					$rules[ $key_group ][ $key . '_' . $lang ] = [ $new_key => $value . '&lang=' . $lang ];
				}
			}

			// Rewrite url for courses
			// Not use learn_press_get_page_id( 'courses' ) because it $lang_current = false with $pll_options['force_lang'] = 0
			$page_id_lang = LP_Settings::get_option( 'courses_page_id' . $lang_get_option, false );
			if ( $page_id_lang ) {
				$page = get_post( $page_id_lang );
				$slug = $page->post_name;

				$rules['courses'][ 'pll-archive-' . $lang ] = [
					"^{$lang_slug}{$slug}/?(?:page/)?([^/][0-9]*)?/?$" =>
						'index.php?paged=$matches[1]&post_type=' . LP_COURSE_CPT . '&lang=' . $lang,
				];
			}

			// Rewrite url for instructors
			$instructors_page_id_lang = LP_Settings::get_option( 'instructors_page_id' . $lang_get_option, false );
			if ( $instructors_page_id_lang ) {
				$page = get_post( $page_id_lang );
				$slug = $page->post_name;

				$rules['instructors']['pll-archive-option-0'] = [
					"^{$lang_slug}{$slug}/?(?:page/)?([^/][0-9]*)?/?$" =>
						'index.php?page_id=' . $page_id_lang . '&lang=' . $lang,
				];
			}

			// Rewrite url for instructor
			$single_instructor_page_id = LP_Settings::get_option( 'single_instructor_page_id' . $lang_get_option, false );
			if ( $page_id_lang ) {
				$page            = get_post( $single_instructor_page_id );
				$instructor_slug = $page->post_name;

				$rules['instructor'][ 'has_name_' . $lang ] = [
					"^{$lang_slug}{$instructor_slug}/([^/]+)/?(?:page/)?([^/][0-9]*)?/?$" =>
						'index.php?page_id=' . $single_instructor_page_id . '&is_single_instructor=1&instructor_name=$matches[1]&paged=$matches[2]&lang=' . $lang,
				];
				$rules['instructor'][ 'no_name_' . $lang ]  = [
					"^{$lang_slug}{$instructor_slug}/?$" =>
						'index.php?page_id=' . $single_instructor_page_id . '&is_single_instructor=1&paged=$matches[2]&lang=' . $lang,
				];
			}
		}

		return apply_filters( 'learn-press/polylang-rewrite-url', $rules );
	}

	/**
	 * Get polylang settings
	 *
	 * @since 4.2.3.3
	 * @version 1.0.0
	 * @return array
	 */
	public function get_pll_options(): array {
		return is_array( PLL()->options ) ? PLL()->options : [];
	}
}
