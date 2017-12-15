<?php
/**
 * Template for displaying price of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/price.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user   = LP_Global::user();
$course = LP_Global::course();

if ( ! $price = $course->get_price_html() ) {
	return;
}
?>

<div class="course-price">

	<?php if ( $course->has_sale_price() ) { ?>

        <span class="origin-price"> <?php echo $course->get_origin_price_html(); ?></span>

	<?php } ?>

    <span class="price"><?php echo $price; ?></span>

</div>

