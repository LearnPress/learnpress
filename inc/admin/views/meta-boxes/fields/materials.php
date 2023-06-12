<?php
/**
 * LP_Meta_Box_Material_Fields
 *
 * @author khanhbd
 * @version 1.0.0
 * @since 4.0.0
 */
if ( ! class_exists( 'LP_Meta_Box_Material_Fields' ) ) {
	class LP_Meta_Box_Material_Fields extends LP_Meta_Box_Field {
		/**
		 * Constructor.
		 *
		 * @param string $id
		 * @param string $label
		 * @param string $description
		 * @param mixed  $default
		 * @param array  $extra
		 */
		private static $instance = null;
		public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
			parent::__construct( $label, $description, $default, $extra );
			add_action( 'wp_ajax__lp_save_materials', array( $this, 'lp_material_save_materials' ) );
			add_action( 'wp_ajax__lp_delete_material', array( $this, 'lp_material_delete_material' ) );
		}
		/**
		 * [output Downloadable Material Tab content in Course Setting Meta Box]
		 * @param  [int] $thepostid [course's post_id]
		 * @return [html]            [content of Download material tab]
		 */
		public function output( $thepostid ) {
			$material_init = LP_Material_Files_DB::getInstance();
			$course_material = $material_init->get_material_by_item_id( $thepostid );
			?>
			<button class="button button-primary" id="btn-lp--add-material" type="button"><?php esc_html_e( 'Add Course Materials', 'learnpress' ) ?></button>
			<div id="lp-material--add-material-template" hidden>
				<div class="lp-material--group">
					<div class="lp-material--field-wrap">
						<label ><?php esc_html_e( 'File Title', 'learnpress' ) ?></label>
						<input type="text" class="lp-material--field-title" value="" placeholder="<?php esc_attr_e( 'Enter File Title', 'learnpress' ) ?>" />
					</div>
					<div class="lp-material--field-wrap">
						<label ><?php esc_html_e( 'Method', 'learnpress' ) ?></label>
						<select class="lp-material--field-method">
							<option value="upload" selected><?php esc_html_e( 'Upload', 'learnpress' ) ?></option>
							<option value="external"><?php esc_html_e( 'External', 'learnpress' ) ?></option>
						</select>
					</div>
					<div class="lp-material--field-wrap lp-material--upload-wrap">
						<label ><?php esc_html_e( 'Choose File', 'learnpress' ) ?><input type="file" class="lp-material--field-upload" value=""/></label>
					</div>
					<div class="lp-material--field-wrap">
						<button class="button lp-material--delete" type="button"><?php esc_html_e( 'Remove', 'learnpress' ) ?></button>
					</div>
				</div>
			</div>
			<div id="lp-material--upload-field-template" hidden>
				<div class="lp-material--field-wrap lp-material--upload-wrap">
					<label >
						<?php esc_html_e( 'Choose File', 'learnpress' ) ?>
						<input type="file" class="lp-material--field-upload" value=""/>
					</label>
				</div>
			</div>
			<div id="lp-material--external-field-template" hidden>
				<div class="lp-material--field-wrap lp-material--external-wrap">
					<label ><?php esc_html_e( 'External Link', 'learnpress' ) ?></label>
					<input type="url" class="lp-material--field-external-link" value="" placeholder="<?php esc_attr_e( 'Enter External Link', 'learnpress' ) ?>" />
				</div>
			</div>
			<input type="hidden" id="current-material-post-id" value="<?php echo esc_attr( $thepostid ) ?>">
			<input type="hidden" id="delete-material-message" value="<?php esc_attr_e( 'Do you want to delete this file?', 'learnpress' ) ?>">
			<?php if ($course_material): ?>
				<style>
					table.lp-material--table {
/*					  font-family: arial, sans-serif;*/
					  border-collapse: collapse;
					  width: 100%;
					}

					table.lp-material--table td, th {
					  border: 1px solid #dddddd;
					  text-align: left;
					  padding: 8px;
					}

					table.lp-material--table tr:nth-child(even) {
					  background-color: #dddddd;
					}
					</style>
				</style>
				<table class="lp-material--table">
				  <tr>
				    <th><?php esc_html_e( 'File Title', 'learnpress' ) ?></th>
				    <th><?php esc_html_e( 'Method', 'learnpress' ) ?></th>
				    <th><?php esc_html_e( 'Action', 'learnpress' ) ?></th>
				  </tr>
				  
				  <?php foreach ($course_material as $row): ?>
				  	<tr >
				  	  <td><?php echo esc_attr( $row->file_name )?></td>
				  	  <td><?php echo esc_attr( ucfirst( $row->method ) )?></td>
				  	  <td><a href="javascript:void(0)" class="delete-material-row" data-id="<?php echo esc_attr( $row->file_id )?>"><?php esc_html_e( 'Delete', 'learnpress' ) ?></a></td>
				  	</tr>
				  <?php endforeach;?>
				</table>

			<?php endif;?>
			<div id="lp-material--group-container">
				
			</div>
			<button class="button button-primary" id="btn-lp--save-material" type="button"><?php esc_html_e( 'Save', 'learnpress' ) ?></button>

			<?php
		}
		public function lp_material_save_materials() {
			try {
				if ( ! wp_verify_nonce( $_POST['nonce'], 'wp_rest' ) ) {
					throw new Exception( esc_html__( 'Invalid nonce!', 'learnpress' ) );
					die();
				}
				$material_data = $_POST['data'] ?? '';
				$material_data = json_decode( stripslashes( $material_data ), true );
				$post_id = $_POST['post_id'] ?? 0;
				if ( ! $post_id ) {
					throw new Exception( esc_html__( 'Invalid post_id', 'learnpress' ) );
				}
				if ( ! $material_data ) {
					throw new Exception( esc_html__( 'Invalid materials', 'learnpress' ) );
				}
				if ( ! function_exists( 'wp_handle_upload' ) || ! function_exists( 'download_url' ) ) {
				    require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}
				$file = array();
				if ( $_FILES['file'] ) {
					$file = $_FILES['file'];
					// print_r($file);
				}
				$file_method = array( 'upload', 'external' );
				$material_init = LP_Material_Files_DB::getInstance();
				$max_file_size = (int)LP_Settings::get_option('material_max_file_size');
				foreach ( $material_data as $key => $material ) {
					if ( ! $material['label'] ) {
						throw new Exception( esc_html__( 'Invalid material file title!', 'learnpress' ) );
					}
					if ( ! in_array( $material['method'], $file_method ) ) {
						throw new Exception( esc_html__( 'Invalid file method', 'learnpress' ) );
					}
					if ( $material['method'] == 'upload' ) {
						if ( ! $material['file'] ) {
							throw new Exception( esc_html__( 'Invalid upload file', 'learnpress' ) );
						}
						$file_key = array_search( $material['file'], $file);
						if ( $file['size'][ $key ] > $max_file_size*1024*1024 ) {
							throw new Exception( esc_html__( 'File size is too large, cannot upload.', 'learnpress' ) );
						}
						$movefile = $this->material_upload_file( $file['name'][ $file_key ], $file['tmp_name'][ $file_key ] );
						// print_r($movefile);
						$insert_arr = array( 
								'file_name' 	=> $material['label'],
								'file_type' 	=> $movefile['type'],
								'item_id'		=> (int)$post_id,
								'item_type'		=> get_post_type( $post_id ),
								'method'		=> 'upload',
								'file_path'		=> str_replace( wp_upload_dir()['baseurl'], '', $movefile['url'] ),
								'created_at'	=> date('Y-m-d H:i:s')
							 );
						print_r($insert_arr);
						$insert = $material_init->create_material( $insert_arr );
						if( ! $insert ) {
							// throw new Exception( $insert );
							throw new Exception( esc_html__( 'Upload fail.', 'learnpress' ) );
						}
					}
					
					if ( $material['method'] == 'external' ) {
						$check_file = $this->check_external_file( $material['link'] );
						if ( ! $check_file ){
							throw new Exception( esc_html__( 'Invalid external file', 'learnpress' ) );
						}
						if ( $check_file['size'] > $max_file_size*1024*1024 ) {
							throw new Exception( esc_html__( 'File size is too large, cannot upload.', 'learnpress' ) );
						}
						$insert = $material_init->create_material(
							array( 
								'file_name' 	=> $material['label'],
								'file_type' 	=> $check_file['type'],
								'item_id'		=> (int)$post_id,
								'item_type'		=> get_post_type( $post_id ),
								'method'		=> 'external',
								'file_path'		=> $material['link'],
								'created_at'	=> date('Y-m-d H:i:s')
							 )
						);
						if( ! $insert ) {
							throw new Exception( esc_html__( 'Can upload external file!', 'learnpress' ) );
						}
					}
				}
			} catch (Exception $e) {
				throw new Exception( $e->getMessage() );
			}
		}
		/**
		 * [check_external_file check the file from external url]
		 * @param  [string] $file_url [url]
		 * @return [array||fasle]     [array of file infomations]
		 */
		public function check_external_file( $file_url ) {

			// it allows us to use download_url() and wp_handle_sideload() functions
			require_once( ABSPATH . 'wp-admin/includes/file.php' );

			// download to temp dir
			$temp_file = download_url( $file_url );

			if( is_wp_error( $temp_file ) ) {
				return false;
			}

			//get file properties
			$file = array(
				'name'     => basename( $file_url ),
				'type'     => mime_content_type( $temp_file ),
				'tmp_name' => $temp_file,
				'size'     => filesize( $temp_file ),
			);

			return $file;
		}
		/**
		 * [material_upload_file upload file when user choose upload method]
		 * @param  [string] $file_name [upload file name]
		 * @param  [] $file_tmp  [file content]
		 * @return [array]            [file infomations]
		 */
		public function material_upload_file( $file_name, $file_tmp ){
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			$max_file_size = (int)LP_Settings::get_option('material_max_file_size');
			$file = wp_upload_bits( $file_name, null, file_get_contents( $file_tmp ) );

			return $file;
		}
		public function lp_material_delete_material() {
			try {
				if ( ! wp_verify_nonce( $_POST['nonce'], 'wp_rest' ) ) {
					throw new Exception( esc_html__( 'Invalid nonce!', 'learnpress' ) );
					die();
				}
				$file_id = $_POST['row_id'] ?? 0;
				if ( ! $file_id ) {
					throw new Exception( esc_html__( 'Invalid file id', 'learnpress' ) );
				}
				$material_init = LP_Material_Files_DB::getInstance();
				$delete = $material_init->delete_material( (int)$file_id );
				$result = $delete ? true : false;
				wp_send_json_success( 
					array(
						'delete' => $result
						),
					200
				);
				wp_die();
			} catch (Exception $e) {
				throw new Exception( $e->getMessage() );
			}
		}
		/**
		 * Get instance
		 *
		 * @return LP_Addon_Custom_Tab_Meta_Field
		 */
		public static function instance(): LP_Meta_Box_Material_Fields {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

	}
	LP_Meta_Box_Material_Fields::instance();
}