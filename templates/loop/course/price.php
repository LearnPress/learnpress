<?php
/**
 * Template for displaying course price within the loop
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$course = LP()->global['course'];
?>
<div class="course-price">
	<?php if ( $price_html = $course->get_price_html() ) : ?>
		<?php
		if ( $course->get_origin_price() != $course->get_price() ) {
			$origin_price_html = $course->get_origin_price_html();
			?>
            <span class="origin-price"><?php echo $origin_price_html; ?></span>
			<?php
		}
		?>

        <span class="price"><?php echo $price_html; ?></span>

	<?php endif; ?>
</div>