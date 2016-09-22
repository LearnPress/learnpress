<?php
/**
 * Template for displaying the enroll button
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;

if ( !$course->is_required_enroll() ) {
	return;
}

$course_status = learn_press_get_user_course_status();
$user          = learn_press_get_current_user();

// only show enroll button if user had not enrolled
$purchase_button_text = apply_filters( 'learn_press_purchase_button_text', __( 'Buy this course', 'learnpress' ) );
$enroll_button_text   = apply_filters( 'learn_press_enroll_button_text', __( 'Enroll', 'learnpress' ) );
$retake_button_text   = apply_filters( 'learn_press_retake_button_text', __( 'Retake', 'learnpress' ) );
$button_retake        = '';
$security             = wp_create_nonce( sprintf( 'learn-press-retake-course-%d-%d', $course->id, $user->id ) );
?>
<div class="learn-press-course-buttons">
	<?php 

	# -------------------------------
	# Finished Course
	# -------------------------------
	if ( $user->has( 'finished-course', $course->id ) ): ?>

		<?php if ( $count = $user->can( 'retake-course', $course->id ) ): ?>
			<?php $button_retake = sprintf( '<button class="button button-retake-course" data-course_id="%d" data-security="%s">%s</button>', $course->id, $security, sprintf( __( 'Retake (+%d)', 'learnpress' ), $count ) ); ?>
		<?php endif; ?>

		<?php learn_press_display_message( sprintf( __( 'Congratulations! You have finished this course%s', 'learnpress' ), $button_retake ) ); ?>

	<?php 
	
	# -------------------------------
	# Enrolled Course
	# -------------------------------
	elseif ( $user->has( 'enrolled-course', $course->id ) ): ?>

		<?php
		$can_finish = $user->can( 'finish-course', $course->id );
		$nonce      = wp_create_nonce( sprintf( 'learn-press-finish-course-%d-%d', $course->id, $user->id ) );
		?>

		<?php if ( $can_finish /*&& ( $message = apply_filters( 'learn_press_finish_course_message', __( 'Congrats! You can finish this course right now.', 'learnpress' ) ) ) !== false */): ?>

			<?php //learn_press_display_message( $message ); ?>
			<button id="learn-press-finish-course" class="button-finish-course" data-course_id="<?php echo esc_attr( $course->id ); ?>" data-security="<?php echo esc_attr( $nonce ); ?>">
				<?php _e( 'Finish course', 'learnpress' ); ?>
			</button>
		<?php endif; ?>
<?php 
	elseif ( $user->can( 'enroll-course', $course->id ) ) : 
//	elseif ( $user->can( 'enroll-course', $course->id ) && ( $course->is_free() || $user->has_purchased_course($course->id) ) ) : 
?>
		<form name="enroll-course" class="enroll-course" method="post" enctype="multipart/form-data">
			<?php do_action( 'learn_press_before_enroll_button' ); ?>

			<input type="hidden" name="lp-ajax" value="enroll-course" />
			<input type="hidden" name="enroll-course" value="<?php echo $course->id; ?>" />
			<input type="hidden" name="_wp_http_referer" value="<?php echo get_the_permalink(); ?>" />
			<button class="button enroll-button"><?php echo $enroll_button_text; ?></button>

			<?php do_action( 'learn_press_after_enroll_button' ); ?>
		</form>
	<?php 

	elseif ( $user->can( 'purchase-course', $course->id ) ) : ?>
		<!--
	<?php if ( LP()->cart->has_item( $course->id ) ) { ?>
		<?php if ( learn_press_is_enable_cart() ): ?>
			<?php learn_press_display_message( sprintf( __( 'This course is already added to your cart <a href="%s" class="button view-cart-button">%s</a>', 'learnpress' ), learn_press_get_page_link( 'cart' ), __( 'View Cart', 'learnpress' ) ) ); ?>
		<?php else: ?>
			<?php learn_press_display_message( sprintf( __( 'You have selected course. <a href="%s" class="button view-cart-button">%s</a>', 'learnpress' ), learn_press_get_page_link( 'checkout' ), __( 'Process Checkout', 'learnpress' ) ) ); ?>
		<?php endif; ?>
	<?php } else {
		} ?>


	<form name="purchase-course" class="purchase-course" method="post" enctype="multipart/form-data">
		<?php do_action( 'learn_press_before_purchase_button' ); ?>
		<input type="hidden" name="_wp_http_referer" value="<?php echo get_the_permalink(); ?>" />
		<input type="hidden" name="add-course-to-cart" value="<?php echo $course->id; ?>" />
		<button class="button purchase-button"><?php echo $purchase_button_text; ?></button>
		<a class="button view-cart-button hide-if-js" href="<?php echo learn_press_get_page_link( 'cart' ); ?>"><?php esc_html_e( 'View cart', 'learnpress' ); ?></a>
		<?php do_action( 'learn_press_after_purchase_button' ); ?>
	</form>
	-->

		<form name="purchase-course" class="purchase-course" method="post" enctype="multipart/form-data">
			<?php do_action( 'learn_press_before_purchase_button' ); ?>
			<input type="hidden" name="_wp_http_referer" value="<?php echo get_the_permalink(); ?>" />
			<!--<input type="hidden" name="add-course-to-cart" value="<?php echo $course->id; ?>" />-->
			<input type="hidden" name="purchase-course" value="<?php echo $course->id; ?>" />
			<button class="button purchase-button"><?php echo $purchase_button_text; ?></button>
			<?php do_action( 'learn_press_after_purchase_button' ); ?>
		</form>

	<?php else: ?>

		<?php learn_press_display_message( apply_filters( 'learn_press_user_can_not_purchase_course_message', __( 'Sorry, you can not purchase this course', 'learnpress' ), $course, $user ) ); ?>

	<?php endif; ?>

</div>