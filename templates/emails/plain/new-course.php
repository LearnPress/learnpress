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
<?php printf( __( 'A new course "%s" has submitted is waiting for your approval.', 'learn_press' ), get_the_title( $course_id ) ); echo "\n\n"; ?>
<?php printf( __( 'Please login and review course at %s.', 'learn_press' ), get_edit_post_link( $course_id ) ); echo "\n\n"; ?>
<?php echo $footer_text . "\n\n";?>
