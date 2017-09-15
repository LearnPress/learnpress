<?php
$course    = LP_Global::course();
$quiz      = LP_Global::course_item_quiz();
$questions = $quiz->get_questions();
$user      = LP_Global::user();
$quiz_data = $user->get_quiz_data( $quiz->get_id() );
$result    = $quiz_data->get_result();
$position  = 0;
?>
<ul class="question-numbers">
	<?php foreach ( $questions as $question_id ) {
		$position ++;
		$class = array( "question-" . $position );
		if ( $quiz->is_viewing_question( $question_id ) ) {
			$class[] = 'current';
		}

		if ( $quiz_data->is_answered_true( $question_id ) ) {
			$class[] = 'answered';
			$class[] = 'answered-true';
		}elseif($quiz_data->is_answered( $question_id )){
			$class[] = 'answered';
			$class[] = 'answered-wrong';
        }
		?>
        <li class="<?php echo join( ' ', $class ); ?>">
            <a href="<?php echo $quiz->get_question_link( $question_id ); ?>">
                <span><?php echo $position; ?></span>
            </a>
        </li>
	<?php } ?>
</ul>

<div id="content-item-nav">
    <div class="content-item-nav-wrap">
        <form>
            <a href="<?php echo $course->get_next_item(); ?>">Prev</a>
            <button>Next</button>
        </form>
    </div>
</div>
