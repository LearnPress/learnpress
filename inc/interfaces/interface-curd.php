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
	 * Read meta data for passed object.
	 *
	 * @since 3.0.0
	 *
	 * @param $object
	 */
	public function read_meta( &$object );

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

	public function duplicate( $order_id ) {
		$order = learn_press_get_order( $order_id );
	}

	public function add_meta( &$object, $meta ) {
		// TODO: Implement add_meta() method.
	}

	public function delete_meta( &$object, $meta ) {
		// TODO: Implement delete_meta() method.
	}

	/**
	 * Read all meta data from DB.
	 *
	 * @param $object
	 *
	 * @return array|null|object
	 */
	public function read_meta( &$object ) {
		global $wpdb;

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

		return $meta_data;
	}

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
}