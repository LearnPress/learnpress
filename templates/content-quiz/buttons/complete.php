<?php
$user = learn_press_get_current_user();
$data = $user->get_course_data( get_the_ID() );
$quiz = LP_Global::course_item_quiz();
$item = $data->get_viewing_item();
?>
<a href="<?php echo $quiz->get_question_link( $item->get_current_question() ); ?>">Continue</a>
<button>Complete</button>