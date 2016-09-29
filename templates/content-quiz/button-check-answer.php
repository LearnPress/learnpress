<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
?>

<div class="quiz-question-answer">
	<button type="button" data-nav="answer" class="check_answer"><?php echo apply_filters( 'learn_press_check_answer_button', __( 'Check answer', 'learnpress' ) ); ?></button>
</div>