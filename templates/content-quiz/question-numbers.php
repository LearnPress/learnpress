<?php
$course    = LP_Global::course();
$quiz      = LP_Global::course_item_quiz();
$questions = $quiz->get_questions();
$position  = 0;
?>
<ul class="question-numbers">
	<?php foreach ( $questions as $question_id ) {
		$position ++; ?>
        <li<?php echo $quiz->is_viewing_question( $question_id ) ? ' class="current"' : ''; ?> >
            <a href="<?php echo $quiz->get_question_link( $question_id ); ?>"><?php echo $position; ?></a>
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
