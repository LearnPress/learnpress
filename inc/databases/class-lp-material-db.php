<?php 
/**
 * Class LP_Material_Files_DB
 *
 * @author khanhbd
 * @version 1.0.0
 * @since 4.2.2
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Material_Files_DB' ) ) {
	return;
}
class LP_Material_Files_DB extends LP_Database {

	private static $_instance;
	public $table_name;
	protected function __construct() {
		parent::__construct();
		$this->table_name = $this->tb_lp_material_files;
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * [create_material create new material]
	 * @param  [array] $data [data to create a material: file name, file type, the post id, post type, save method, file path or file url, created date ]
	 * @return [int]  new record id(file_id)
	 */
	public function create_material( $data ) {
		if( ! is_array( $data ) ){
			return;
		}
		if( ! is_int( $data['item_id'] ) ){
			return;
		}
		$file_id = $this->wpdb->insert(
			$this->table_name,
			$data,
			array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
		$this->check_execute_has_error();
		return $file_id;
	}

	/**
	 * [get_single_material get a material]
	 * @param  [int] $file_id [file_id]
	 * @return [object||null] [A material or null]
	 */
	public function get_material( $file_id ) {
		if( ! is_int( $file_id ) ) {
			return;
		}
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE file_id = %d",
				$file_id
			)
		);
		$this->check_execute_has_error();
		return $row;
	}
	public function get_material_by_item_id( $item_id ) {
		if( ! is_int( $item_id ) ) {
			return;
		}
		$result = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE item_id = %d",
				$item_id
			)
		);
		$this->check_execute_has_error();
		return $result;
	}
	/**
	 * [delete_material delete a material]
	 * @param  [int] $file_id [file id]
	 * @return [boolean]          [description]
	 */
	public function delete_material( $file_id ){
		if( ! is_int( $file_id ) ) {
			return;
		}
		$delete = $this->wpdb->delete(
			$this->table_name,
			array( 'file_id' => $file_id ),
			array( '%d' )
		);
		$this->check_execute_has_error();
		return $delete;
	}
	/**
	 * [delete_material_by_item_id delete all material file of an item]
	 * @param  [int] $item_id [the post id]
	 * @return [boolean]          [description]
	 */
	public function delete_material_by_item_id( $item_id ) {
		if( ! is_int( $item_id ) ) {
			return;
		}
		$delete = $this->wpdb->delete(
			$this->table_name,
			array( 'item_id' => $item_id ),
			array( '%d' )
		);
		$this->check_execute_has_error();
		return $delete;
	}
	public function delete_local_file( $file_id ) {
		if( ! is_int( $file_id ) ) {
			return;
		}
		$file = $this->get_material( $file_id );
		if( ! $file || $file['method'] == 'external' ) {
			return;
		}
		$file_path = $file['file_path'];
		$upload_dir = wp_upload_dir();
		if( ! file_exists( $upload_dir['basedir'] . $file_path ) ){
			return;
		}
		unlink( $upload_dir['basedir'] . $file_path );
	}
}

LP_Material_Files_DB::getInstance();
 ?>