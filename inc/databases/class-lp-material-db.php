<?php
/**
 * Class LP_Material_Files_DB
 *
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
		$this->table_name = $this->tb_lp_files;
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [create_material create new material]
	 * @param  [array] $data [data to create a material: file name, file type, the post id, post type, save method, file path or file url, created date ]
	 * @return [int]  new record id(file_id)
	 */
	public function create_material( $data ) {
		if ( ! is_array( $data ) ) {
			return;
		}
		if ( ! is_int( $data['item_id'] ) ) {
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
				'%d',
				'%s',
			)
		);
		$this->check_execute_has_error();
		return $insert_file ? $this->wpdb->insert_id : false;
	}

	/**
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
	 * @version 1.0.0
	 * @since 4.2.2
	 * [get_material_by_item_id get all material files of a post( course or lesson )]
	 * @param  integer $item_id [post_id]
	 * @param  integer $per_page [file amount for each get files]
	 * @param  integer $offset  [query offset]
	 * @param  boolean $is_admin [check if is admin page, use for course to get only course's files ( don't include lesson's files )]
	 * @return [array]           [post's material files]
	 */
	public function get_material_by_item_id( $item_id = 0, $perpage = 0, $offset = 0, $is_admin = false ) {
		if ( ! is_int( $item_id ) ) {
			return;
		}
		$result = array();
		if ( get_post_type( $item_id ) == LP_COURSE_CPT && ! $is_admin ) {
			$sql = "SELECT * FROM $this->table_name WHERE item_id 
				IN ( SELECT si.item_id FROM $this->tb_lp_section_items AS si
				INNER JOIN $this->tb_lp_sections AS s ON s.section_id = si.section_id 
				WHERE s.section_course_id=%d ) 
				OR item_id=%d ORDER BY item_id, orders";
			if ( $perpage > 0 ) {
				$sql .= ' LIMIT ' . intval( $perpage );
			}
			if ( $offset > 0 && $perpage > 0 ) {
				$sql .= ' OFFSET ' . intval( $offset );
			}
			$result = $this->wpdb->get_results(
				$this->wpdb->prepare(
					$sql,
					$item_id,
					$item_id
				)
			);
		} else {
			$sql = "SELECT * FROM $this->table_name WHERE item_id = %d ORDER BY orders";
			if ( $perpage > 0 ) {
				$sql .= ' LIMIT ' . intval( $perpage );
			}
			if ( $offset > 0 && $perpage > 0 ) {
				$sql .= ' OFFSET ' . intval( $offset );
			}
			$result = $this->wpdb->get_results(
				$this->wpdb->prepare(
					$sql,
					$item_id
				)
			);
		}
		$this->check_execute_has_error();
		return $result;
	}

	/**
	 * [get_total get total file amount of an item]
	 * @param  [type] $item_id [description]
	 * @return [type]          [description]
	 */
	public function get_total( $item_id ) {
		if ( ! $item_id ) {
			return;
		}
		$item_id = (int) $item_id;
		if ( get_post_type( $item_id ) == LP_COURSE_CPT ) {
			$sql    = "SELECT COUNT(file_id) FROM $this->table_name WHERE item_id 
				IN ( SELECT si.item_id FROM $this->tb_lp_section_items AS si
				INNER JOIN $this->tb_lp_sections AS s ON s.section_id = si.section_id 
				WHERE s.section_course_id=%d ) 
				OR item_id=%d ORDER BY item_id";
			$result = $this->wpdb->get_var(
				$this->wpdb->prepare(
					$sql,
					$item_id,
					$item_id
				)
			);
		} else {
			$sql    = "SELECT COUNT(file_id) FROM $this->table_name WHERE item_id = %d";
			$result = $this->wpdb->get_var(
				$this->wpdb->prepare(
					$sql,
					$item_id
				)
			);
		}
		$this->check_execute_has_error();
		return (int) $result;
	}

	/**
	 * [update_material_orders update order of material]
	 * @param  array   $orders  [array or materials]
	 * @param  integer $item_id [item (course/lesson ID)]
	 * @return [type]           [update or false]
	 */
	public function update_material_orders( $orders = [], $item_id = 0 ) {
		if ( empty( $orders ) ) {
			return;
		}
		if ( ! $item_id ) {
			return;
		}
		$prepare_arr = [];
		$sql         = "UPDATE $this->table_name SET orders = (CASE ";
		foreach ( $orders as $id => $val ) {
			$sql          .= 'when file_id = %d then %d ';
			$prepare_arr[] = (int) $val['file_id'];
			$prepare_arr[] = (int) $val['orders'];
		}
		$prepare_arr[] = $item_id;
		$sql          .= 'END) ';
		$sql          .= 'WHERE item_id = %d';
		$update        = $this->wpdb->query( $this->wpdb->prepare( $sql, $prepare_arr ) );
		$this->check_execute_has_error();
		return $update ? $update : 0;
	}
	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [delete_material delete a material]
	 * @param  [int] $file_id [file id]
	 * @return [boolean]          [description]
	 */
	public function delete_material( $file_id = 0 ) {
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
			$file_path = wp_upload_dir()['basedir'] . $material->file_path;
			$this->delete_local_file( $file_path );
		}
		return $delete;
	}
	/**
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
					$file_path = wp_upload_dir()['basedir'] . $m->file_path;
					$this->delete_local_file( $file_path );
				}
			}
		}
		return $delete;
	}
	/**
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

