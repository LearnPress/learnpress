<?php
/**
 * Template for displaying content of landing course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


$user = learn_press_get_current_user();
//learn_press_debug($user->get_course_info2(get_the_ID()));
$curd = new LP_User_CURD();
$curd->read_course( $user->get_id(), array( 71, 17, 127 ) );
learn_press_debug( $user->get_course_data( 17 ) );

print_r( $user->start_quiz(290, 17, true));

?>

<?php do_action( 'learn_press_before_content_landing' ); ?>

<div class="course-landing-summary">

	<?php do_action( 'learn_press_content_landing_summary' ); ?>

</div>

<?php do_action( 'learn_press_after_content_landing' ); ?>
