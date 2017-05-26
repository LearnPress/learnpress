<?php

/**
 * Template for displaying generic footer of question interface
 */
defined( 'ABSPATH' ) or exit();
?>

<input type="hidden" name="learn_press_question[<?php echo $template_data['id']; ?>][type]" class="question-type"
       value="<?php echo $template_data['type']; ?>">
<input type="hidden" name="learn_press_question[<?php echo $template_data['id']; ?>][id]" class="question-id"
       value="<?php echo $template_data['id']; ?>">
<?php if ( $supports = $question->get_supports() ) { ?>
	<?php foreach ( $supports as $k => $v ) { ?>
        <input type="hidden" name="learn_press_question[<?php echo $template_data['id']; ?>][supports][<?php echo $k;?>]" class="question-type"
               value="<?php echo esc_attr($v);?>">
	<?php } ?>
<?php } ?>
<input type="hidden" name="question-nonce" value="<?php echo wp_create_nonce( 'question-nonce' ); ?>">
</div>
</div>