<?php

/**
 * Class MaterialFilesModel
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.2
 */

namespace LearnPress\Models;

use Exception;
use LearnPress\Databases\MaterialFilesDB;
use LearnPress\Filters\MaterialFilesFilter;
use LP_WP_Filesystem;

defined( 'ABSPATH' ) || exit();

class MaterialFilesModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $file_id = 0;
	/**
	 * @var string File name
	 */
	public $file_name = '';
	/**
	 * @var string File type (extension)
	 */
	public $file_type = '';
	/**
	 * @var int Post ID (course or lesson)
	 */
	public $item_id = 0;
	/**
	 * @var string Post type (lp_course, lp_lesson...)
	 */
	public $item_type = '';
	/**
	 * @var string Save method: upload|external
	 */
	public $method = 'upload';
	/**
	 * @var string File path or URL
	 */
	public $file_path = '';
	/**
	 * @var int Display order
	 */
	public $orders = 0;
	/**
	 * @var string|null Created datetime
	 */
	public $created_at = null;

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}

	/**
	 * Map array/object data to MaterialFilesModel.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return MaterialFilesModel
	 */
	public function map_to_object( $data ): MaterialFilesModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get file id.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return (int) $this->file_id;
	}

	/**
	 * Get file name.
	 *
	 * @return string
	 */
	public function get_file_name(): string {
		return (string) $this->file_name;
	}

	/**
	 * Get file type.
	 *
	 * @return string
	 */
	public function get_file_type(): string {
		return (string) $this->file_type;
	}

	/**
	 * Get item id.
	 *
	 * @return int
	 */
	public function get_item_id(): int {
		return (int) $this->item_id;
	}

	/**
	 * Get item type.
	 *
	 * @return string
	 */
	public function get_item_type(): string {
		return (string) $this->item_type;
	}

	/**
	 * Get save method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return (string) $this->method;
	}

	/**
	 * Get file path or URL.
	 *
	 * @return string
	 */
	public function get_file_path(): string {
		return (string) $this->file_path;
	}

	/**
	 * Get display order.
	 *
	 * @return int
	 */
	public function get_orders(): int {
		return (int) $this->orders;
	}

	/**
	 * Get created datetime.
	 *
	 * @return string|null
	 */
	public function get_created_at(): ?string {
		return $this->created_at;
	}

	/**
	 * Get item model from database by filter.
	 *
	 * @param MaterialFilesFilter $filter
	 *
	 * @return static|false
	 * @throws Exception
	 */
	public static function get_item_model_from_db( MaterialFilesFilter $filter ) {
		$db = MaterialFilesDB::getInstance();
		$db->get_query_single_row( $filter );
		$query = $db->get_files( $filter );
		$row   = $db->wpdb->get_row( $query );

		if ( ! $row ) {
			return false;
		}

		return new static( $row );
	}

	/**
	 * Find a material file by ID.
	 *
	 * @param int $file_id
	 *
	 * @return static|false
	 * @throws Exception
	 */
	public static function find( int $file_id ) {
		if ( ! $file_id ) {
			return false;
		}

		$filter          = new MaterialFilesFilter();
		$filter->file_id = $file_id;

		return self::get_item_model_from_db( $filter );
	}

	/**
	 * Save material file to database.
	 * Insert if file_id is empty, update if file_id exists.
	 *
	 * @param bool $force_save Skip permission check if true.
	 *
	 * @return static
	 * @throws Exception
	 * @since 4.2.2
	 * @version 1.0.0
	 */
	public function save( bool $force_save = false ): MaterialFilesModel {
		if ( ! $force_save ) {
			$this->check_permission();
		}

		$db     = MaterialFilesDB::getInstance();
		$filter = new MaterialFilesFilter();
		$args   = array(
			'data'       => get_object_vars( $this ),
			'filter'     => $filter,
			'table_name' => $db->table_name,
		);

		if ( empty( $this->file_id ) ) {
			$args['key_auto_increment'] = MaterialFilesFilter::COL_FILE_ID;
			$this->file_id              = $db->insert_data( $args );
		} else {
			$args['where_key'] = MaterialFilesFilter::COL_FILE_ID;
			$db->update_data( $args );
		}

		return $this;
	}

	/**
	 * Delete material file from database.
	 *
	 * @param bool $force_delete Skip permission check if true.
	 *
	 * @throws Exception
	 * @since 4.2.2
	 * @version 1.0.0
	 */
	public function delete( bool $force_delete = false ): void {
		if ( ! $force_delete ) {
			$this->check_permission();
		}

		$db                 = MaterialFilesDB::getInstance();
		$filter             = new MaterialFilesFilter();
		$filter->collection = $db->table_name;
		$filter->where[]    = $db->wpdb->prepare( 'AND file_id = %d', $this->file_id );
		$db->delete_execute( $filter );

		if ( $this->method === 'upload' && ! empty( $this->file_path ) ) {
			$file_init = LP_WP_Filesystem::instance();
			$full_path = wp_upload_dir()['basedir'] . $this->file_path;
			if ( $file_init->file_exists( $full_path ) ) {
				$file_init->unlink( $full_path );
			}
		}
	}

	/**
	 * Check permission to create/update material file.
	 *
	 * @throws Exception
	 * @since 4.2.2
	 * @version 1.0.0
	 */
	public function check_permission(): void {
		$can_manage = true;
		if ( $this->item_type === LP_COURSE_CPT ) {
			if ( ! current_user_can( 'edit_' . LP_COURSE_CPT, $this->item_id ) ) {
				$can_manage = false;
			}
		} elseif ( $this->item_type === LP_LESSON_CPT ) {
			if ( ! current_user_can( 'edit_' . LP_LESSON_CPT, $this->item_id ) ) {
				$can_manage = false;
			}
		}

		if ( ! $can_manage ) {
			throw new Exception( __( 'You do not have permission to manage this material file!', 'learnpress' ) );
		}
	}
}
