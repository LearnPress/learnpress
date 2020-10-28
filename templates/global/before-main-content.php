<?php
/**
 * Template for displaying before main content.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/before-main-content.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */


defined( 'ABSPATH' ) || exit();
?>

<?php $user = learn_press_get_current_user(); ?>

<?php if ( learn_press_is_course() ) { ?>

<div id="lp-single-course" class="lp-single-course">

	<?php if ( ! learn_press_get_page_link( 'checkout' ) && ( $user->is_admin() || $user->is_instructor() ) ) { ?>

		<?php
		$message = __( 'LearnPress <strong>Checkout</strong> page is not set up. ', 'learnpress' );

		if ( $user->is_instructor() ) {
			$message .= __( 'Please contact administrator for setting up this page.', 'learnpress' );
		} else {
			$message .= sprintf( __( 'Please <a href=\"%s\" target=\"_blank\">setup</a> it so users can purchase courses.', 'learnpress' ), admin_url( 'admin.php?page=learn-press-settings&tab=checkout' ) );
		}
		?>

		<?php learn_press_display_message( $message, 'error' ); ?>

	<?php } ?>

	<?php } else { ?>

	<div id="lp-archive-courses" class="lp-archive-courses">

		<?php } ?>
