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
<?php echo "=" . $email_heading; learn_press_email_new_line( 2 );?>
<?php printf( __( 'Dear %s', 'learn_press' ), $user_name ); learn_press_email_new_line( 2 ); ?>
<?php printf( __( 'Congratulation! The course you created (%s) is available now.', 'learn_press' ), $course_name ); learn_press_email_new_line( 2 ); ?>
<?php printf( __( 'Visit our website at %s to view your course.', 'learn_press' ), $login_url ); learn_press_email_new_line( 2 ); ?>
<?php _e( 'Best regards,', 'learn_press' ); learn_press_email_new_line( 2 ); ?>
<?php _e( 'Administration', 'learn_press' ); learn_press_email_new_line( 2 ); ?>
<?php echo $footer_text;?>