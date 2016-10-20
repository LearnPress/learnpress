<?php
/**
 * User Courses enrolled
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
} ?>

<li class="course">
	<?php
	do_action( 'learn_press_before_profile_tab_' . $subtab . '_loop_course' );

	learn_press_get_template( 'profile/tabs/courses/index.php' );

	do_action( 'learn_press_after_profile_tab_' . $subtab . '_loop_course', $user, $course_id );
	?>
</li>

<?php
