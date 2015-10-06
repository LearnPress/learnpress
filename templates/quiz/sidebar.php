<?php
learn_press_prevent_access_directly();
if ( ! learn_press_user_can_view_quiz() ) {
    return;
}
?>
<?php if( learn_press_get_quiz_questions() ):?>
<div class="quiz-sidebar">
    <?php do_action( 'learn_press_content_quiz_sidebar' );?>
</div>
<?php endif;?>