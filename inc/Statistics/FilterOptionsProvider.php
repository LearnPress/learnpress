<?php
/**
 * Class FilterOptionsProvider
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

defined( 'ABSPATH' ) || exit();

/**
 * Options for the global statistics filters: instructor and category dropdowns.
 *
 * @since 4.4.2
 */
class FilterOptionsProvider {
	const TRANSIENT_KEY = 'lp_statistics_filter_options';

	/**
	 * Get dropdown options, cached in a transient.
	 *
	 * Always returns both keys with arrays (possibly empty) — the JS iterates blindly.
	 *
	 * @return array [ 'instructors' => [ [ 'id' => int, 'name' => string ] ], 'categories' => [ [ 'id' => int, 'name' => string ] ] ]
	 */
	public static function get_options(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) && isset( $cached['instructors'], $cached['categories'] ) ) {
			return $cached;
		}

		$options = [
			'instructors' => self::get_instructors(),
			'categories'  => self::get_categories(),
		];

		$ttl = (int) apply_filters( 'learn-press/statistics/filter-options-ttl', 600 );
		set_transient( self::TRANSIENT_KEY, $options, $ttl );

		return $options;
	}

	/**
	 * Users having at least one published course.
	 *
	 * Author-based on purpose (no role check): StatisticsScope filters on
	 * post_author, so every author of a published course must be selectable.
	 * Only id + display_name are exposed — no emails/user_login (privacy).
	 *
	 * @return array [ [ 'id' => int, 'name' => string ] ] sorted by name.
	 */
	private static function get_instructors(): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT u.ID AS id, u.display_name AS name
				FROM {$wpdb->users} AS u
				INNER JOIN {$wpdb->posts} AS p ON p.post_author = u.ID
				WHERE p.post_type = %s
				AND p.post_status = %s
				ORDER BY u.display_name ASC",
				LP_COURSE_CPT,
				'publish'
			)
		);

		if ( ! is_array( $results ) ) {
			return [];
		}

		$instructors = [];
		foreach ( $results as $row ) {
			$instructors[] = [
				'id'   => (int) $row->id,
				'name' => (string) $row->name,
			];
		}

		return $instructors;
	}

	/**
	 * Non-empty course categories.
	 *
	 * @return array [ [ 'id' => int, 'name' => string ] ] sorted by name.
	 */
	private static function get_categories(): array {
		$terms = get_terms(
			[
				'taxonomy'   => LP_COURSE_CATEGORY_TAX,
				'hide_empty' => true,
				'fields'     => 'id=>name',
			]
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		$categories = [];
		foreach ( $terms as $term_id => $name ) {
			$categories[] = [
				'id'   => (int) $term_id,
				// Term names are entity-encoded in the DB; JS renders via textContent, so decode here.
				'name' => wp_specialchars_decode( (string) $name ),
			];
		}

		return $categories;
	}
}
