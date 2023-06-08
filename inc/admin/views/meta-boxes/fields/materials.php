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
		public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
			parent::__construct( $label, $description, $default, $extra );
			
		}
		/**
		 * [output Downloadable Material Tab content in Course Setting Meta Box]
		 * @param  [int] $thepostid [course's post_id]
		 * @return [html]            [content of Download material tab]
		 */
		public function output( $thepostid ) {
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
			<div id="lp-material--group-container">
				
			</div>
			<button class="button button-primary" id="btn-lp--save-material" type="button"><?php esc_html_e( 'Save', 'learnpress' ) ?></button>
			<script type="text/javascript">

				document.getElementById( 'btn-lp--add-material' ).addEventListener( 'click', function( e ) {
					let group_template = document.getElementById( 'lp-material--add-material-template' ).innerHTML;
					document.getElementById( 'lp-material--group-container' ).insertAdjacentHTML( 'beforeend', group_template );
				} );

				document.getElementById( 'downloadable_material_data' ).addEventListener( 'change', function( event ) {
					let target = event.target;
					if ( target.classList.contains( 'lp-material--field-method' ) ) {
						let method = target.value;
						let upload_field_template = document.getElementById( 'lp-material--upload-field-template' ).innerHTML,
							external_field_template = document.getElementById( 'lp-material--external-field-template' ).innerHTML;
						switch ( method ) {
							case 'upload' :
								target.parentNode.insertAdjacentHTML( 'afterend', upload_field_template );
								target.closest( '.lp-material--group' ).querySelector( '.lp-material--external-wrap' ).remove();
								break;
							case 'external' :
								target.parentNode.insertAdjacentHTML( 'afterend', external_field_template );
								target.closest( '.lp-material--group' ).querySelector( '.lp-material--upload-wrap' ).remove();
								break;
						}
						// console.log(target.parentNode)
					}
				} );

				document.getElementById( 'downloadable_material_data' ).addEventListener( 'click', function( event ) {
					let target = event.target;
					if ( target.classList.contains( 'lp-material--delete' ) && target.nodeName == 'BUTTON' ) {
						target.closest( '.lp-material--group' ).remove();
					}
					return false;
				} );
				document.getElementById( 'btn-lp--save-material' ).addEventListener( 'click', function(event) {
					let materials = document.getElementById( 'lp-material--group-container' ).querySelectorAll( '.lp-material--group' );
					let material_data = [];
						
					if ( materials.length > 0 ) {
						let formData = new FormData();
						formData.append( 'action', '_lp_save_materials' );
						materials.forEach( function ( ele, index ) {
							let label = ele.querySelector( '.lp-material--field-title' ).value,
								method = ele.querySelector( '.lp-material--field-method' ).value,
								external_field = ele.querySelector( '.lp-material--field-external-link' ),
								upload_field = ele.querySelector( '.lp-material--field-upload' ), file, link;
							switch ( method ) {
								case 'upload' :
									file = upload_field.files[0].name;
									link = '';
									formData.append( 'file[]', upload_field.files[0] );
									break;
								case 'external' :
									link = external_field.value;
									file = '';
									break;
							}
							material_data.push( { 'label': label, 'method': method, 'file':file, 'link':link } );
						} );

						material_data = JSON.stringify( material_data );
						let url = `<?php echo esc_url(admin_url('admin-ajax.php')) ?>`;
						
						formData.append( 'data', material_data );
						formData.append( 'post_id', <?php esc_attr_e( $thepostid ) ?> );
						fetch( url, {
						    method: 'POST',
						    body: formData,
						} ) // wrapped
						    .then( res => res.text() )
						    .then( data => console.log( data ) )
						    .catch( err => console.log( err ) );
					}
					// console.log( data );
				} );
			</script>
			<?php
		}

	}
}