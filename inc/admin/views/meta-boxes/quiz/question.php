<?php
$is_hidden = $question->id && is_array($hidden) && in_array( $question->id, $hidden );
print_r($qustion);
?>
<div class="quiz-question<?php echo $is_hidden ? ' is-hidden' : '';?>">
	<div class="quiz-question-head">
		<span class="quiz-question-icon">
			<?php echo $question->get_icon(); ?>
		</span>
		<input type="text" class="question-name" name="learn-press-question-name[<?php echo $question->id; ?>]" value="<?php echo esc_attr( $question->post->post_title ); ?>" />
		<p class="quiz-question-actions lp-button-actions">
			<a href="" data-action="expand"<?php echo $is_hidden ? '' : 'class="hide-if-js"';?>"><?php _e( 'Expand', 'learn_press' ); ?></a>
			<a href="" data-action="collapse"<?php echo !$is_hidden ? '' : 'class="hide-if-js"';?>><?php _e( 'Collapse', 'learn_press' ); ?></a>
			<a href="<?php echo get_edit_post_link( $question->id );?>" data-action="edit"><?php _e( 'Edit', 'learn_press' ); ?></a>
			<a href="" data-action="remove"><?php _e( 'Remove', 'learn_press' ); ?></a>
			<a href="" class="move"></a>
		</p>
	</div>
	<div class="quiz-question-content<?php echo $is_hidden ? ' hide-if-js' : '';?>">
	<?php $question && $question->admin_interface(); ?>
	</div>
</div>