<?php
/**
 * Template for displaying price of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/price.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $price_html ) || ! isset( $course ) ) {
	return;
}
?>

<div class="course-price">
	<?php echo $price_html; ?>
</div>

