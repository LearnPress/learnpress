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
<?php do_action( 'learn_press_email_header', $email_heading ); ?>

<p><?php printf( __( 'A new course <a href="%s">%s</a> has submitted is waiting for your approval.', 'learn_press' ), $course_edit_link, $course_name ); ?></p>
<p><?php printf( __( 'Please <a href="%s">login</a> to review and approval.', 'learn_press' ), $login_url ); ?></p>

<?php do_action( 'learn_press_email_footer', $footer_text ); ?>