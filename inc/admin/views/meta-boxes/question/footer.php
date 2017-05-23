<?php

/**
 * Template for displaying generic footer of question interface
 */
defined( 'ABSPATH' ) or exit();
?>

        <input type="hidden" name="lp-question-data[id]" class="question-id" value="<?php echo $template_data['id']; ?>">
        <input type="hidden" name="lp-question-data[type]" class="question-type" value="<?php echo $template_data['type']; ?>">
        <input type="hidden" name="question-nonce" value="<?php echo wp_create_nonce( 'question-nonce' ); ?>">

        <div class="hide-if-js element-data">
            <?php $question->to_element_data(); ?>
        </div>
    </div>
</div>