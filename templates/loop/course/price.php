<?php
/**
 * Template for displaying price of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/price.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) || ! isset( $price_html ) ) {
	return;
}
?>

<div class="course-price">
	<?php echo $price_html; ?>
</div>
