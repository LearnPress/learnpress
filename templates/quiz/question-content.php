<?php
/**
 * Template for displaying content of quiz's question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 */
if ( !isset( $quiz ) ) {
	return;
}
LP()->quiz = $quiz;
$question = $quiz->get_current_question();
if(!$question){
	return;
}
?>
<div class="question-content">
	<?php
	$question->render();
	?>

</div>
