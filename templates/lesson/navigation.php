<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

global $course;
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $course->is( 'viewing' ) != 'lesson' && $course->is( 'viewing' ) != 'quiz' ) {
	return;
}

if( !$course->is_free() && !LP()->user->has('enrolled-course', $course->id ) ){
	return;
}
$buttons = array();
if( $next_item = $course->get_next_item_html() ){
	$buttons[] = $next_item;
}
if( $prev_item = $course->get_prev_item_html() ){
	$buttons[] = $prev_item;
}

if( $buttons ){

	?>
	<div class="course-item-nav"><?php echo join( "\n",$buttons ) ;?></div>
	<?php
}
