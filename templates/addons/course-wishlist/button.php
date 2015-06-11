<?php
$user_id = get_current_user_id();
$course_id = get_the_ID();
$wish_list = get_user_meta( $user_id, '_lpr_wish_list', true );
if ( !$wish_list ) {
	$wish_list = array();
}
if ( in_array( $course_id, $wish_list ) ) {
	$class = 'course-wishlisted';
} else {
	$class = 'course-wishlist';
}
do_action('learn_press_before_wishlist_button');
printf(
	'<span class="dashicons dashicons-heart %s" course-id="%s"></span>',
	$class,
	$course_id
);
do_action('learn_press_after_wishlist_button');