<?php
/**
 * HTML View for displaying courses user enrolled in wp profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || die;

if ( ! isset( $user_id ) ) {
	return;
}

$profile = LP_Profile::instance( $user_id );
$user    = $profile->get_user();
$query   = $profile->query_courses( 'purchased' );
?>

<div class="lp-admin-profile-courses">
	<h2><?php esc_html_e( 'LearnPress Courses', 'learnpress' ); ?></h2>

	<?php $course_ids = $query->get_items(); ?>

	<?php if ( ! $query || ( ! $course_ids ) ) : ?>
		<?php esc_html_e( 'No courses.', 'learnpress' ); ?>
	<?php else : ?>

		<table class="wp-list-table widefat fixed striped courses">
			<thead>
			<tr>
				<th class="manage-column column-course"><?php esc_html_e( 'Course', 'learnpress' ); ?></th>
				<th class="manage-column column-start-date"><?php esc_html_e( 'Start time', 'learnpress' ); ?></th>
				<th class="manage-column column-end-date"><?php esc_html_e( 'End time', 'learnpress' ); ?></th>
				<th class="manage-column column-finished-date"><?php esc_html_e( 'Finished', 'learnpress' ); ?></th>
				<th class="manage-column column-results"><?php esc_html_e( 'Results', 'learnpress' ); ?></th>
			</tr>
			</thead>

			<tbody>
				<?php foreach ( $course_ids as $course_id ) : ?>
					<?php
					$course_id      = absint( $course_id->get_id() );
					$course         = learn_press_get_course( $course_id );
					$course_data    = $user->get_course_data( $course_id );
					$course_results = $course_data->get_results( '' );
					$status         = $course_results['status'];
					$grade          = $course_data->get_graduation_text();
					?>

					<tr>
						<td class="manage-column column-course">
							<a href="<?php echo esc_url( $course->get_permalink() ); ?>"><?php echo esc_html( $course->get_title() ); ?></a>
						</td>

						<td class="manage-column column-start-date">
							<?php echo $course_data->get_start_time(); ?>
						</td>

						<td class="manage-column column-end-date">
							<?php echo $course_data->get_end_time(); ?>
						</td>

						<td class="manage-column column-finished-date">
							<?php
							if ( $status === 'finished' ) {
								echo $course_data->get_end_time();
							}

							$icon        = '';
							$label_class = array( $status );

							switch ( $status ) {
								case 'finished':
									if ( $grade === 'passed' ) {
										$icon          = '<i class="far fa-check-circle"></i>';
										$label_class[] = 'success';
									} elseif ( $grade === 'failed' ) {
										$icon          = '<i class="far fa-times-circle"></i>';
										$label_class[] = 'error';
									} else {
										$icon          = '<i class="far fa-check-circle"></i>';
										$label_class[] = 'warning';
									}
									break;
								case 'enrolled':
									$icon          = '<i class="far fa-check-circle"></i>';
									$label_class[] = 'warning';
									break;
								case 'in-progress':
									$icon          = '<i class="far fa-check-circle"></i>';
									$label_class[] = 'warning';
									break;
								case 'passed':
									$icon          = '<i class="far fa-check-circle"></i>';
									$label_class[] = 'success';
							}
							?>

							<span class="lp-label <?php echo implode( ' ', $label_class ); ?>">
								<?php
								echo $icon;
								echo ucfirst( $grade ? $grade : $status );
								?>
							</span>
						</td>

						<td class="manage-column column-results">
							<?php learn_press_admin_view( 'user/course-progress', compact( 'user', 'course' ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
