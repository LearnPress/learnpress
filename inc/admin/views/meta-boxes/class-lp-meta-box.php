<?php

/**
 * LP Admin Metabox
 *
 * @author Nhamdv
 * @version 4.0.0
 */
abstract class LP_Meta_Box {

	private static $saved_meta_boxes = false;

	public $post_type = LP_COURSE_CPT;

	/**
	 * LP_Meta_Box constructor.
	 */
	public function __construct() {
		$this->includes();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 100, 2 );
		add_action( 'learnpress_save_' . $this->post_type . '_metabox', array( $this, 'save' ) );

		add_action( 'learnpress_save_lp_course_metabox', 'LP_Meta_Box_Course::save_eduma_child_metabox_v3', 10 );

		add_action( 'wp_ajax__lp_save_materials', array( $this, 'lp_save_materials' ) );
	}

	// Include fields.
	public function includes() {
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/class-lp-meta-box-fields.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/text.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/duration.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/checkbox.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/textarea.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/file.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/radio.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/select.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/extra.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/extra-faq.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/date.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/wysiwyg.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/repeater.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/autocomplete.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/fields/materials.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/lp-meta-box-functions.php';
	}

	public function add_meta_box() {}

	public function metabox( $post_id ) {
		return array();
	}

	public function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
	}

	public function save( $post_id ) {
		if ( ! empty( $this->metabox( $post_id ) ) ) {
			foreach ( $this->metabox( $post_id ) as $key => $object ) {
				if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
					$object->id = $key;
					$object->save( $post_id );
				}
			}
		}
	}

	/**
	 * @param id      $post_id
	 * @param WP_Post $post
	 */
	public function save_meta_boxes( $post_id = 0, $post = null ) {
		$post_id = absint( $post_id );

		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		if ( empty( $_POST['learnpress_meta_box_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['learnpress_meta_box_nonce'] ), 'learnpress_save_meta_box' ) ) {
			return;
		}

		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		self::$saved_meta_boxes = true;

		do_action( 'learnpress_save_' . $post->post_type . '_metabox', $post_id, $post );
	}
	public function lp_save_materials() {


		$max_file_size = LP_Settings::get_option( 'material_max_file_size' );
		print_r( $_POST['data'][0] );
		print_r( $_FILES['file'] );
		echo count($_FILES['file']['name']);
		$data =  $_POST['data'];
		update_post_meta( $_POST['post_id'], '__save_lp_material_test', $data );
		wp_send_json_success(
			array(
				'call' => 'From some API/trigger', 
			    'post_id' => $_POST['post_id'],
			    'data'	=> $_POST['data']
			),
			200
		);
	}
}
