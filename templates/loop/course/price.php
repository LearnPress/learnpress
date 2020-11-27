<?php
/**
 * Template for displaying price of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/price.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();

if ( ! $course ) {
	return;
}

$price_html = $course->get_price_html();
?>

<div class="course-price">

	<?php if ( $price_html ) : ?>

		<?php if ( $course->get_origin_price() != $course->get_price() ) : ?>
			<span class="origin-price"><?php echo $course->get_origin_price_html(); ?></span>
		<?php endif; ?>

		<span class="price"><?php echo $price_html; ?></span>

	<?php endif; ?>
</div>
