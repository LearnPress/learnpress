<div class="learn-press-question" id="learn-press-question-<?php echo $question->get_id(); ?>" data-type="none"
     data-id="<?php echo $question->get_id(); ?>">

	<?php printf( __( 'Function %s should override from its child (%s)', 'learnpress' ), 'admin_interface', get_class( $question ) ); ?>

	<?php _e( 'Please select a type for this question' ); ?>

    <p class="question-bottom-actions">
		<?php
		$buttons = apply_filters(
			'learn_press_question_bottom_buttons',
			array(
				'change_type' => learn_press_dropdown_question_types( array( 'echo'     => false,
				                                                             'id'       => 'learn-press-dropdown-question-types-' . $question->get_id(),
				                                                             'selected' => 'none'
				) )
			),
			$this
		);
		echo join( "\n", $buttons );
		?>
    </p>
</div>