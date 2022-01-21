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
		add_filter( 'lp/template/archive-course/skeleton/args', [ $this, 'localize_script' ] );
		add_filter( 'lp/api/courses/filter', [ $this, 'filter_query_courses' ], 10, 2 );
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
	public function get_page_id( int $page_id = 0, string $name ): int {
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
	public function localize_script( array $args ): array {
		$args['pll-current-lang'] = pll_current_language();

		return $args;
	}

	/**
	 * Query get course by current language
	 *
	 * @param LP_Course_Filter $filter
	 * @param WP_REST_Request $request
	 *
	 * @return LP_Course_Filter
	 */
	public function filter_query_courses( LP_Course_Filter $filter, WP_REST_Request $request ): LP_Course_Filter {
		$pll_current_lang = $request['pll-current-lang'] ?? '';
		$lp_db            = LP_Database::getInstance();

		$filter->join[]  = "INNER JOIN $lp_db->tb_term_relationships AS r_term ON p.ID = r_term.object_id";
		$filter->join[]  = "INNER JOIN $lp_db->tb_terms AS term ON r_term.term_taxonomy_id = term.term_id";
		$filter->where[] = $lp_db->wpdb->prepare( 'AND term.slug = %s', $pll_current_lang );

		return $filter;
	}

	/**
	 * Create new fields of languages not default
	 */
	public function general_settings_fields( array $fields ): array {
		$default_lang = pll_default_language();
		$current_lang = pll_current_language();

		$field_arr = [ 'courses_page_id', 'profile_page_id', 'checkout_page_id', 'become_a_teacher_page_id', 'term_conditions_page_id' ];
		$field_arr = apply_filters( 'lp_pll/name-fields', $field_arr );

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

		if ( '' !== LP_Page_Controller::page_current() ) {
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
