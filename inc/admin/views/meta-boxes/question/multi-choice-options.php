<div class="learn-press-question" id="learn-press-question-<?php echo $this->id;?>" data-type="multi-choice" data-id="<?php echo $this->id;?>">

	<p class="question-bottom-actions">
		<?php
		$top_buttons = apply_filters(
			'learn_press_question_top_buttons',
			array(
				'change_type' => learn_press_dropdown_question_types(array('echo' => false, 'id' => 'learn-press-dropdown-question-types-' . $this->id, 'selected' => $this->type ))
			),
			$this
		);
		echo join( "\n", $top_buttons );
		?>
	</p>
	<table class="lp-sortable lp-list-options" id="learn-press-list-options-<?php echo $this->id;?>">
		<thead>

		<th><?php _e( 'Answer Text', 'learnpress' ); ?></th>
		<th><?php _e( 'Is Correct?', 'learnpress' ); ?></th>
		<th width="20"></th>
		<th width="20"></th>
		</thead>
		<tbody>

		<?php $answers = $this->answers; if ( $answers ): ?>
			<?php foreach ( $answers as $answer ): ?>
				<?php
				$value = $this->_get_option_value( $answer['value'] );
				?>

				<?php do_action( 'learn_press_before_question_answer_option', $this ); ?>

				<tr class="lp-list-option lp-list-option-<?php echo $value;?>" data-id="<?php echo $value;?>">

					<td>
						<input class="lp-answer-text no-submit key-nav" type="text" name="learn_press_question[<?php echo $this->id; ?>][answer][text][]" value="<?php echo esc_attr( $answer['text'] ); ?>" />
					</td>
					<th class="lp-answer-check">
						<input type="hidden" name="learn_press_question[<?php echo $this->id; ?>][answer][value][]" value="<?php echo $value; ?>" />
						<input type="checkbox" name="learn_press_question[<?php echo $this->id; ?>][checked][]" <?php checked( $answer['is_true'] == 'yes', true ); ?> value="<?php echo $value; ?>" />
					</th>
					<td class="lp-list-option-actions lp-remove-list-option">
						<i class="dashicons dashicons-trash"></i>
					</td>
					<td class="lp-list-option-actions lp-move-list-option open-hand">
						<i class="dashicons dashicons-sort"></i>
					</td>
				</tr>

				<?php do_action( 'learn_press_after_question_answer_option', $this ); ?>

			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p class="question-bottom-actions">
		<?php
		$bottom_buttons = apply_filters(
			'learn_press_question_bottom_buttons',
			array(
				'add_option' => sprintf(
					__( '<button class="button add-question-option-button add-question-option-button-%1$d" data-id="%1$d" type="button">%2$s</button>', 'learnpress' ),
					$this->id,
					__( 'Add Option', 'learnpress' )
				)
			),
			$this
		);
		echo join( "\n", $bottom_buttons );
		?>
	</p>
	</div>
<script type="text/javascript">
	jQuery(function ($) {
		LP.sortableQuestionAnswers($('#learn-press-question-<?php echo $this->id;?>'));
	});
</script>