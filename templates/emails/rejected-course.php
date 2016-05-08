<?php
/**
 * @author  ThimPress
 * @package LearnPress/Tempates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$user_course = learn_press_get_course_user( $course->id );

?>
<?php do_action( 'learn_press_email_header', $email_heading ); ?>

	<p><?php printf( __( 'Dear <strong>%s</strong>', 'learnpress' ), $user_course->display_name ); ?></p>
	<p><?php printf( __( 'Unfortunately! The course you created (%s) isn\'t ready for sale now.', 'learnpress' ), get_the_title( $course->id ) ); ?></p>
	<p><?php printf( __( 'Please <a href="%s">login</a> and update your course to meet our minimum requirements for quality and/or our policies', 'learnpress' ), $login_url ); ?></p>
	<p><?php _e( 'Best regards,', 'learnpress' ); ?></p>
	<p><?php _e( '<em>Administration</em>', 'learnpress' ); ?></p>

<?php do_action( 'learn_press_email_footer', $footer_text ); ?>