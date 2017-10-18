<?php
/**
 * User own courses tab.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$profile = learn_press_get_profile();
$query   = $profile->query_courses( 'own', array( 'status' => LP_Request::get_string( 'filter-status' ) ) );

?>
<div class="learn-press-subtab-content">
    <h3 class="profile-heading">
		<?php _e( 'My Courses', 'learnpress' ); ?>
    </h3>

	<?php if ( $filters = $profile->get_own_courses_filters( LP_Request::get_string( 'filter-status' ) ) ) { ?>
        <ul class="lp-sub-menu">
			<?php foreach ( $filters as $class => $link ) { ?>
                <li class="<?php echo $class; ?>"><?php echo $link; ?></li>
			<?php } ?>
        </ul>
	<?php } ?>

	<?php
	if ( ! $query['total'] ) {
		learn_press_display_message( __( 'No courses!', 'learnpress' ) );
	} else {
		?>

        <ul class="learn-press-courses profile-courses-list">
			<?php
			global $post;
			foreach ( $query['items'] as $item ) {
				$course = learn_press_get_course( $item );
				$post   = get_post( $item );
				setup_postdata( $post );
				learn_press_get_template( 'content-course.php' );
			}
			wp_reset_postdata();
			?>
        </ul>

		<?php $query->get_nav( '', true ); ?>

	<?php } ?>
</div>
