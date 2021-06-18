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
<div class="lp_profile_course_progress">
	<div class="lp_profile_course_progress__item lp_profile_course_progress__header">
		<div></div>
		<div><?php esc_html_e( 'Name', 'learnpress' ); ?></div>
		<div><?php esc_html_e( 'Result', 'learnpress' ); ?></div>
		<div><?php esc_html_e( 'Expiration time', 'learnpress' ); ?></div>
		<div><?php esc_html_e( 'End time', 'learnpress' ); ?></div>
	</div>
<?php endif; ?>

	<?php
	global $post;

	foreach ( $course_ids as $id ) {
		$course = learn_press_get_course( $id );
		$post   = get_post( $id );
		setup_postdata( $post );

		$course_data    = $user->get_course_data( $id );
		$course_results = $course_data->calculate_course_results();
		?>
		<div class="lp_profile_course_progress__item">
			<div><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php echo $course->get_image( 'course_thumbnail' ); ?></a></div>
			<div><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
			<div><?php echo absint( $course_results['result'] ); ?>%</div>
			<div><?php echo ! empty( $course_data->get_expiration_time() ) ? $course_data->get_expiration_time() : '-'; ?></div>
			<div><?php echo ! empty( $course_data->get_end_time() ) ? $course_data->get_end_time() : '-'; ?></div>
		</div>
		<?php
	}

	wp_reset_postdata();
	?>

<?php if ( $current_page === 1 ) : ?>
</div>
<?php endif; ?>

<?php if ( $num_pages > 1 && $current_page < $num_pages && $current_page === 1 ) : ?>
	<div class="lp_profile_course_progress__nav">
		<button data-paged="<?php echo absint( $current_page + 1 ); ?>" data-number="<?php echo absint( $num_pages ); ?>"><?php esc_html_e( 'View more', 'learnpress' ); ?></button>
	</div>
<?php endif; ?>
