<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<?php do_action( 'learn_press_email_header', $email_heading ); ?>

<p><?php printf( __( 'Dear <strong>%s</strong>', 'learn_press' ), $user_name );?></p>
<p><?php printf( __( 'Congratulation! The course you created (%s) is available now.', 'learn_press' ), $course_name );?></p>
<p><?php printf( __( 'Visit our website at %s to view your course.', 'learn_press' ), $login_url );?></p>
<p><?php _e( 'Best regards,', 'learn_press' );?></p>
<p><?php _e( '<em>Administration</em>', 'learn_press' );?></p>

<?php do_action( 'learn_press_email_footer', $footer_text ); ?>