<div class="quiz-question">
	<div class="quiz-question-head">
							<span class="quiz-question-icon">
								<?php echo $question->get_icon(); ?>
							</span>
		<input type="text" class="question-name" name="learn-press-question-name[<?php echo $question->id; ?>]" value="<?php echo esc_attr( $question->post->post_title ); ?>" />

		<p class="quiz-question-actions">
			<a href="" data-action="expand"><?php _e( 'Expand', 'learn_press' ); ?></a>
			<a href="" data-action="collapse"><?php _e( 'Collapse', 'learn_press' ); ?></a>
			<a href="<?php echo get_edit_post_link( $question->id );?>" data-action="edit"><?php _e( 'Edit', 'learn_press' ); ?></a>
			<a href="" data-action="remove"><?php _e( 'Remove', 'learn_press' ); ?></a>
		</p>
	</div>
	<?php $question && $question->admin_interface(); ?>
</div>