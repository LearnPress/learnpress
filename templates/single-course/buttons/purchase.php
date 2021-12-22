<?php
/**
 * Template for displaying Purchase button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/purchase.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) ) {
	$course = learn_press_get_course();
}

$classes_purchase  = 'purchase-course';
$classes_purchase .= ( LP()->checkout()->is_enable_guest_checkout() ) ? ' guest_checkout' : '';

$classes_purchase = apply_filters( 'lp/btn/purchase/classes', $classes_purchase );
?>

<?php do_action( 'learn-press/before-purchase-form' ); ?>

	<form name="purchase-course" class="<?php echo esc_attr( $classes_purchase ); ?>" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/before-purchase-button' ); ?>

		<input type="hidden" name="purchase-course" value="<?php echo esc_attr( $course->get_id() ); ?>"/>

		<button class="lp-button button button-purchase-course">
			<?php echo esc_html( apply_filters( 'learn-press/purchase-course-button-text', esc_html__( 'Buy Now', 'learnpress' ), $course->get_id() ) ); ?>
		</button>

		<?php do_action( 'learn-press/after-purchase-button' ); ?>

	</form>

<?php do_action( 'learn-press/after-purchase-form' ); ?>
