<?php
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\CourseMaterialTemplate;
/**
 * Class LP_Rest_Material_Controller
 * in LearnPres > Downloadable Materials
 *
 * @since 4.2.2
 * khanhbd <email@email.com>
 */
class LP_Rest_Material_Controller extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/';
		$this->rest_base = 'material';

		parent::__construct();
	}

	public function register_routes() {
		$this->routes = array(
			'item-materials/(?P<item_id>[\d]+)' => array(
				'args' => array(
					'item_id' => array(
						'description'       => __( 'A unique identifier for the resource.', 'learnpress' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_post_materials' ),
					'permission_callback' => array( $this, 'check_user_permissons' ),
					'args'                => array(
						'data' => array(
							'description'       => esc_html__( 'Data of material', 'learnpress' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'file' => array(
							'description' => esc_html__( 'File.', 'learnpress' ),
							'type'        => 'array',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_materials_by_item' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_material_orders' ),
					'permission_callback' => array( $this, 'check_user_permissons' ),
					'args'                => array(
						'sort_arr' => array(
							'description'       => esc_html__( 'Material orders', 'learnpress' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			'(?P<file_id>[\d]+)'                => array(
				'args' => array(
					'file_id' => array(
						'description' => __( 'A unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_material' ),
					'permission_callback' => array( $this, 'check_user_permissons' ),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_material' ),
					'permission_callback' => '__return_true',
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [save_post_materials create material files of a course or lesson and save them to DB]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function save_post_materials( $request ) {
		$response = array(
			'data'    => array(
				'status' => 400,
			),
			'message' => esc_html__( 'There was an error when save the file.', 'learnpress' ),
		);
		try {
			$item_id       = $request['item_id'];
			$material_data = $request->get_param( 'data' );
			$upload_file   = $request->get_file_params( 'file' );
			if ( ! $item_id ) {
				throw new Exception( esc_html__( 'Invalid course or lesson', 'learnpress' ) );
			}
			if ( ! $material_data || ! json_decode( wp_unslash( $material_data ), true ) ) {
				throw new Exception( esc_html__( 'Invalid materials', 'learnpress' ) );
			}
			// $material0 = $material_data;
			$material_data = json_decode( wp_unslash( $material_data ), true );
			$file          = $upload_file['file'] ?? false;
			// $file = $file['file'] ;
			$file_method = array( 'upload', 'external' );
			// DB Init
			$material_init = LP_Material_Files_DB::getInstance();
			// LP Material Settings
			$max_file_size       = (int) LP_Settings::get_option( 'material_max_file_size', 2 );
			$allow_upload_amount = (int) LP_Settings::get_option( 'material_upload_files', 2 );
			// check file was uploaded
			$uploaded_files = count( $material_init->get_material_by_item_id( $item_id, 0, 0, 1 ) );
			// check file amount which can upload
			$can_upload      = $allow_upload_amount - $uploaded_files;
			$allow_file_type = LP_Settings::get_option( 'material_allow_file_type', array( 'pdf', 'txt' ) );
			//Check file amount validation
			if ( $can_upload <= 0 ) {
				throw new Exception( esc_html__( 'Material feature is not allowed to upload', 'learnpress' ) );
			} elseif ( $allow_upload_amount > 0 ) {
				if ( count( $material_data ) > $can_upload ) {
					throw new Exception( esc_html__( 'Your uploaded files reach the maximum amount!', 'learnpress' ) );
				}
			}
			foreach ( $material_data as $key => $material ) {
				// check file title
				if ( ! $material['label'] ) {
					$response['items'][ $key ]['message'] = sprintf( esc_html__( 'File %d title is not empty!', 'learnpress' ), $key );
					continue;
				}
				// check file upload method
				if ( ! in_array( $material['method'], $file_method ) ) {
					$response['items'][ $key ]['message'] = sprintf( esc_html__( 'File %s method is invalid!', 'learnpress' ), $material['label'] );
					continue;
				}

				if ( $material['method'] == 'upload' ) {
					if ( ! $material['file'] ) {
						$response['items'][ $key ]['message'] = sprintf( esc_html__( 'File %s is empty!', 'learnpress' ), $material['label'] );
						continue;
					}
					$file_key = array_search( $material['file'], $file['name'] );
					if ( $file['size'][ $file_key ] > $max_file_size * 1024 * 1024 ) {
						$response['items'][ $key ]['message'] = sprintf( esc_html__( 'File %s size is too large!', 'learnpress' ), $material['label'] );
						continue;
					}
					$movefile = $this->material_upload_file( $file['name'][ $file_key ], $file['tmp_name'][ $file_key ] );
					if ( ! $movefile ) {
						$response['items'][ $key ]['message'] = sprintf( esc_html__( 'Upload File %s is error!', 'learnpress' ), $material['label'] );
						continue;
					}
					$file_type = wp_check_filetype( basename( $movefile['file'] ) )['ext'];
					$file_type = $this->material_check_file_extention( $file_type );
					$file_path = str_replace( wp_upload_dir()['baseurl'], '', $movefile['url'] );
				}

				if ( $material['method'] == 'external' ) {
					$check_file = $this->check_external_file( sanitize_url( $material['link'] ) );
					if ( $check_file['error'] ) {
						$response['items'][ $key ]['message'] = sprintf( esc_html__( 'An error occurred while checking %1$s. %2$s', 'learnpress' ), $material['label'], $check_file['error_message'] );
						continue;
					}
					if ( $check_file['size'] > $max_file_size * 1024 * 1024 ) {
						$response['items'][ $key ]['message'] = sprintf( esc_html__( 'File %s size is too large!', 'learnpress' ), $material['label'] );
						continue;
					}
					$file_type = wp_check_filetype( $check_file['name'] )['ext'];
					$file_type = $this->material_check_file_extention( $file_type );
					$file_path = sanitize_url( $material['link'] );
				}
				if ( ! $file_type ) {
					$response['items'][ $key ]['message'] = sprintf( esc_html__( 'File %s - file type is invalid!', 'learnpress' ), $material['label'] );
					continue;
				}
				$orders     = $uploaded_files + $key + 1;
				$insert_arr = array(
					'file_name'  => sanitize_text_field( $material['label'] ),
					'file_type'  => $file_type,
					'item_id'    => (int) $item_id,
					'item_type'  => get_post_type( $item_id ),
					'method'     => $material['method'],
					'file_path'  => $file_path,
					'orders'     => $orders,
					'created_at' => current_time( 'Y-m-d H:i:s' ),
				);
				$insert     = $material_init->create_material( $insert_arr );
				if ( ! $insert ) {
					$response['items'][ $key ]['message'] = sprintf( esc_html__( 'cannot save file %d', 'learnpress' ), $key );
					continue;
				}
				$response['material'][ $key ]['data'] = array(
					'file_name' => $material['label'],
					'method'    => ucfirst( $material['method'] ),
					'file_id'   => $insert,
					'orders'    => $orders,
				);
			}
			$response['data']['status'] = 200;
			$response['message']        = esc_html__( 'The progress was saved! Your file(s) were uploaded successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response['data']['status'] = 400;
			$response['message']        = $e->getMessage();
		}
		return rest_ensure_response( $response );
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [get_materials_by_item get materials file of a course or a lesson]
	 * @param  [wp_request] $request [description]
	 * @return [type]          [description]
	 */
	public function get_materials_by_item( WP_REST_Request $request ) {
		/*$response = array(
			'data'    => array(),
			'status'  => 400,
			'message' => esc_html__( 'There was an error when save the file.', 'learnpress' ),
		);*/
		$response = new LP_REST_Response();
		try {
			$params  = $request->get_params();
			$item_id = $request['item_id'];
			if ( ! $item_id ) {
				throw new Exception( esc_html__( 'Invalid course or lesson identifier', 'learnpress' ) );
			}
			$is_admin         = $params['is_admin'] ?? false;
			$material_init    = LP_Material_Files_DB::getInstance();
			$page             = absint( $params['page'] ?? 1 );
			$per_page         = $params['per_page'] ?? (int) LP_Settings::get_option( 'material_file_per_page', -1 );
			$offset           = ( $per_page > 0 && $page > 1 ) ? $per_page * ( $page - 1 ) : 0;
			$total            = $material_init->get_total( $item_id );
			$pages            = $per_page > 0 ? ceil( $total / $per_page ) : 0;
			$item_materials   = $material_init->get_material_by_item_id( $item_id, $per_page, $offset, $is_admin );
			$response->status = 200;
			if ( $item_materials ) {
				// $lp_file = LP_WP_Filesystem::instance();
				if ( $is_admin ) {
					$response->data = $item_materials;
				} else {
					$response->load_more = ( $page < $pages && $per_page > 0 ) ? true : false;
					ob_start();
					$material_template = CourseMaterialTemplate::instance();
					foreach ( $item_materials as $m ) {
						$m->current_item_id = $item_id;
						echo $material_template->material_item( $m );
					}
					$response->data = ob_get_clean();
				}
				$response->message = esc_html__( 'Successfully', 'learnpress' );
			} else {
				$response->message = esc_html__( 'Empty material!', 'learnpress' );
			}
		} catch ( Throwable $th ) {
			$response->status  = 400;
			$response->message = $th->getMessage();
		}
		return rest_ensure_response( $response );
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [check_external_file check the file from external url]
	 * @param  [string] $file_url [url]
	 * @return [array||fasle]     [array of file infomations]
	 */
	public function check_external_file( $file_url ) {
		try {
			// it allows us to use download_url() and wp_handle_sideload() functions
			$lp_file = LP_WP_Filesystem::instance();
			// download to temp dir
			$temp_file = $lp_file->download_url( $file_url );
			$error     = false;
			$file      = array();
			if ( is_wp_error( $temp_file ) ) {
				$file['error']         = true;
				$file['error_message'] = $temp_file->get_error_message();
			} else {
				$file = array(
					'name'          => basename( $file_url ),
					'type'          => mime_content_type( $temp_file ),
					'tmp_name'      => $temp_file,
					'size'          => filesize( $temp_file ),
					'error'         => false,
					'error_message' => '',
				);
			}
			//get file properties
			return $file;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [material_upload_file upload file when user choose upload method]
	 * @param  [string] $file_name [upload file name]
	 * @param  [] $file_tmp  [file content]
	 * @return [array]            [file infomations]
	 */
	public function material_upload_file( $file_name, $file_tmp ) {
		try {
			$file = wp_upload_bits( $file_name, null, file_get_contents( $file_tmp ) );

			return $file['error'] ? false : $file;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [material_check_file_extention return file type of file]
	 * @param  [string] $ext [file extendsion]
	 * @return [string]      [file extendsion]
	 */
	public function material_check_file_extention( $ext ) {
		try {
			$allow_file_type = LP_Settings::get_option( 'material_allow_file_type', array( 'pdf', 'txt' ) );
			$allow_file_type = implode( ',', $allow_file_type );
			$allow_file_type = explode( ',', $allow_file_type );
			return in_array( $ext, $allow_file_type ) ? $ext : false;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [get_material description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_material( $request ) {
		$response = new LP_REST_Response();
		try {
			$id = $request['file_id'];
			if ( ! $id ) {
				throw new Exception( esc_html__( 'Invalid identifier', 'learnpress' ) );
			}
			$material_init = LP_Material_Files_DB::getInstance();
			$file          = $material_init->get_material( $id );
			if ( $file ) {
				if ( $file->method == 'upload' ) {
					$file->file_path = wp_upload_dir()['baseurl'] . $file->file_path;
				}
				$response_data = $file;
				$message       = esc_html__( 'Get file successfully.', 'learnpress' );
			} else {
				$response_data = [];
				$message       = esc_html__( 'The file is not exist', 'learnpress' );
			}
			$response->message = $message;
			$response->data    = $response_data;
			$response->status  = 200;
		} catch ( Throwable $th ) {
			$response->message = $th->getMessage();
		}
		return rest_ensure_response( $response );
	}
	public function update_material_orders( $request ) {
		$response = new LP_REST_Response();
		try {
			$item_id       = $request['item_id'];
			$sort_arr      = $request->get_param( 'sort_arr' );
			$sort_arr      = json_decode( wp_unslash( $sort_arr ), true );
			$material_init = LP_Material_Files_DB::getInstance();
			$update_sort   = $material_init->update_material_orders( $sort_arr, $item_id );
			if ( $update_sort ) {
				$response->status  = 200;
				$response->message = esc_html__( 'Updated.', 'learnpress' );
				// $response->data = $sort_arr;
			} else {
				throw new Exception( esc_html__( 'Update failed!', 'learnpress' ) );
			}
		} catch ( Throwable $e ) {
			$response->status  = 400;
			$response->message = $e->getMessage();
		}
		return rest_ensure_response( $response );
	}
	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [delete_material delete a material when a delete request is send]
	 * @param  [type] $request [description]
	 * @return [json]          [return message]
	 */
	public function delete_material( $request ) {
		$response = new LP_REST_Response();
		try {
			$id = $request['file_id'];
			if ( ! $id ) {
				throw new Exception( esc_html__( 'Invalid file identifier', 'learnpress' ) );
			}
			// DB Init
			$material_init = LP_Material_Files_DB::getInstance();
			// Delete record
			$delete = $material_init->delete_material( $id );
			if ( $delete ) {
				$message = esc_html__( 'File is deleted.', 'learnpress' );
				$deleted = true;
			} else {
				$message = esc_html__( 'There is an error when delete this file.', 'learnpress' );
				$deleted = false;
			}
			$response->status  = 200;
			$response->delete  = $deleted;
			$response->message = $message;
		} catch ( Throwable $th ) {
			$response->status  = 400;
			$response->message = $th->getMessage();
		}
		return rest_ensure_response( $response );
	}
	/**
	 * @version 1.0.0
	 * @since 4.2.2
	 * [check_user_permissons check permisson, true when user is admin or instructor]
	 * @return [boolean] [description]
	 */
	public function check_user_permissons( $request ) : bool {
		$permission      = false;
		$item_id         = $request['item_id'] ?? $request->get_param( 'item_id' );
		$author          = get_post_field( 'post_author', $item_id );
		$current_user_id = get_current_user_id();
		if ( $author == $current_user_id || current_user_can( ADMIN_ROLE ) ) {
			$permission = true;
		}
		return $permission;
	}

}
