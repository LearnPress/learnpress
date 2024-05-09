<?php

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\CourseMaterialTemplate;

/**
 * Class LP_Rest_Material_Controller
 * in LearnPres > Downloadable Materials
 *
 * @since 4.2.2
 * @author khanhbd
 * @version 1.0.1
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
					'permission_callback' => array( $this, 'check_user_permission' ),
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
					'permission_callback' => array( $this, 'check_user_permission' ),
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
					'permission_callback' => array( $this, 'check_user_permission' ),
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
	 * Save files materials
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @version 1.0.1
	 * @since 4.2.2
	 */
	public function save_post_materials( WP_REST_Request $request ) {
		$response = new LP_REST_Response();
		$response->data = [];

		try {
			$params               = $request->get_params();
			$item_id              = $params['item_id'] ?? 0;
			$material_data_string = $params['data'] ?? false;
			$upload_file          = $request->get_file_params();

			if ( empty( $item_id ) ) {
				throw new Exception( esc_html__( 'Invalid course or lesson', 'learnpress' ) );
			}

			if ( empty( $material_data_string ) ) {
				throw new Exception( esc_html__( 'Invalid materials', 'learnpress' ) );
			}

			$material_data = LP_Helper::json_decode( wp_unslash( $material_data_string ), true );
			$file          = $upload_file['file'] ?? false;

			// DB Init
			$material_db = LP_Material_Files_DB::getInstance();
			// LP Material Settings
			$max_file_size       = (int) LP_Settings::get_option( 'material_max_file_size', 2 );
			$allow_upload_amount = (int) LP_Settings::get_option( 'material_upload_files', 2 );
			// check file was uploaded
			$count_uploaded_files = count( $material_db->get_material_by_item_id( $item_id, 0, 0, 1 ) );
			// check file amount which can upload
			$can_upload = $allow_upload_amount - $count_uploaded_files;

			//Check file amount validation
			if ( $can_upload <= 0 ) {
				throw new Exception( esc_html__( 'Material feature is not allowed to upload', 'learnpress' ) );
			} elseif ( $allow_upload_amount > 0 ) {
				if ( count( $material_data ) > $can_upload ) {
					throw new Exception( esc_html__( 'Your uploaded files reach the maximum amount!', 'learnpress' ) );
				}
			}

			$mime_types       = get_allowed_mime_types();
			$file_methods     = array( 'upload', 'external' );
			$error_messages   = '';
			$success_messages = '';
			foreach ( $material_data as $key => $material ) {
				$label     = LP_Helper::sanitize_params_submitted( $material['label'] ?? '' );
				$method    = $material['method'] ?? '';
				$file_name = $file['name'][ $key ] ?? '';

				// check file title
				if ( empty( $label ) ) {
					$error_messages .= sprintf( __( 'File "%s" title is not empty!', 'learnpress' ), $file_name );
					continue;
				}
				// check file upload method
				if ( ! in_array( $method, $file_methods ) ) {
					$error_messages .= sprintf( __( 'File %s method is invalid!', 'learnpress' ), $label );
					continue;
				}

				$file_path = '';
				if ( $method == 'upload' ) {
					if ( $file['size'][ $key ] > $max_file_size * 1024 * 1024 ) {
						$error_messages .= sprintf( __( 'File %s size is too large!', 'learnpress' ), $label );
						continue;
					}

					// Check type file
					$file_info = wp_check_filetype( $file_name );
					$file_type = $file_info['ext'] ?? '';
					if ( empty( $file_info['ext'] )
					     || false === $this->material_check_file_extention( $file_info['ext'] )
					     || ! in_array( $file_info['type'], get_allowed_mime_types() ) ) {
						$error_messages .= sprintf( esc_html__( 'File %s type is invalid!', 'learnpress' ), $label );
						continue;
					}

					LP_WP_Filesystem::instance();
					$file_uploading     = [
						'name'     => $file_name,
						'type'     => $file_type,
						'tmp_name' => $file['tmp_name'][ $key ],
						'error'    => $file['error'][ $key ],
						'size'     => $file['size'][ $key ],
					];
					$file_handle_upload = wp_handle_upload( $file_uploading, [ 'test_form' => false ] );
					if ( ! empty( $file_handle_upload['error'] ) ) {
						$error_messages .= sprintf( esc_html__( 'File %s: ', 'learnpress' ), $label ) . $file_handle_upload['error'];
						continue;
					}

					$file_path = str_replace( wp_upload_dir()['baseurl'], '', $file_handle_upload['url'] );
				} elseif ( $method == 'external' ) {
					$file_path          = sanitize_url( $material['link'] );
					$file_external_info = $this->check_external_file( $file_path );
					$mime_type          = $file_external_info['type'] ?? '';
					$file_ext           = array_search( $mime_type, $mime_types );
					if ( $file_ext === false ) {
						$file_type = __( 'Unknown', 'learnpress' );
					} else {
						$file_type = $file_ext;
					}
					if ( $file_external_info['error'] ) {
						$error_messages .= sprintf(
							esc_html__( 'An error occurred while checking %1$s. %2$s', 'learnpress' ),
							$label,
							$file_external_info['error_message']
						);
						continue;
					}

					// For a long time remove this code. @since 4.2.6.6
					$material_db->wpdb->query(
						"ALTER TABLE $material_db->tb_lp_files MODIFY file_type VARCHAR(100) NOT NULL DEFAULT '';"
					);
				}

				$orders     = $count_uploaded_files + $key + 1;
				$insert_arr = [
					'file_name'  => sanitize_text_field( $label ),
					'file_type'  => $file_type ?? '',
					'item_id'    => (int) $item_id,
					'item_type'  => get_post_type( $item_id ),
					'method'     => $method,
					'file_path'  => $file_path,
					'orders'     => $orders,
					'created_at' => current_time( 'Y-m-d H:i:s' ),
				];
				$insert     = $material_db->create_material( $insert_arr );
				if ( ! $insert ) {
					$error_messages .= sprintf( __( 'Cannot save file %s', 'learnpress' ), $label );
					continue;
				}

				$success_messages .= __( 'Other files is upload successfully.', 'learnpress' );
				$response->data[] = [
					'file_name' => $label,
					'method'    => ucfirst( $method ),
					'file_id'   => $insert,
					'orders'    => $orders,
				];
			}

			if ( ! empty( $error_messages ) ) {
				$response->message .= $error_messages;
			}

			if ( ! empty( $success_messages ) ) {
				$response->status = 'success';
				if ( empty( $error_messages ) ) {
					$success_messages = __( 'Files upload successfully.', 'learnpress' );;
				}
				$response->message .= $success_messages;
			}
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Get list files of a course or a lesson
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @version 1.0.1
	 * @since 4.2.2
	 */
	public function get_materials_by_item( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$params  = $request->get_params();
			$item_id = $params['item_id'] ?? 0;
			if ( ! $item_id ) {
				throw new Exception( esc_html__( 'Invalid course or lesson identifier', 'learnpress' ) );
			}

			$is_admin       = $params['is_admin'] ?? false;
			$material_init  = LP_Material_Files_DB::getInstance();
			$page           = absint( $params['page'] ?? 1 );
			$per_page       = $params['per_page'] ?? (int) LP_Settings::get_option( 'material_file_per_page', - 1 );
			$offset         = ( $per_page > 0 && $page > 1 ) ? $per_page * ( $page - 1 ) : 0;
			$total          = $material_init->get_total( $item_id );
			$pages          = $per_page > 0 ? ceil( $total / $per_page ) : 0;
			$item_materials = $material_init->get_material_by_item_id( $item_id, $per_page, $offset, $is_admin );

			if ( $item_materials ) {
				if ( $is_admin ) {
					$response->data->items = $item_materials;
				} else {
					$response->data->load_more = $page < $pages && $per_page > 0;
					ob_start();
					$material_template = CourseMaterialTemplate::instance();
					foreach ( $item_materials as $m ) {
						$m->current_item_id = $item_id;
						echo $material_template->material_item( $m );
					}
					$response->data->items = ob_get_clean();
				}

				$response->message = esc_html__( 'Successfully', 'learnpress' );
			} else {
				$response->message = esc_html__( 'Empty material!', 'learnpress' );
			}

			$response->status = 'success';
		} catch ( Throwable $th ) {
			$response->message = $th->getMessage();
		}

		return $response;
	}

	/**
	 * Get info file from external link
	 *
	 * @param $file_url
	 *
	 * @return array
	 * @version 1.0.1
	 * @since 4.2.2
	 */
	public function check_external_file( $file_url ): array {
		$lp_file   = LP_WP_Filesystem::instance();
		$temp_file = $lp_file->download_url( $file_url );
		$file      = [];
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

		return $file;
	}

	/**
	 * @param  [string] $file_name [upload file name]
	 * @param  [] $file_tmp  [file content]
	 *
	 * @return [array]            [file infomations]
	 * @since 4.2.2
	 * [material_upload_file upload file when user choose upload method]
	 * @version 1.0.0
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
	 * @param  [string] $ext [file extendsion]
	 *
	 * @return [string]      [file extendsion]
	 * @version 1.0.0
	 * @since 4.2.2
	 * [material_check_file_extention return file type of file]
	 */
	public function material_check_file_extention( $ext ) {
		$allow_file_type = LP_Settings::get_option( 'material_allow_file_type', array( 'pdf', 'txt' ) );
		$allow_file_type = implode( ',', $allow_file_type );
		$allow_file_type = explode( ',', $allow_file_type );

		return in_array( $ext, $allow_file_type ) ? $ext : false;
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
	 * Check user permission
	 *
	 * @param $request
	 *
	 * @return bool
	 * @version 1.0.1
	 * @since 4.2.2
	 */
	public function check_user_permission( $request ): bool {
		$permission      = false;
		$item_id         = $request['item_id'] ?? $request->get_param( 'item_id' );
		$author          = get_post_field( 'post_author', $item_id );
		$current_user_id = get_current_user_id();
		if ( ( $author == $current_user_id && current_user_can( LP_TEACHER_ROLE ) ) || current_user_can( ADMIN_ROLE ) ) {
			$permission = true;
		}

		return $permission;
	}

}
