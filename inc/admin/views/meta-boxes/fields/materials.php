<?php
/**
 * LP_Meta_Box_Material_Fields
 *
 * @author khanhbd
 * @version 1.0.0
 * @since 4.2.2
 */
if ( ! class_exists( 'LP_Meta_Box_Material_Fields' ) ) {
	class LP_Meta_Box_Material_Fields extends LP_Meta_Box_Field {
		/**
		 * Constructor.
		 *
		 * @param string $id
		 * @param string $label
		 * @param string $description
		 * @param mixed $default
		 * @param array $extra
		 */
		private static $instance = null;

		public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
			parent::__construct( $label, $description, $default, $extra );
			// delete materials in post when post is deleted
			add_action( 'delete_post', array( $this, 'clear_material_in_post' ) );
		}
		/**
		 * @author khanhbd
		 * @version 1.0.0
		 * @since 4.2.2
		 * [output Downloadable Material Tab content in Course Setting Meta Box]
		 * @param  [int] $thepostid [course's post_id]
		 * @return [html]            [content of Download material tab]
		 */
		public function output( $thepostid ) {
			$material_init       = LP_Material_Files_DB::getInstance();
			$course_materials    = $material_init->get_material_by_item_id( $thepostid, 0, 0, 1 );
			$max_file_size       = (int) LP_Settings::get_option( 'material_max_file_size', 2 );
			$allow_upload_amount = (int) LP_Settings::get_option( 'material_upload_files', 2 );
			// check file was uploaded
			$uploaded_files = count( $course_materials );
			// check file amount which can upload
			$can_upload          = $allow_upload_amount - $uploaded_files;
			$allow_file_type     = LP_Settings::get_option( 'material_allow_file_type', array( 'pdf', 'txt' ) );
			$accept              = '';
			$accept_file_type    = implode( ', ', $allow_file_type );
			$material_mime_types = LP_Settings::lp_material_file_types();
			foreach ( $allow_file_type as $ext ) {
				$accept .= $material_mime_types[ $ext ] . ',';
			}
			$accept = rtrim( $accept, ',' );
			?>
			<div id="lp-material-container">
				<?php if ( $allow_upload_amount == 0 ) : ?>
					<?php if ( get_post_type( $thepostid ) == LP_COURSE_CPT ) : ?>
						<div> <?php esc_html_e( 'Downloadable Materials is not allowed!', 'learnpress' ); ?> </div>
					<?php endif ?>
				<?php else : ?>
					<div>
						<?php
						echo '+ ';
						echo sprintf(
							__( 'Maximum amount of files you can upload more: %d files (maximum file size is %s MB)', 'learnpress' ),
							$can_upload,
							$max_file_size
						);
						?>
					</div>
					<div>
						<?php
						echo '+ ';
						echo sprintf( __( 'And allow upload only these types: %s.', 'learnpress' ), $accept_file_type );
						?>
					</div>
					<hr>
					<div class="lp-material-btn-wrap">
						<button class="button button-primary" id="btn-lp--add-material" type="button"
								can-upload="<?php esc_attr_e( $can_upload ); ?>">
							<?php esc_html_e( 'Add Course Materials', 'learnpress' ); ?>
						</button>
						<button class="button button-primary" id="btn-lp--save-material"
								type="button"><?php esc_html_e( 'Save', 'learnpress' ); ?></button>
					</div>
					<input type="hidden" id="material-max-file-size" value="<?php esc_attr_e( $max_file_size ); ?>"/>
					<div id="lp-material--add-material-template" hidden>
						<div class="lp-material--group">
							<div class="lp-material--field-wrap">
								<label><?php esc_html_e( 'File Title', 'learnpress' ); ?></label>
								<input type="text" class="lp-material--field-title" value=""
									   placeholder="<?php esc_attr_e( 'Enter File Title', 'learnpress' ); ?>"/>
							</div>
							<div class="lp-material--field-wrap">
								<label><?php esc_html_e( 'Method', 'learnpress' ); ?></label>
								<select class="lp-material--field-method">
									<option value="upload"
											selected><?php esc_html_e( 'Upload', 'learnpress' ); ?></option>
									<option value="external"><?php esc_html_e( 'External', 'learnpress' ); ?></option>
								</select>
							</div>
							<div class="lp-material--field-wrap lp-material--upload-wrap">
								<label><?php esc_html_e( 'Choose File  ', 'learnpress' ); ?><input type="file"
																								   class="lp-material--field-upload"
																								   value=""
																								   accept="<?php esc_attr_e( $accept ); ?>"/></label>
							</div>
							<div class="lp-material--field-wrap field-action-wrap">
								<button type="button"
										class="button lp-material-save-field"><?php esc_html_e( 'Save field', 'learnpress' ); ?></button>
								<button class="button lp-material--delete"
										type="button"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
							</div>
						</div>
					</div>
					<div id="lp-material--upload-field-template" hidden>
						<div class="lp-material--field-wrap lp-material--upload-wrap">
							<label>
								<?php esc_html_e( 'Choose File  ', 'learnpress' ); ?>
								<input type="file" class="lp-material--field-upload" value=""
									   accept="<?php esc_attr_e( $accept ); ?>"/>
							</label>
						</div>
					</div>
					<div id="lp-material--external-field-template" hidden>
						<div class="lp-material--field-wrap lp-material--external-wrap">
							<label><?php esc_html_e( 'File URL', 'learnpress' ); ?></label>
							<input type="url" class="lp-material--field-external-link" value=""
								   placeholder="<?php esc_attr_e( 'Enter File URL', 'learnpress' ); ?>"/>
						</div>
					</div>
					<input type="hidden" id="current-material-post-id" value="<?php echo esc_attr( $thepostid ); ?>">
					<input type="hidden" id="delete-material-message"
						   value="<?php esc_attr_e( 'Do you want to delete this file?', 'learnpress' ); ?>">
					<input type="hidden" id="delete-material-row-text"
						   value="<?php esc_attr_e( 'Delete', 'learnpress' ); ?>">
					<table class="lp-material--table">
						<?php
						$class_hidden_thead = '';
						if ( empty( $uploaded_files ) ) {
							$class_hidden_thead = 'hidden';
						}
						?>
						<thead class="<?php echo esc_attr( $class_hidden_thead ); ?>">
						<tr>
							<th><?php esc_html_e( 'File Title', 'learnpress' ); ?></th>
							<th><?php esc_html_e( 'Method', 'learnpress' ); ?></th>
							<th><?php esc_html_e( 'Action', 'learnpress' ); ?></th>
						</tr>
						</thead>
						<tbody>

						<!-- <?php if ( $course_materials ) : ?>
					<?php foreach ( $course_materials as $row ) : ?>
						<tr data-id="<?php esc_attr_e( $row->file_id ); ?>" data-sort="<?php esc_attr_e( $row->orders ); ?>">
						<td class="sort"><?php esc_attr_e( $row->file_name ); ?></td>
						<td><?php esc_attr_e( ucfirst( $row->method ) ); ?></td>
						<td><a href="javascript:void(0)" class="delete-material-row" data-id="<?php esc_attr_e( $row->file_id ); ?>"><?php esc_html_e( 'Delete', 'learnpress' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?> -->
						</tbody>

					</table>
					<?php if ( $course_materials ) : ?>
						<?php lp_skeleton_animation_html( 3, 100 ); ?>
					<?php endif ?>
					<div id="lp-material--group-container">

					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Remove material when delete course or lesson
		 *
		 * @param int $post_id id of course or lesson
		 *
		 * @author khanhbd
		 * @version 1.0.1
		 * @since 4.2.2
		 */
		public function clear_material_in_post( $post_id ) {
			try {
				$post_type = get_post_type( $post_id );
				if ( ! in_array( $post_type, [ LP_COURSE_CPT, LP_LESSON_CPT ] ) ) {
					return;
				}

				$material_init = LP_Material_Files_DB::getInstance();
				$material_init->delete_material_by_item_id( $post_id );
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ': ' . $e->getMessage() );
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
