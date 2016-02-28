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
<?php printf( __( 'Dear %s', 'learnpress' ), $user_name ); echo "\n\n"; ?>
<?php printf( __( 'Congratulation! The course you created (%s) is available now.', 'learnpress' ), get_the_title( $course_id ) ); echo "\n\n"; ?>
<?php printf( __( 'Click %s to view your course.', 'learnpress' ), get_the_permalink( $course_id ) ); echo "\n\n"; ?>
<?php _e( 'Best regards,', 'learnpress' ); echo "\n\n"; ?>
<?php _e( 'Administration', 'learnpress' ); echo "\n\n"; ?>
<?php echo $footer_text;?>