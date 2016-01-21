<?php
/**
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php echo "= " . $email_heading . " =\n\n";?>
<?php printf( __( 'You have been finished course "%s" (%s).', 'learn_press' ), get_the_title( $course_id ), get_the_permalink( $course_id ) ); echo "\n\n"; ?>
<?php printf( __( 'Please login %s and view your course results.', 'learn_press' ), $login_url ); echo "\n\n"; ?>
<?php printf( __( 'Best regards,', 'learn_press' ) ); echo "\n\n"; ?></p>
<?php printf( __( 'Administration', 'learn_press' ) ); echo "\n\n"; ?></p>
<?php echo $footer_text . "\n\n";?>


