<?php
/**
 * Template for displaying the price of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.4.2
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course = LP()->global['course'];

if ( learn_press_is_enrolled_course() ) {
	return;
}
?>
<?php if ( $price = $course->get_price_html() ) {

	$origin_price = $course->get_origin_price_html();
	if ( $course->get_sale_price() !== ''/* $price != $origin_price */ ) {
		echo '<span class="course-origin-price">' . $origin_price . '</span>';
	}
	echo '<span class="course-price">' . $price . '</span>';
}
?>
