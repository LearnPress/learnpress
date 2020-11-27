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

defined( 'ABSPATH' ) || exit();

$user   = LP_Global::user();
$course = LP_Global::course();

$price = $course->get_price_html();

if ( ! $price ) {
	return;
}
?>

<div class="course-price">
	<?php if ( $course->has_sale_price() ) : ?>
		<span class="origin-price"> <?php echo $course->get_origin_price_html(); ?></span>
	<?php endif; ?>
	<span class="price"><?php echo $price; ?></span>
</div>

