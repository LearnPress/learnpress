<?php

/**
 * @class LP_Polylang
 *
 * @since 4.1.5
 * @version 1.0.0
 * @author tungnx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Polylang {
	protected static $instance;

	protected function __construct() {
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

		if ( $lang_current != $lang_default ) {
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
	 * @return LP_Course_Filter
	 */
	public function filter_query_courses( LP_Course_Filter $filter ): LP_Course_Filter {
		$pll_current_lang = LP_Request::get_param( 'pll-current-lang' );
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
		$pll_current_lang = LP_Helper::sanitize_params_submitted( $_REQUEST['pll-current-lang'] ?? '' );

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

		$field_arr = [ 'courses_page_id', 'profile_page_id', 'checkout_page_id', 'become_a_teacher_page_id', 'term_conditions_page_id' ];
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
	 * @return LP_Polylang
	 */
	public static function instance(): LP_Polylang {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
