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
	 * Register cache-invalidation hooks so a newly published course's author or a
	 * new/changed course category appears in the filter dropdowns promptly,
	 * instead of waiting out the transient TTL. Call once from the plugin bootstrap.
	 *
	 * @return void
	 * @since 4.4.2
	 */
	public static function register_flush_hooks(): void {
		// Publish/update/unpublish/trash a course → author list may change.
		add_action( 'save_post_' . LP_COURSE_CPT, array( __CLASS__, 'flush' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush_on_course_delete' ) );
		// Category taxonomy changes → category list may change.
		add_action( 'created_' . LP_COURSE_CATEGORY_TAX, array( __CLASS__, 'flush' ) );
		add_action( 'edited_' . LP_COURSE_CATEGORY_TAX, array( __CLASS__, 'flush' ) );
		add_action( 'delete_' . LP_COURSE_CATEGORY_TAX, array( __CLASS__, 'flush' ) );
	}

	/**
	 * Drop the cached options so the next request rebuilds them.
	 *
	 * @return void
	 * @since 4.4.2
	 */
	public static function flush(): void {
		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Flush only when the deleted post is a course ( deleted_post is global ).
	 *
	 * @param int $post_id
	 * @return void
	 * @since 4.4.2
	 */
	public static function flush_on_course_delete( $post_id ): void {
		if ( LP_COURSE_CPT === get_post_type( $post_id ) ) {
			self::flush();
		}
	}

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

		$options = array(
			'instructors' => self::get_instructors(),
			'categories'  => self::get_categories(),
		);

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
			return array();
		}

		$instructors = array();
		foreach ( $results as $row ) {
			$instructors[] = array(
				'id'   => (int) $row->id,
				'name' => (string) $row->name,
			);
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
			array(
				'taxonomy'   => LP_COURSE_CATEGORY_TAX,
				'hide_empty' => true,
				'fields'     => 'id=>name',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$categories = array();
		foreach ( $terms as $term_id => $name ) {
			$categories[] = array(
				'id'   => (int) $term_id,
				// Term names are entity-encoded in the DB; JS renders via textContent, so decode here.
				'name' => wp_specialchars_decode( (string) $name ),
			);
		}

		return $categories;
	}
}
