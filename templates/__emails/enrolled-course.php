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

<p>
	<?php printf( __( 'You have been enrolled the course <a href="%s">%s</a>.', 'learnpress' ), get_the_permalink( $course->id ), get_the_title( $course->id ) ); ?>
</p>

<p>
	<?php printf( __( 'Please <a href="%s">login</a> and start learning now.', 'learnpress' ), $login_url ); ?>
</p>

<?php do_action( 'learn_press_email_footer', $footer_text ); ?>
