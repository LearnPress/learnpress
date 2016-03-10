<?php
wp_die( 'Something went wrong');
$post_id = $this->get( 'ID' );
settype( $args, 'array' );
$is_collapse = array_key_exists( 'toggle', $args ) && !$args['toggle'];

$questions = learn_press_question_types();
?>
<div class="learn-press-question learn-press-question-<?php echo preg_replace( '!_!', '-', $this->get_type() ); ?>" data-id="<?php echo $this->id; ?>">
	<div class="question-head">
		<p>
			<a href="<?php echo get_edit_post_link( $this->id ); ?>"><?php _e( 'Edit', 'learnpress' ); ?></a>
			<a href="" data-action="remove"><?php _e( 'Remove', 'learnpress' ); ?></a>
			<a href="" data-action="expand" class="<?php echo !$is_collapse ? "hide-if-js" : ""; ?>"><?php _e( 'Expand', 'learnpress' ); ?></a>
			<a href="" data-action="collapse" class="<?php echo $is_collapse ? "hide-if-js" : ""; ?>"><?php _e( 'Collapse', 'learnpress' ); ?></a>
			<a href="" class="move">asdasdadsaasdsad</a>
		</p>
		<select name="learn_press_question[<?php echo $post_id; ?>][type]" data-type="<?php echo $this->get_type(); ?>">
			<?php if ( $questions ) foreach ( $questions as $slug => $name ): ?>
				<option value="<?php echo $slug; ?>" <?php selected( $this->get_type() == $slug ? 1 : 0, 1 ); ?>>
					<?php echo $name; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<input class="question-title" type="text" name="learn_press_question[<?php echo $this->id; ?>][text]" value="<?php echo esc_attr( $this->get( 'post.post_title' ) ); ?>" />
	</div>
	<div class="question-content<?php echo $is_collapse ? " hide-if-js" : ""; ?>">
