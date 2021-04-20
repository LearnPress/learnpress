<?php
class LP_Meta_Box_Lesson extends LP_Meta_Box {

	private static $_instance = null;

	public $post_type = LP_LESSON_CPT;

	public function add_meta_box() {
		add_meta_box( 'lesson_settings', esc_html__( 'Lesson Settings', 'learnpress' ), array( $this, 'output' ), $this->post_type, 'normal', 'high' );
	}

	public function metabox( $post_id = 0 ) {
		return apply_filters(
			'lp/metabox/lesson/lists',
			array(
				'_lp_duration' => new LP_Meta_Box_Duration_Field(
					esc_html__( 'Duration', 'learnpress' ),
					'',
					'0',
					array(
						'default_time'      => 'minute',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				),
				'_lp_preview'  => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Preview', 'learnpress' ),
					esc_html__( 'Students can view this lesson content without taking the course.', 'learnpress' ),
					'no'
				),
			)
		);
	}

	public function output( $post ) {
		parent::output( $post );
		?>

		<div class="lp-meta-box lp-meta-box--lesson">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/lesson-settings/before' );
				// Check if add_filter to old version.
				$is_old = false;

				foreach ( $this->metabox( $post->ID ) as $key => $object ) {
					if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
						$object->id = $key;
						echo $object->output( $post->ID );
					} elseif ( is_array( $object ) ) {
						$is_old = true;
					}
				}

				if ( $is_old ) {
					lp_meta_box_output( $this->metabox( $post->ID ) );
				}

				do_action( 'learnpress/lesson-settings/after' );
				?>
			</div>
		</div>

		<?php
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_Meta_Box_Lesson::instance();
