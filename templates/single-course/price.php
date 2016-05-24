<?php
/**
 * Template for displaying the price of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;

if ( learn_press_is_enrolled_course() ) {
	return;
}

?>
<?php if ( $price_html = $course->get_price_html() ) : ?>

	<span class="course-price"><?php echo $price_html; ?></span>

<?php endif; ?>
