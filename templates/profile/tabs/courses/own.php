<?php
/**
 * User Courses own
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !user_can( $user->ID, 'edit_lp_courses' ) ) {
	return;
}
global $post, $wp;
$heading = apply_filters( 'learn_press_profile_tab_courses_own_heading', false );
$paged   = !empty( $_REQUEST['section'] ) && $_REQUEST['section'] == $subtab ? get_query_var( 'paged' ) : 1;
?>

<?php if ( $heading ): ?>

	<h4 class="profile-courses-heading"><?php echo $heading; ?></h4>

<?php endif; ?>

<?php if ( $courses ) : ?>

	<ul class="profile-courses courses-list own">

		<?php foreach ( $courses as $post ): ?>
			<?php setup_postdata( $post ); ?>
			<?php learn_press_get_template( 'profile/tabs/courses/loop.php', array( 'subtab' => 'own' ) ); ?>

		<?php endforeach; ?>

	</ul>
	<?php
	learn_press_paging_nav(
		array(
			'num_pages' => $num_pages,
			'paged'     => $paged,
			//'base' => add_query_arg( array( 'section' => $subtab ), learn_press_user_profile_link( $user->id, learn_press_get_current_profile_tab() ) )
		)
	);
	?>

<?php else: ?>

	<?php learn_press_display_message( __( 'You haven\'t got any published courses yet!', 'learnpress' ) ); ?>

<?php endif ?>

<?php wp_reset_postdata(); // do not forget to call this function here! ?>