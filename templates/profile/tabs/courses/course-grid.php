<?php
/**
 * Template for displaying own courses in courses tab of user profile page.
 * Edit by Nhamdv
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.9
 */

defined( 'ABSPATH' ) || exit();
?>

<?php if ( $current_page === 1 ) : ?>
<div class="lp-archive-courses">
<ul <?php lp_item_course_class( array( 'profile-courses-list' ) ); ?> data-layout="grid" data-size="3">
<?php endif; ?>

	<?php
	global $post;

	foreach ( $course_ids as $id ) {
		$course = learn_press_get_course( $id );
		$post   = get_post( $id );
		setup_postdata( $post );

		$course_data    = $user->get_course_data( $id );
		$course_results = $course_data->calculate_course_results();
		learn_press_get_template( 'content-course.php' );
	}

	wp_reset_postdata();
	?>

<?php if ( $current_page === 1 ) : ?>
</ul>
</div>
<?php endif; ?>

<?php if ( $num_pages > 1 && $current_page < $num_pages && $current_page === 1 ) : ?>
	<div class="lp_profile_course_progress__nav">
		<button data-paged="<?php echo absint( $current_page + 1 ); ?>" data-number="<?php echo absint( $num_pages ); ?>"><?php esc_html_e( 'View more', 'learnpress' ); ?></button>
	</div>
<?php endif; ?>
