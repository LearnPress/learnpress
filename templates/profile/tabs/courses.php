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

$courses = $profile->query_courses();
/*
$user = $profile->get_user();
$args = array( 'user' => $user );


return;
$limit   = LP()->settings->get( 'profile_courses_limit', 10 );
$limit   = apply_filters( 'learn_press_profile_tab_courses_all_limit', $limit );
$courses = $user->get( 'courses', array( 'limit' => $limit ) );

$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
$args['courses']   = $courses;
$args['num_pages'] = $num_pages;*/
?>

<?php if ( $courses ) { ?>
    <div class="learn-press-subtab-content">

        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Enrolled</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $courses as $user_course ) { ?>
				<?php $course = learn_press_get_course( $user_course->get_id() ); ?>
                <tr>
                    <td><?php echo $course->get_title(); ?></td>
                    <td><?php print_r( $user_course->get_results() ); ?></td>
                    <td><?php echo $user_course->get_results( 'status' ); ?> </td>
                </tr>

			<?php } ?>
            </tbody>
        </table>


		<?php learn_press_paging_nav( array( 'num_pages' => $num_pages ) ); ?>

    </div>
<?php } else {
	learn_press_display_message( __( 'You haven\'t got any courses yet!', 'learnpress' ) );
} ?>
