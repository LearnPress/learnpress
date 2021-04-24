<?php
class LP_Meta_Box_Question extends LP_Meta_Box {

	private static $_instance = null;

	public $post_type = LP_QUESTION_CPT;

	public function add_meta_box() {
		add_meta_box( 'question_settings', esc_html__( 'Question Settings', 'learnpress' ), array( $this, 'output' ), $this->post_type, 'normal', 'high' );
	}

	public function metabox( $post_id ) {
		return apply_filters(
			'lp/metabox/question/lists',
			array(
				'_lp_mark'        => new LP_Meta_Box_Text_Field(
					esc_html__( 'Points', 'learnpress' ),
					esc_html__( 'Points for choosing the correct answer.', 'learnpress' ),
					'1',
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
						'style'             => 'width: 60px;',
					)
				),
				'_lp_hint'        => new LP_Meta_Box_Textarea_Field(
					esc_html__( 'Hint', 'learnpress' ),
					esc_html__( 'Instruction for user to select the right answer. The text will be shown when users click the \'Hint\' button.', 'learnpress' ),
					''
				),
				'_lp_explanation' => new LP_Meta_Box_Textarea_Field(
					esc_html__( 'Explanation', 'learnpress' ),
					esc_html__( 'Explanation will be displayed when students click button "Check Answer".', 'learnpress' ),
					''
				),
			)
		);
	}

	public function output( $post ) {
		parent::output( $post );
		?>

		<div class="lp-meta-box lp-meta-box--question">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/question-settings/before' );
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
					lp_meta_box_output( $this->metabox() );
				}

				do_action( 'learnpress/question-settings/after' );
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

LP_Meta_Box_Question::instance();
