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
			echo $thepostid;
			?>
			<button class="button button-primary" id="btn-lp--add-material" type="button"><?php esc_html_e( 'Add Course Materials', 'learnpress' ) ?></button>
			<button class="button button-primary" id="btn-lp--save-material" type="button"><?php esc_html_e( 'Save', 'learnpress' ) ?></button>
			<?php
		}
	}
}