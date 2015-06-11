<?php
/**
 *
 */
?>
<?php

$question = learn_press_get_current_question();

?>
<?php do_action( 'learn_press_before_quiz_question_nav' );?>
<div class="quiz-question-nav">

    <?php do_action( 'learn_press_quiz_question_nav' );?>

</div>
<?php do_action( 'learn_press_after_quiz_question_nav' );?>



