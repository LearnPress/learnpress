<?php

namespace LearnPress\Databases;

use LearnPress\Filters\MaterialFilter;
use Exception;
use LP_Helper;

defined( 'ABSPATH' ) || exit();

/**
 * Class MaterialFilesDB
 *
 * PSR-4 style database class for learnpress_files table.
 * Refactored from LP_Material_Files_DB.
 *
 * @since 4.2.9.3
 * @version 1.0.0
 */
class MaterialFilesDB extends DataBase {

	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return MaterialFilesDB
	 */
	public static function getInstance(): self {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Create a new material record.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param array $data {
	 *     @type string $file_name   File name
	 *     @type string $file_type   File extension/type
	 *     @type int    $item_id     Post ID (course or lesson)
	 *     @type string $item_type   Post type
	 *     @type string $method      'upload' or 'external'
	 *     @type string $file_path   File path or URL
	 *     @type int    $orders      Sort order
	 *     @type string $created_at Creation timestamp
	 * }
	 * @return int|false Insert ID on success, false on failure.
	 */
	public function create_material( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}
		if ( ! is_int( $data['item_id'] ) ) {
			return false;
		}

		$insert_file = $this->wpdb->insert(
			$this->tb_lp_files,
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
	 * Get a single material by ID.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param int $file_id File ID.
	 * @return object|null Material row or null.
	 */
	public function get_material( $file_id = 0 ) {
		if ( ! is_int( $file_id ) ) {
			return null;
		}

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM $this->tb_lp_files WHERE file_id = %d",
				$file_id
			)
		);
		$this->check_execute_has_error();

		return $row;
	}

	/**
	 * Get all material files of a post (course or lesson).
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param int     $item_id  Post ID.
	 * @param int     $perpage  Items per page (0 = all).
	 * @param int     $offset   Query offset.
	 * @param bool    $is_admin Whether called from admin (for courses, exclude lesson files).
	 * @return array Array of material objects.
	 */
	public function get_material_by_item_id( $item_id = 0, $perpage = 0, $offset = 0, $is_admin = false ) {
		if ( ! is_int( $item_id ) ) {
			return array();
		}

		$result = array();

		if ( get_post_type( $item_id ) == LP_COURSE_CPT && ! $is_admin ) {
			$sql = "SELECT * FROM $this->tb_lp_files WHERE item_id
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
			$sql = "SELECT * FROM $this->tb_lp_files WHERE item_id = %d ORDER BY orders";

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
	 * Get total file count for an item.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param int $item_id Post ID.
	 * @return int Total count.
	 */
	public function get_total( $item_id ) {
		if ( ! $item_id ) {
			return 0;
		}

		$item_id = (int) $item_id;

		if ( get_post_type( $item_id ) == LP_COURSE_CPT ) {
			$sql    = "SELECT COUNT(file_id) FROM $this->tb_lp_files WHERE item_id
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
			$sql    = "SELECT COUNT(file_id) FROM $this->tb_lp_files WHERE item_id = %d";
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
	 * Update sort order of materials.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param array $orders  Array of [file_id => ['file_id' => int, 'orders' => int]].
	 * @param int   $item_id Post ID.
	 * @return int|false Number of rows updated or false.
	 */
	public function update_material_orders( $orders = array(), $item_id = 0 ) {
		if ( empty( $orders ) ) {
			return false;
		}
		if ( ! $item_id ) {
			return false;
		}

		$prepare_arr = array();
		$sql         = "UPDATE $this->tb_lp_files SET orders = (CASE ";

		foreach ( $orders as $id => $val ) {
			$sql          .= 'when file_id = %d then %d ';
			$prepare_arr[] = (int) $val['file_id'];
			$prepare_arr[] = (int) $val['orders'];
		}

		$prepare_arr[] = $item_id;
		$sql          .= 'END) ';
		$sql          .= 'WHERE item_id = %d';

		$update = $this->wpdb->query( $this->wpdb->prepare( $sql, $prepare_arr ) );
		$this->check_execute_has_error();

		return $update ? $update : 0;
	}

	/**
	 * Delete a material by ID.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param int $file_id File ID.
	 * @return int|false Number of rows deleted or false.
	 */
	public function delete_material( $file_id = 0 ) {
		if ( ! is_int( $file_id ) ) {
			return false;
		}

		$material = $this->get_material( $file_id );
		if ( ! $material ) {
			return false;
		}

		$delete = $this->wpdb->delete(
			$this->tb_lp_files,
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
	 * Delete all materials for an item.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param int $item_id Post ID.
	 * @return int|false Number of rows deleted or false.
	 */
	public function delete_material_by_item_id( $item_id = 0 ) {
		if ( ! is_int( $item_id ) ) {
			return false;
		}

		$materials = $this->get_material_by_item_id( $item_id );
		if ( ! $materials ) {
			return false;
		}

		$delete = $this->wpdb->delete(
			$this->tb_lp_files,
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
	 * Get material files using MaterialFilter
	 *
	 * @param MaterialFilter $filter
	 * @param int $total_rows return total_rows
	 *
	 * @return array|int|string|null
	 * @throws Exception
	 * @since 4.3.4
	 * @version 1.0.0
	 */
	public function get_files( MaterialFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_files;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'mf';
		}

		$ca = $filter->collection_alias;

		// File ID
		if ( ! empty( $filter->file_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.file_id = %d", $filter->file_id );
		}

		// File name
		if ( ! empty( $filter->file_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.file_name LIKE %s", '%' . $filter->file_name . '%' );
		}

		// File type
		if ( ! empty( $filter->file_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.file_type = %s", $filter->file_type );
		}

		// Item ID
		if ( ! empty( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.item_id = %d", $filter->item_id );
		}

		// Item IDs
		if ( ! empty( $filter->item_ids ) ) {
			$filter->item_ids = array_map( 'absint', $filter->item_ids );
			$item_ids_format  = join( ',', $filter->item_ids );
			$filter->where[]  = "AND $ca.item_id IN ($item_ids_format)";
		}

		// Item type
		if ( ! empty( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.item_type = %s", $filter->item_type );
		}

		// Method
		if ( ! empty( $filter->method ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.method = %s", $filter->method );
		}

		// Default ordering
		if ( empty( $filter->order_by ) ) {
			$filter->order_by = "$ca.item_id, $ca.orders";
			$filter->order    = 'ASC';
		}

		$filter = apply_filters( 'lp/material-files/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Delete a local file from the filesystem.
	 *
	 * @since 4.2.2
	 * @version 1.0.0
	 *
	 * @param string $file_path Absolute file path.
	 */
	public function delete_local_file( $file_path = '' ) {
		$file_init = LP_WP_Filesystem::instance();
		if ( $file_init->file_exists( $file_path ) ) {
			$file_init->unlink( $file_path );
		}
	}
}
