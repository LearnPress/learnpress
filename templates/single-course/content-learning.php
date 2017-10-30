<?php
/**
 * Template for displaying content of learning course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//global $wpdb;
//$query = "select id from {$wpdb->users} where id<=48";
//$users = $wpdb->get_col( $query );
//$curd = new LP_User_CURD();
//
//foreach ( $users as $uid ) {
//	$user = learn_press_get_user( $uid );
//	$data = $user->get_course_data( get_the_ID() );
//	learn_press_debug($user->get_id(), $data ->get_status());
//	echo $curd->get_current_user_order($uid, get_the_ID());
//
//}


?>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_before_content_learning' );
?>
<div class="course-learning-summary">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_content_learning_summary' );

	/**
	 * @since 3.0.0
	 *
	 * @see   learn_press_course_meta_start_wrapper()
	 */
	do_action( 'learn-press/content-learning-summary' );

	?>

</div>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_after_content_learning' );
?>

