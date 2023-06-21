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
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [create_material create new material]
	 * @param  [array] $data [data to create a material: file name, file type, the post id, post type, save method, file path or file url, created date ]
	 * @return [int]  new record id(file_id)
	 */
	public function create_material( $data ) {
		if ( ! is_array( $data ) ){
			return;
		}
		if ( ! is_int( $data['item_id'] ) ){
			return;
		}
		$insert_file = $this->wpdb->insert(
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
		return $insert_file ? $this->wpdb->insert_id : false;
	}

	/**
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [get_single_material get a material]
	 * @param  [int] $file_id [file_id]
	 * @return [object||null] [A material or null]
	 */
	public function get_material( $file_id = 0 ) {
		if ( ! is_int( $file_id ) ) {
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
	/**
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [get_material_by_item_id get all material files of a post( course or lesson )]
	 * @param  integer $item_id [post_id]
	 * @return [array]           [post's material files]
	 */
	public function get_material_by_item_id( $item_id = 0 ) {
		if ( ! is_int( $item_id ) ) {
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
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [get_course_materials get all material files of course( include material files of lessons )]
	 * @param  [integer] $course_id [description]
	 * @return [array]            [description]
	 */
	public function get_course_materials( $course_id ) {
		if ( ! is_int( $course_id ) ) {
			return;
		}

		$sql = "SELECT * FROM $this->table_name WHERE item_id 
			IN ( SELECT si.item_id FROM $this->tb_lp_section_items AS si
			INNER JOIN $this->tb_lp_sections AS s ON s.section_id = si.section_id 
			WHERE s.section_course_id=%d ) 
			OR item_id=%d ORDER BY item_id";
		$result = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $course_id, $course_id ) );
		$this->check_execute_has_error();
		return $result;
	}
	/**
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [delete_material delete a material]
	 * @param  [int] $file_id [file id]
	 * @return [boolean]          [description]
	 */
	public function delete_material( $file_id = 0 ){
		if ( ! is_int( $file_id ) ) {
			return;
		}
		$material = $this->get_material( $file_id );
		if ( ! $material ) {
			return;
		}
		$delete = $this->wpdb->delete(
			$this->table_name,
			array( 'file_id' => $file_id ),
			array( '%d' )
		);
		$this->check_execute_has_error();

		if ( $material->method == 'upload' && $delete ) {
			$file_path = wp_upload_dir()['basedir'].$material->file_path;
			$this->delete_local_file( $file_path );
		}
		return $delete;
	}
	/**
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [delete_material_by_item_id delete all material file of an item]
	 * @param  [int] $item_id [the post id]
	 * @return [boolean]          [description]
	 */
	public function delete_material_by_item_id( $item_id = 0 ) {
		if ( ! is_int( $item_id ) ) {
			return;
		}
		$materials = $this->get_material_by_item_id( $item_id );
		if ( ! $materials ) {
			return;
		}
		$delete = $this->wpdb->delete(
			$this->table_name,
			array( 'item_id' => $item_id ),
			array( '%d' )
		);
		$this->check_execute_has_error();
		if ( $delete ) {
			foreach ( $materials as $m ) {
				if ( $m->method == 'upload' ) {
					$file_path = wp_upload_dir()['basedir'].$m->file_path;
					$this->delete_local_file( $file_path );
				}
			}
		}
		return $delete;
	}
	/**
	 * @author khanhbd
	 * @version 1.0.0
	 * @since 4.2.2
	 * [delete_local_file delete file when record is deleted]
	 * @param  string $file_path [description]
	 */
	public function delete_local_file( $file_path = '' ) {
		$file_init = LP_WP_Filesystem::instance();
		if ( $file_init->file_exists( $file_path ) ) {
			$file_init->unlink( $file_path );
		}
	}
}

LP_Material_Files_DB::getInstance();
 ?>