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
		<?php
		printf(
			__( 'A new course <a href="%s">%s</a> has submitted is waiting for your approval.', 'learnpress' ),
			get_edit_post_link( $course_id ),
			get_the_title( $course_id )
		);
		?>
	</p>
	<p><?php printf( __( 'Please login and review course.', 'learnpress' ) ); ?></p>

<?php do_action( 'learn_press_email_footer', $footer_text ); ?>