<?php
$user   = learn_press_get_current_user();
$course = LP()->global['course'];
$quiz   = isset( $item ) ? $item : LP()->global['course-item'];
if ( !$quiz ) {
	return;
}
$duration = $quiz->get_duration_html();
if ( strpos( $duration, ':' ) === false ) {
	return;
}
$segs = explode( ':', $duration );
if ( sizeof( $segs ) == 3 ) {
	$text = $segs[0] == '00' ? '88:88' : '88:88:88';
} else {
	$text = '88:88';
}
?>
<div id="quiz-countdown" class="quiz-countdown" data-value="100">
	<div class="countdown" data-total="<?php echo $text; ?>"><span><?php echo $quiz->get_duration_html(); ?></span></div>
</div>