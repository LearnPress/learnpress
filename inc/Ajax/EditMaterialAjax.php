<?php
/**
 * class EditMaterialAjax
 *
 * @since 4.2.8.7.6
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\LessonPostModel;
use LP_Helper;
use LP_REST_Response;
use Throwable;
use LP_Material_Files_DB;
use Exception;
use LP_Settings;
use LP_WP_Filesystem;

class EditMaterialAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.2.8.7.6
	 * @version 1.0.0
	 */
	public static function get_data() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		return LP_Helper::json_decode( $params, true );
	}
	public static function save_materials() {
		$response = new LP_REST_Response();
		try {
			$params        = self::get_data();
			$item_id       = absint( $params['item_id'] ?? 0 );
			$file          = $_FILES['file'];
			$material_data = $params['material_data'] ?? false;
			// set_transient( 'test_params', $params, $expiration = 3600 );
			if ( empty( $item_id ) ) {
				throw new Exception( esc_html__( 'Invalid course or lesson', 'learnpress' ) );
			}
			if ( empty( $material_data ) ) {
				throw new Exception( esc_html__( 'Invalid materials', 'learnpress' ) );
			}
			$material_db         = LP_Material_Files_DB::getInstance();
			$material_data       = LP_Helper::json_decode( wp_unslash( $material_data ), true );
			$max_file_size       = (int) LP_Settings::get_option( 'material_max_file_size', 2 );
			$allow_upload_amount = (int) LP_Settings::get_option( 'material_upload_files', 2 );
			// check file was uploaded
			$count_uploaded_files = count( $material_db->get_material_by_item_id( $item_id, 0, 0, 1 ) );
			// check file amount which can upload
			$can_upload = $allow_upload_amount - $count_uploaded_files;
			if ( $can_upload <= 0 ) {
				throw new Exception( esc_html__( 'Uploading this type of material is not allowed.', 'learnpress' ) );
			} elseif ( $allow_upload_amount > 0 ) {
				if ( count( $material_data ) > $can_upload ) {
					throw new Exception( esc_html__( 'No more files can be uploaded — limit reached.!', 'learnpress' ) );
				}
			}
			$error_messages   = '';
			$success_messages = '';
			$response_data    = array();
			foreach ( $material_data as $key => $material ) {
				$label     = sanitize_text_field( $material['label'] ?? '' );
				$method    = $material['method'] ?? '';
				$file_name = $file['name'][ $key ] ?? '';

				// check file title
				if ( empty( $label ) ) {
					$error_messages .= sprintf( __( 'File "%s" title is not empty!', 'learnpress' ), $file_name );
					continue;
				}
				// check file upload method
				if ( ! in_array( $method, array( 'upload', 'external' ) ) ) {
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
						|| false === self::material_check_file_extention( $file_info['ext'] )
						|| ! in_array( $file_info['type'], get_allowed_mime_types() ) ) {
						$error_messages .= sprintf( esc_html__( 'File %s type is invalid!', 'learnpress' ), $label );
						continue;
					}

					LP_WP_Filesystem::instance();
					$file_uploading     = array(
						'name'     => $file_name,
						'type'     => $file_type,
						'tmp_name' => $file['tmp_name'][ $key ],
						'error'    => $file['error'][ $key ],
						'size'     => $file['size'][ $key ],
					);
					$file_handle_upload = wp_handle_upload( $file_uploading, array( 'test_form' => false ) );
					if ( ! empty( $file_handle_upload['error'] ) ) {
						$error_messages .= sprintf( esc_html__( 'File %s: ', 'learnpress' ), $label ) . $file_handle_upload['error'];
						continue;
					}

					$file_path = str_replace( wp_upload_dir()['baseurl'], '', $file_handle_upload['url'] );
				} elseif ( $method == 'external' ) {
					$file_path   = sanitize_url( $material['link'] );
					$file_info   = pathinfo( $file_path );
					$file_extend = $file_info['extension'] ?? '';
					if ( ! $file_extend ) {
						$file_type = __( 'Unknown', 'learnpress' );
					} else {
						$file_type = $file_extend;
					}
					$check_allow_file = self::check_wp_allowed_file_type( $file_extend );
					if ( ! $check_allow_file ) {
						$error_messages .= sprintf(
							esc_html__( 'An error occurred while checking %1$s. %2$s', 'learnpress' ),
							$label,
							__( 'Oops! That file type isn’t allowed.', 'learnpress' )
						);
						continue;
					}

					// For a long time remove this code. @since 4.2.6.6
					$material_db->wpdb->query(
						"ALTER TABLE $material_db->tb_lp_files MODIFY file_type VARCHAR(100) NOT NULL DEFAULT '';"
					);
				}

				$orders     = $count_uploaded_files + $key + 1;
				$insert_arr = array(
					'file_name'  => sanitize_text_field( $label ),
					'file_type'  => $file_type ?? '',
					'item_id'    => (int) $item_id,
					'item_type'  => get_post_type( $item_id ),
					'method'     => $method,
					'file_path'  => $file_path,
					'orders'     => $orders,
					'created_at' => current_time( 'Y-m-d H:i:s' ),
				);
				$insert     = $material_db->create_material( $insert_arr );
				if ( ! $insert ) {
					$error_messages .= sprintf( __( 'Cannot save file %s', 'learnpress' ), $label );
					continue;
				}

				$success_messages .= __( 'Other files is upload successfully.', 'learnpress' );

				$response_data[] = array(
					'file_name' => $label,
					'method'    => ucfirst( $method ),
					'file_id'   => $insert,
					'orders'    => $orders,
				);
			}
			if ( ! empty( $error_messages ) ) {
				$response->message .= $error_messages;
			}

			if ( ! empty( $success_messages ) ) {
				$response->status = 'success';
				if ( empty( $error_messages ) ) {
					$success_messages = __( 'Files upload successfully.', 'learnpress' );
				}
				$response->message .= $success_messages;
			}
			$response->data->items = $response_data;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage() . $e->getLine();
		}
		wp_send_json( $response );
	}

	/**
	 * delete material file
	 */
	public static function delete_material() {
		$response = new LP_REST_Response();
		try {
			$params  = self::get_data();
			$file_id = $params['file_id'] ?? 0;
			if ( ! $file_id ) {
				throw new Exception( esc_html__( 'Invalid file identifier', 'learnpress' ) );
			}
			// DB Init
			$material_db = LP_Material_Files_DB::getInstance();
			// Delete record
			$delete = $material_db->delete_material( absint( $file_id ) );
			if ( $delete ) {
				$message = esc_html__( 'File is deleted.', 'learnpress' );
				$deleted = true;
			} else {
				$message = esc_html__( 'There is an error when delete this file.', 'learnpress' );
				$deleted = false;
			}
			$response->status  = 'success';
			$response->delete  = $deleted;
			$response->message = $message;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}
		wp_send_json( $response );
	}
	/**
	 * update material files's orders
	 */
	public static function update_material_orders() {
		$response = new LP_REST_Response();
		try {
			$params   = self::get_data();
			$item_id  = $params['item_id'] ?? 0;
			$sort_arr = $params['sort_arr'];
			$sort_arr = json_decode( wp_unslash( $sort_arr ), true );

			$material_db = LP_Material_Files_DB::getInstance();
			$update_sort = $material_db->update_material_orders( $sort_arr, (int) $item_id );
			if ( $update_sort ) {
				$response->message = esc_html__( 'Updated.', 'learnpress' );
				$response->status  = 'success';
			} else {
				throw new Exception( esc_html__( 'Update failed!', 'learnpress' ) );
			}
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}
		wp_send_json( $response );
	}

	/**
	 * material_check_file_extention return file type of file
	 *
	 * @param  string $ext file extendsion
	 *
	 * @return string|false file extendsion or false
	 */
	public static function material_check_file_extention( $ext ) {
		$allow_file_type = LP_Settings::get_option( 'material_allow_file_type', array( 'pdf', 'txt' ) );
		$allow_file_type = implode( ',', $allow_file_type );
		$allow_file_type = explode( ',', $allow_file_type );

		return in_array( $ext, $allow_file_type ) ? $ext : false;
	}
	/**
	 * [check_wp_allowed_file_type description]
	 *
	 * @param  string $file_ext file extend(png, jpg.....)
	 * @return boolean
	 */
	public static function check_wp_allowed_file_type( string $file_ext = '' ): bool {
		$allowed = get_allowed_mime_types();
		foreach ( $allowed as $ext => $mime ) {
			if ( strpos( $ext, $file_ext ) !== false ) {
				return true; // Found the string in a key
			}
		}
		return false;
	}
}
