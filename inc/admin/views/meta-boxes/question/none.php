<div class="learn-press-question" id="learn-press-question-<?php echo $this->id;?>" data-type="none" data-id="<?php echo $this->id;?>">
	<?php _e( 'Please select a type for this question'); ?>

	<p class="question-bottom-actions">
		<?php
		$buttons = apply_filters(
			'learn_press_question_bottom_buttons',
			array(
				'change_type' => learn_press_dropdown_question_types(array('echo' => false, 'id' => 'learn-press-dropdown-question-types-' . $this->id, 'selected' => 'none' ))
			),
			$this
		);
		echo join( "\n", $buttons );
		?>
	</p>
</div>