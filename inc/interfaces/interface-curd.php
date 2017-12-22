<?php

/**
 * Interface LP_Interface_CURD
 */
interface LP_Interface_CURD {

	/**
	 * Create item and insert to database.
	 *
	 * @since 3.0.0
	 *
	 * @param object $object
	 */
	public function create( &$object );

	/**
	 * Load data from database.
	 *
	 * @since 3.0.0
	 *
	 * @param object $object
	 */
	public function load( &$object );

	/**
	 * Update data into database.
	 *
	 * @since 3.0.0
	 *
	 * @param object $object
	 */
	public function update( &$object );

	/**
	 * Delete data from database.
	 *
	 * @since 3.0.0
	 *
	 * @param object $object
	 */
	public function delete( &$object );

	/**
	 * Duplicate item and insert to database
	 *
	 * @param       $object
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function duplicate( &$object, $args = array() );

	/**
	 * Add new meta data.
	 *
	 * @since 3.0.0
	 *
	 * @param $object
	 * @param $meta
	 */
	public function add_meta( &$object, $meta );

	/**
	 * Read meta data for passed object.
	 *
	 * @since 3.0.0
	 *
	 * @param $object
	 */
	public function read_meta( &$object );

	/**
	 * Update meta data.
	 *
	 * @since 3.0.0
	 *
	 * @param $object
	 * @param $meta
	 */
	public function update_meta( &$object, $meta );

	/**
	 * Delete meta data.
	 *
	 * @since 3.0.0
	 *
	 * @param $object
	 * @param $meta
	 */
	public function delete_meta( &$object, $meta );

}

/**
 * Class LP_Object_Data_Meta
 */
class LP_Object_Data_CURD {

	/**
	 * @var string
	 */
	protected $_meta_type = 'post';

	/**
	 * Errors codes and message.
	 *
	 * @var bool
	 */
	protected $_error_messages = false;

	/**
	 * Add new meta data.
	 *
	 * @param $object
	 * @param $meta
	 */
	public function add_meta( &$object, $meta ) {
		// TODO: Implement add_meta() method.
	}

	/**
	 * Delete meta data.
	 *
	 * @since 3.0.0
	 *
	 * @param $object
	 * @param $meta
	 */
	public function delete_meta( &$object, $meta ) {
		// TODO: Implement delete_meta() method.
	}

	/**
	 * Read all meta data from DB.
	 *
	 * @param $object LP_Course|LP_Lesson|LP_Quiz|LP_Question
	 *
	 * @return array|null|object
	 */
	public function read_meta( &$object ) {
		global $wpdb;

		if ( false === ( $meta_data = wp_cache_get( $object->get_id(), 'object-meta' ) ) ) {
			$id_column        = ( 'user' == $this->_meta_type ) ? 'umeta_id' : 'meta_id';
			$object_id_column = $this->_meta_type . '_id';
			$table            = _get_meta_table( $this->_meta_type );

			$query     = $wpdb->prepare( "
				SELECT {$id_column} as meta_id, meta_key, meta_value
				FROM {$table}
				WHERE {$object_id_column} = %d
				ORDER BY {$id_column}
			", $object->get_id() );
			$meta_data = $wpdb->get_results( $query );

			wp_cache_set( $object->get_id(), $meta_data, 'object-meta' );
		}

		return $meta_data;
	}

	/**
	 * Update meta data.
	 *
	 * @since 3.0.0
	 *
	 * @param $object LP_Course|LP_Lesson|LP_Quiz|LP_Question
	 * @param $meta
	 */
	public function update_meta( &$object, $meta ) {
		update_metadata( $this->_meta_type, $object->get_id(), $meta->meta_key, $meta->meta_value );
	}

	/**
	 * @param $type
	 *
	 * @return mixed|LP_Object_Data_CURD
	 */
	public static function get( $type ) {
		static $curds = false;
		if ( ! $curds ) {
			$curds = array(
				'user'  => new LP_User_CURD(),
				'order' => new LP_Order_CURD(),
			);
		}

		return ! empty( $curds[ $type ] ) ? $curds[ $type ] : false;
	}

	/**
	 * Get WP_Object.
	 *
	 * @param $code
	 *
	 * @return bool|WP_Error
	 */
	protected function get_error( $code ) {
		if ( isset( $this->_error_messages[ $code ] ) ) {
			return new WP_Error( $code, $this->_error_messages[ $code ] );
		}

		return false;
	}
}