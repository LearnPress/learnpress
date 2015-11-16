<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php echo "= " . $email_heading . " =\n\n";?>
<?php printf( __( 'A new course <a href="%s">%s</a> has submitted is waiting for your approval.', 'learn_press' ), $course_edit_link, $course_name ); echo "\n\n"; ?>
<?php printf( __( 'Please login link %s to review and approval.', 'learn_press' ), $login_url ); echo "\n\n"; ?>
<?php echo $footer_text . "\n\n";?>
