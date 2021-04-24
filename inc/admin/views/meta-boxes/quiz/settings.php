<?php
class LP_Meta_Box_Quiz extends LP_Meta_Box {

	private static $_instance = null;

	public $post_type = LP_QUIZ_CPT;

	public function add_meta_box() {
		add_meta_box( 'quiz_settings', esc_html__( 'Quiz Settings', 'learnpress' ), array( $this, 'output' ), $this->post_type, 'normal', 'high' );
	}

	public function metabox( $post_id = 0 ) {
		return apply_filters(
			'lp/metabox/quiz/lists',
			array(
				'_lp_duration'             => new LP_Meta_Box_Duration_Field(
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
				'_lp_passing_grade'        => new LP_Meta_Box_Text_Field(
					esc_html__( 'Passing Grade(%)', 'learnpress' ),
					esc_html__( 'The condition that must be achieved in order to be passed the quiz.', 'learnpress' ),
					'80',
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
							'max'  => '100',
						),
						'style'             => 'width: 60px;',
					)
				),
				'_lp_instant_check'        => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Instant Check', 'learnpress' ),
					esc_html__( 'Allow students to immediately check their answers while doing the quiz.', 'learnpress' ),
					'no'
				),
				'_lp_negative_marking'     => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Negative Marking', 'learnpress' ),
					esc_html__( 'For each question which students answer wrongly, the total point is deducted exactly the question\'s point', 'learnpress' ),
					'no'
				),
				'_lp_minus_skip_questions' => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Minus for skip', 'learnpress' ),
					esc_html__( 'For each question which students answer skip, the total point is deducted exactly the question\'s point', 'learnpress' ),
					'no'
				),
				'_lp_retake_count'         => new LP_Meta_Box_Text_Field(
					esc_html__( 'Retake', 'learnpress' ),
					esc_html__( 'How many times the user can re-take this quiz. Set 0 to disable.', 'learnpress' ),
					'',
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
							'max'  => '100',
						),
						'style'             => 'width: 60px;',
					)
				),
				'_lp_pagination'           => new LP_Meta_Box_Text_Field(
					esc_html__( 'Pagination', 'learnpress' ),
					esc_html__( 'The number of questions displayed on each page.', 'learnpress' ),
					'1',
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
							'max'  => '100',
						),
						'style'             => 'width: 60px;',
					)
				),
				'_lp_review'               => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Review', 'learnpress' ),
					esc_html__( 'Allow students to review this quiz after they finish the quiz.', 'learnpress' ),
					'yes'
				),
				'_lp_show_correct_review'  => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Show correct answer', 'learnpress' ),
					esc_html__( 'Allow students view correct answer question in review this quiz.', 'learnpress' ),
					'yes'
				),
			)
		);
	}

	public function output( $post ) {
		parent::output( $post );
		?>

		<div class="lp-meta-box lp-meta-box--quiz">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/quiz-settings/before' );
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

				do_action( 'learnpress/quiz-settings/after' );
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

LP_Meta_Box_Quiz::instance();
