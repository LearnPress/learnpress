<?php

/**
 * Class MaterialFileService
 *
 * @package LearnPress\Services
 * @since 4.3.5
 * @version 1.0.0
 */

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\MaterialFilesDB;
use LearnPress\Filters\MaterialFilesFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\MaterialFilesModel;

defined( 'ABSPATH' ) || exit();

class MaterialFileService {
	use Singleton;

	public function init() {
	}

	/**
	 * Get list of material files by filter.
	 *
	 * @param MaterialFilesFilter $filter
	 * @param int                 $total_rows
	 *
	 * @return MaterialFilesModel[]
	 * @throws Exception
	 * @since 4.2.2
	 * @version 1.0.0
	 */
	public function get_files( MaterialFilesFilter $filter, int &$total_rows = 0 ): array {
		$db   = MaterialFilesDB::getInstance();
		$rows = $db->get_files( $filter, $total_rows );

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				return new MaterialFilesModel( $row );
			},
			$rows
		);
	}

	/**
	 * Create a new material file record.
	 *
	 * @param array $data {
	 *   @type string $file_name  File display name.
	 *   @type string $file_type  File extension: pdf, doc, mp4...
	 *   @type int    $item_id    (Required) ID of the course or lesson.
	 *   @type string $item_type  Post type: lp_course | lp_lesson.
	 *   @type string $method     Save method: upload | external.
	 *   @type string $file_path  Relative path (upload) or full URL (external).
	 *   @type int    $orders     Display order, default 0.
	 *   @type string $created_at Datetime Y-m-d H:i:s, auto-set if omitted.
	 * }
	 *
	 * @return MaterialFilesModel
	 * @throws Exception
	 * @since 4.2.2
	 * @version 1.0.0
	 */
	public function create_file( array $data ): MaterialFilesModel {
		if ( empty( $data['item_id'] ) ) {
			throw new Exception( 'item_id is required!' );
		}

		if ( empty( $data['created_at'] ) ) {
			$data['created_at'] = gmdate( 'Y-m-d H:i:s' );
		}

		$model = new MaterialFilesModel( $data );
		$model->save( true );

		return $model;
	}
}
