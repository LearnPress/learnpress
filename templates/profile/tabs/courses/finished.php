<?php
/**
 * User Courses finished
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$heading = apply_filters( 'learn_press_profile_tab_courses_finished_heading', __( 'Finished', 'learn_press' ) );
$courses = learn_press_get_passed_courses( $user->id );
global $post;
?>

<?php if ( $heading ): ?>

	<h4 class="profile-courses-heading"><?php echo $heading; ?></h4>

<?php endif; ?>

<?php if ( $courses ) : ?>

	<ul class="profile-courses courses-list enrolled">

		<?php foreach ( $courses as $post ): setup_postdata( $post ); ?>

			<?php learn_press_get_template( 'profile/tabs/courses/loop.php' ); ?>

		<?php endforeach; ?>
	</ul>

<?php else: ?>

	<?php learn_press_display_message( __( 'You have not finished any courses yet!', 'learn_press' ) ); ?>

<?php endif ?>

<?php wp_reset_postdata(); // do not forget to call this function here! ?>