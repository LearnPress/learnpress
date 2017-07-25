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
print_r( $user->start_quiz(9, 17, true));

$a = learn_press_get_user_item_meta(22, 'zzzz', true);
var_dump($a);

$b = metadata_exists('learnpress_user_item', 22, 'zzzz' );
var_dump($b);
?>

<?php do_action( 'learn_press_before_content_landing' ); ?>

<div class="course-landing-summary">

	<?php do_action( 'learn_press_content_landing_summary' ); ?>

</div>

<?php do_action( 'learn_press_after_content_landing' ); ?>
