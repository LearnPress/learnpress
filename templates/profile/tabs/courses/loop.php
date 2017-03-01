<?php
/**
 * User Courses enrolled
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.4.2
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
$course_id = absint( $course_id );
/* Check status course */
$all_status = learn_press_get_subtabs_course();
$class_loop = array( 'course' );
$str_status = '';

foreach ( $all_status as $key => $status ) {

	$slug      = preg_replace( '/\s+|-+/', '_', $key );
	$is_status = false;

	switch ( $key ) {

		case 'purchased':
			$is_status = $user->has_course_status( $course_id, array( 'enrolled' ) );
			break;

		case 'learning':
			$is_status = $user->has_course_status( $course_id, array( 'started' ) );
			break;

		case 'finished':
			$is_status = $user->has_finished_course( $course_id );
			break;

		case 'own':
			/*$own_courses = $user->get_own_courses();
			if ( array_key_exists( $course_id, $own_courses ) ) {
				$is_status = true;
			}*/
			$is_status = absint( get_post_field( 'post_author' ) ) == $course_id;
			break;

	}

	if ( $is_status ) {
		$class_loop[] = 'learnpress-status-' . esc_attr( $slug );
	}

	if ( in_array( 'learnpress-status-finished', $class_loop ) ) {
//        $str_status =  __( 'Review', 'learnpress' );
	} else if ( in_array( 'learnpress-status-purchased', $class_loop ) ) {
		$str_status = __( 'Continue', 'learnpress' );
	}
}

?>

	<li class="<?php echo implode( ' ', $class_loop ); ?>">
		<?php
		do_action( 'learn_press_before_profile_loop_course', $user, $course_id );

		learn_press_get_template( 'profile/tabs/courses/index.php' );

		do_action( 'learn_press_after_profile_loop_course', $user, $course_id );

		// Print string "Start Course"
		if ( !empty( $str_status ) ) {
			echo apply_filters( 'learn_press_after_profile_loop_course_text_detail', '<a href="' . get_the_permalink() . '" class="view-more">' . wp_kses_post( $str_status ) . '</a>', $str_status, $course_id, $user );
		}
		?>
	</li>

<?php
