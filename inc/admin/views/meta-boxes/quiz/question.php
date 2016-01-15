<?php
global $post;
if( !isset( $hidden ) ){
	$hidden = array();
}
$is_hidden = $question->id && is_array($hidden) && in_array( $question->id, $hidden );
?>
<div class="quiz-question<?php echo $is_hidden ? ' is-hidden' : '';?>">
	<div class="quiz-question-head">
		<span class="quiz-question-icon">
			<?php echo $question->get_icon(); ?>
		</span>
		<input type="text" class="question-name" name="learn-press-question-name[<?php echo $question->id; ?>]" value="<?php echo esc_attr( $question->post->post_title ); ?>" />
		<p class="quiz-question-actions lp-button-actions">
			<a href="" data-action="expand" class="dashicons dashicons-plus <?php echo $is_hidden ? '' : 'hide-if-js';?>" title="<?php _e( 'Expand', 'learn_press' ); ?>"></a>
			<a href="" data-action="collapse" class="dashicons dashicons-minus <?php echo !$is_hidden ? '' : 'hide-if-js';?>" title="<?php _e( 'Collapse', 'learn_press' ); ?>"></a>
			<a href="<?php echo get_edit_post_link( $question->id );?>" class="dashicons dashicons-edit" data-action="" title="<?php _e( 'Edit', 'learn_press' ); ?>"></a>
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=learnpress_remove_quiz_question&quiz_id=' . $post->ID . '&question_id=' . $question->id ), 'remove_quiz_question', 'remove-nonce' );?>"
			   class="dashicons dashicons-trash"
			   data-action="remove"
			   data-confirm-remove="<?php _e( 'Are you sure you want to remove this question?', 'learn_press' );?>"
			   title="<?php _e( 'Remove', 'learn_press' ); ?>"></a>
			<a href="" class="move"></a>
		</p>
	</div>
	<div class="quiz-question-content<?php echo $is_hidden ? ' hide-if-js' : '';?>">
	<?php $question && $question->admin_interface(); ?>
	</div>
</div>