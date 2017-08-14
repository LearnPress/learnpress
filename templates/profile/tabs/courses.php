<?php
/**
 * User Courses tab
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $profile;
$user = $profile->get_user();
$args = array( 'user' => $user );

$limit   = LP()->settings->get( 'profile_courses_limit', 10 );
$limit   = apply_filters( 'learn_press_profile_tab_courses_all_limit', $limit );
$courses = $user->get( 'courses', array( 'limit' => $limit ) );

$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
$args['courses']   = $courses;
$args['num_pages'] = $num_pages;
?>

<?php if ( $courses ) { ?>
    <div class="learn-press-subtab-content">

        <ul class="learn-press-courses profile-courses courses-list">
			<?php foreach ( $courses as $post ) {
				setup_postdata( $post );
				learn_press_get_template( 'profile/tabs/courses/loop.php', array(
					'user'      => $user,
					'course_id' => $post->ID
				) );
				wp_reset_postdata();
			} ?>
        </ul>

		<?php learn_press_paging_nav( array( 'num_pages' => $num_pages ) ); ?>

    </div>
<?php } else {
	learn_press_display_message( __( 'You haven\'t got any courses yet!', 'learnpress' ) );
} ?>
