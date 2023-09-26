<?php
/**
 * Template for displaying own courses in courses tab of user profile page.
 * Edit by Nhamdv
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.11
 */

use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $course_ids ) || ! isset( $current_page ) || ! isset( $num_pages ) ) {
	return;
}

$userCourseTemplate = UserCourseTemplate::instance();
?>

<?php if ( $current_page === 1 ) : ?>
<table class="lp_profile_course_progress">
	<tr class="lp_profile_course_progress__item lp_profile_course_progress__header">
		<th></th>
		<th><?php esc_html_e( 'Name', 'learnpress' ); ?></th>
		<th><?php esc_html_e( 'Result', 'learnpress' ); ?></th>
		<th><?php esc_html_e( 'Expiration time', 'learnpress' ); ?></th>
		<th><?php esc_html_e( 'End time', 'learnpress' ); ?></th>
	</tr>
	<?php endif; ?>

	<?php
	global $post;

	foreach ( $course_ids as $id ) {
		$course = learn_press_get_course( $id );
		if ( ! $course ) {
			continue;
		}

		$post = get_post( $id );
		setup_postdata( $post );

		$course_data = $user->get_course_data( $id );
		if ( ! $course_data ) {
			continue;
		}
		$course_result = $course_data->get_result();
		?>
	<tr class="lp_profile_course_progress__item">
		<td>
			<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
				<?php echo wp_kses_post( $course->get_image( 'course_thumbnail' ) ); ?>
			</a>
		</td>
		<td><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></td>
		<td><?php echo esc_html( $course_result['result'] ); ?>%</td>
		<td>
			<?php echo $userCourseTemplate->html_expire_date_time( $course_data ); ?>
		</td>
		<td><?php echo $userCourseTemplate->html_end_date_time( $course_data ); ?></td>
	</tr>
		<?php
	}

	wp_reset_postdata();
	?>

	<?php if ( $current_page === 1 ) : ?>
</table>
<?php endif; ?>

<?php if ( $num_pages > 1 && $current_page < $num_pages && $current_page === 1 ) : ?>
<div class="lp_profile_course_progress__nav">
	<button data-paged="<?php echo absint( $current_page + 1 ); ?>"
		data-number="<?php echo absint( $num_pages ); ?>"><?php esc_html_e( 'View more', 'learnpress' ); ?></button>
</div>
<?php endif; ?>
