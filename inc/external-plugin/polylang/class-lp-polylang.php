<?php

/**
 * @class LP_Polylang
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
		add_filter( 'lp/template/archive-course/skeleton/args', [ $this, 'localize_script' ] );
		add_filter( 'lp/rest-api/frontend/course/archive_course/query_args', [ $this, 'query_courses' ], 10, 2 );
		add_filter( 'learn-press/general-settings-fields', [ $this, 'general_settings_fields' ], 10, 2 );
		//add_filter( 'learn-press/get-page-link', [ $this, 'get_page_link' ], 10, 3 );
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
	 * @param array $args
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function query_courses( array $args, WP_REST_Request $request ): array {
		$pll_current_lang = $request['pll-current-lang'] ?? '';

		if ( ! empty( $pll_current_lang ) ) {
			$args_query_pll = [
				'taxonomy' => 'language',
				'field'    => 'slug',
				'terms'    => $pll_current_lang,
			];

			if ( ! isset( $args['tax_query'] ) ) {
				$args['tax_query'] = [ $args_query_pll ];
			} else {
				$args['tax_query']['relation'] = 'AND';
				$args['tax_query'][]           = $args_query_pll;
			}
		}

		return $args;
	}

	/**
	 * Create new fields of languages not default
	 */
	public function general_settings_fields( array $fields ): array {
		$default_lang = pll_default_language();
		$current_lang = pll_current_language();

		if ( $default_lang != $current_lang ) {
			// Set name field with lang for set "page id archive courses"
			$fields[1]['id'] = $fields[1]['id'] . '_' . $current_lang;
		}

		return $fields;
	}

	/**
	 * Get permalink archive courses page
	 *
	 * @param string $permalink
	 * @param int $page_id
	 * @param string $key
	 *
	 * @return string
	 */
	public function get_page_link( string $permalink, int $page_id, string $key ): string {
		if ( 'courses' === $key ) {
			$default_lang = pll_default_language();
			$current_lang = pll_current_language();

			if ( $default_lang != $current_lang ) {
				// Get option by current lang
				$archive_course_id_lang = LP_Settings::get_option( $key . '_page_id_' . $current_lang );
				$permalink              = trailingslashit( get_permalink( $archive_course_id_lang ) );
			}
		}

		return $permalink;
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
