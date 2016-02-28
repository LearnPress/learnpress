<?php
/**
 * @author  ThimPress
 * @package LearnPress/Tempates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$current_user = wp_get_current_user();
?>
<?php echo "= " . $email_heading . " =\n\n";?>
<?php printf( __( 'Dear %s,', 'learnpress' ), $user_name ); echo "\n\n"; ?>
<?php printf( __( 'Unfortunately! The course you created (%s) isn\'t ready for sale now.', 'learnpress' ), $course_name ); echo "\n\n";?>
<?php printf( __( 'Please login %s and update your course to meet our minimum requirements for quality and/or our policies', 'learnpress' ), $login_url ); echo "\n\n";?>
<?php _e( 'Best regards,', 'learnpress' ); echo "\n\n"; ?>
<?php _e( 'Administration', 'learnpress' ); echo "\n\n";?>
<?php echo $footer_text . "\n\n";?>

