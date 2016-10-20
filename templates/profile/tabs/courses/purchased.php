<?php
/**
 * User Courses purchased
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
$heading = apply_filters( 'learn_press_profile_tab_courses_purchased_heading', false );
?>

<?php if ( $heading ): ?>

	<h4 class="profile-courses-heading"><?php echo $heading; ?></h4>

<?php endif; ?>

<?php if ( $courses ) : ?>

	<ul class="learn-press-courses profile-courses courses-list enrolled">

		<?php foreach ( $courses as $post ): setup_postdata( $post ); ?>
			<?php setup_postdata( $post ); ?>
			<?php learn_press_get_template( 'profile/tabs/courses/loop.php', array( 'subtab' => 'purchased', 'user' => $user, 'course_id' => $post->ID ) ); ?>

		<?php endforeach; ?>
	</ul>
	<?php learn_press_paging_nav( array( 'num_pages' => $num_pages ) ); ?>

<?php else: ?>

	<?php learn_press_display_message( __( 'You haven\'t purchased any courses yet!', 'learnpress' ) ); ?>

<?php endif ?>

<?php wp_reset_postdata(); // do not forget to call this function here! ?>