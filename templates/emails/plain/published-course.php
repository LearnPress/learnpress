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
<?php echo "=" . $email_heading . "=\n\n";?>
<?php printf( __( 'Dear %s', 'learn_press' ), $user_name ); echo "\n\n"; ?>
<?php printf( __( 'Congratulation! The course you created (%s) is available now.', 'learn_press' ), get_the_title( $course_id ) ); echo "\n\n"; ?>
<?php printf( __( 'Click %s to view your course.', 'learn_press' ), get_the_permalink( $course_id ) ); echo "\n\n"; ?>
<?php _e( 'Best regards,', 'learn_press' ); echo "\n\n"; ?>
<?php _e( 'Administration', 'learn_press' ); echo "\n\n"; ?>
<?php echo $footer_text;?>