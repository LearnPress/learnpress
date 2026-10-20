<?php

namespace LearnPress\Cache;

defined( 'ABSPATH' ) || exit();

/**
 * Manage cached LearnPress settings.
 *
 * @since 4.4.11
 * @version 1.0.0
 */
class LPSettingsCache extends LPCache {
	/**
	 * Child cache group appended to the parent group.
	 *
	 * @var string
	 */
	protected $key_group_child = '';

	/**
	 * Cache key for LearnPress settings.
	 *
	 * @var string
	 */
	public string $key_settings = 'settings';

	/**
	 * Store LearnPress settings in the cache.
	 *
	 * @since 4.4.11
	 *
	 * @param array $value LearnPress settings.
	 * @return bool Whether the settings were cached successfully.
	 */
	public function set_lp_settings( array $value = array() ): bool {
		return $this->set_cache( $this->key_settings, $value );
	}

	/**
	 * Retrieve LearnPress settings from the cache.
	 *
	 * @since 4.4.11
	 *
	 * @return array|false Cached settings, or false on a cache miss.
	 */
	public function get_lp_settings() {
		return $this->get_cache( $this->key_settings, 'array' );
	}

	/**
	 * Delete LearnPress settings from the cache.
	 *
	 * @since 4.4.11
	 *
	 * @return bool Whether the cached settings were deleted successfully.
	 */
	public function clean_lp_settings(): bool {
		return $this->clear( $this->key_settings );
	}
}
