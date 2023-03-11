<?php

/**
 * Class LP_Course_Cache
 *
 * @author tungnx
 * @since 4.0.9
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_User_Items_Cache extends LP_Cache {
	protected $key_group_child = 'user-items';

	/**
	 * @param $has_thim_cache bool
	 */
	public function __construct( $has_thim_cache = false ) {
		parent::__construct( $has_thim_cache );
	}

	public function set_user_item( $keys = [], string $value = '' ) {
		$key = implode( '/', $keys );
		$this->set_cache( $key, $value );
	}

	public function get_user_item( $keys = [] ) {
		$key = implode( '/', $keys );
		$rs  = LP_Cache::cache_load_first( 'get', $key );
		if ( false !== $rs ) {
			return $rs;
		}

		$rs = $this->get_cache( $key );
		LP_Cache::cache_load_first( 'set', $key, $rs );

		return $rs;
	}

	public function clean_user_item( $keys = [] ) {
		$key = implode( '/', $keys );
		$this->clear( $key );
	}

	/**
	 * For cache clear all user items has course_id
	 *
	 * @param $course_id int
	 *
	 * @return bool|int|mysqli_result|resource|null
	 * @throws Exception
	 */
	public function clean_user_items_by_course( int $course_id = 0 ) {
		$thim_cache_db = Thim_Cache_DB::instance();
		$query         = $thim_cache_db->wpdb->prepare(
			"DELETE FROM {$thim_cache_db->table_name}
				WHERE key_cache REGEXP %s",
			"learn_press/user-items/[0-9]*/{$course_id}/" . LP_COURSE_CPT
		);

		$rs = $thim_cache_db->wpdb->query( $query );

		if ( $thim_cache_db->wpdb->last_error ) {
			throw new Exception( $thim_cache_db->wpdb->last_error );
		}

		return $rs;
	}
}
