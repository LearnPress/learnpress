<?php
/**
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$current_user = wp_get_current_user();
?>
<?php printf( __( 'Dear %s,', 'learn_press' ), $current_user->user_login );?>
<?php echo "\n\n";?>
<?php printf( __( 'Congratulation! The course you created (<a href="%s">%s</a>) is available now.', 'learn_press' ), $course_link, $course_name );?>
<?php echo "\n\n";?>
<?php printf( __( 'Visit our website at %s', 'learn_press' ), get_site_url() );?>.
<?php echo "\n\n";?>
<?php _e( 'Best regards, <br /><em>Administration</em>', 'learn_press' );?>