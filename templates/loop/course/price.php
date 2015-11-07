<?php
/**
 * Template for displaying course price within the loop
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;
?>

<?php if ( $price_html = $course->get_price_html() ) : ?>

	<span class="course-price"><?php echo $price_html; ?></span>

<?php endif; ?>
