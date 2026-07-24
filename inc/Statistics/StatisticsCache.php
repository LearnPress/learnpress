<?php
/**
 * Class StatisticsCache
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use LP_Cache;

defined( 'ABSPATH' ) || exit();

/**
 * Short-lived cache for the heavy dashboard aggregates
 * (funnel, health checks, instructor performance, watchlist).
 *
 * Backed by LP_Cache (WP object cache + optional Thim persistent cache, the
 * same store LearnPress uses for its expensive course queries) under the
 * 'learn_press/statistics' group. Keyed by md5 of the bucket + its params;
 * TTL only, no explicit busting — data self-refreshes after the window.
 *
 * Set the TTL filter to 0 to bypass caching entirely (useful while profiling
 * or debugging queries).
 *
 * @since 4.4.2
 */
class StatisticsCache extends LP_Cache {
	/**
	 * @var string Cache group child → group 'learn_press/statistics'.
	 */
	protected $key_group_child = 'statistics';

	/**
	 * Persist through the Thim cache layer too, matching LearnPress' own
	 * heavy-query caching, so entries survive across requests where available.
	 */
	public function __construct( $has_thim_cache = true ) {
		parent::__construct( $has_thim_cache );
	}

	/**
	 * Cache lifetime in seconds. 0 (or negative) disables caching.
	 *
	 * @return int
	 */
	public static function ttl(): int {
		return (int) apply_filters( 'learn-press/statistics/cache-ttl', 300 );
	}

	/**
	 * Return the cached array for ( bucket, params ) or compute + store it.
	 *
	 * Only array payloads are trusted on read — a corrupt/false/scalar hit is
	 * treated as a miss and recomputed. All dashboard datasets are arrays.
	 *
	 * @param string   $bucket     Short logical name of the dataset, e.g. 'funnel'.
	 * @param array    $key_params Everything the result depends on (type, value, scope signature, flags).
	 * @param callable $callback   Produces the value on a cache miss.
	 * @return array
	 */
	public static function remember( string $bucket, array $key_params, callable $callback ): array {
		$ttl = self::ttl();
		if ( $ttl <= 0 ) {
			return (array) call_user_func( $callback );
		}

		$key   = $bucket . '_' . md5( (string) wp_json_encode( $key_params ) );
		$cache = new self();

		$cached = $cache->get_cache( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$value = (array) call_user_func( $callback );
		$cache->set_cache( $key, $value, $ttl );

		return $value;
	}
}
