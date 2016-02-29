<?php
/**
 * User Courses own
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !user_can( $user->ID, 'edit_lp_courses' ) ){
	return;
}
global $post;
$heading = apply_filters( 'learn_press_profile_tab_courses_own_heading', false );
?>

<?php if ( $heading ): ?>

	<h4 class="profile-courses-heading"><?php echo $heading; ?></h4>

<?php endif; ?>

<?php if ( $courses ) : ?>

	<ul class="profile-courses courses-list own">

	<?php foreach( $courses as $post ): ?>

		<?php learn_press_get_template( 'profile/tabs/courses/loop.php', array( 'subtab' => 'own' ) ); ?>

	<?php endforeach; ?>

	</ul>

<?php else: ?>

	<?php learn_press_display_message( __( 'You haven\'t got any published courses yet!', 'learnpress' ) ); ?>

<?php endif ?>

<?php wp_reset_postdata(); // do not forget to call this function here! ?>