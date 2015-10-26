<?php
/**
 * User Courses enrolled
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$heading = apply_filters( 'learn_press_profile_tab_courses_enrolled_heading', __( 'Enrolled', 'learn_press' ) );
$courses = learn_press_get_enrolled_courses( $user->id );

?>

<?php if ( $heading ): ?>

	<h4 class="profile-courses-heading"><?php echo $heading; ?></h4>

<?php endif; ?>

<?php if ( $courses->have_posts() ) : ?>

	<ul class="profile-courses courses-list enrolled">

		<?php while ( $courses->have_posts() ) : $courses->the_post(); ?>

			<?php learn_press_get_template( 'profile/tabs/courses/loop.php' ); ?>

		<?php endwhile; ?>
	</ul>

<?php else: ?>

	<?php learn_press_display_message( __( 'You have not taken any courses yet!', 'learn_press' ) ); ?>

<?php endif ?>

<?php wp_reset_postdata(); // do not forget to call this function here! ?>
