<?php
/**
 * HTML View for displaying courses user enrolled in wp profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

if ( ! isset( $user_id ) ) {
	return;
}

$profile = LP_Profile::instance( $user_id );
$user    = $profile->get_user();
$query   = $profile->query_courses( 'purchased' );
?>
<div class="lp-admin-profile-courses">
    <h2><?php esc_attr_e( 'LP Courses', 'learnpress' ); ?></h2>
	<?php
	if ( ! $query || ( ! $course_ids = $query->get_items() ) ) {
		esc_html_e( 'No courses.', 'learnpress' );
	} else {
		?>
        <table class="wp-list-table widefat fixed striped courses">
            <thead>
            <tr>
                <th class="manage-column column-course"><?php esc_html_e( 'Course', 'learnpress' ); ?></th>
                <th class="manage-column column-start-date"><?php esc_html_e( 'Enrolled', 'learnpress' ); ?></th>
                <th class="manage-column column-end-date"><?php esc_html_e( 'Finished', 'learnpress' ); ?></th>
                <th class="manage-column column-results"><?php esc_html_e( 'Results', 'learnpress' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $course_ids as $course_id ) {
				$course         = learn_press_get_course( $course_id );
				$course_data    = $user->get_course_data( $course_id );
				$course_results = $course_data->get_results( '' );
				$status         = $course_results['status'];
				$grade          = $course_data->get_graduation_text();//$course_results['grade'];
				?>
                <tr>
                    <td class="manage-column column-course">
                        <a href="<?php echo esc_url( $course->get_permalink() ); ?>"><?php echo $course->get_title(); ?></a>
                    </td>
                    <td class="manage-column column-start-date">
						<?php echo $course_data->get_start_time(); ?>
                    </td>
                    <td class="manage-column column-end-date">
						<?php if ( $status === 'finished' ) {
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
						}
						?>
                        <span class="lp-label <?php echo join( ' ', $label_class ); ?>">
                        <?php
                        echo $icon;
                        echo ucfirst( $grade ? $grade : $status );
                        ?>
                    </span>
                    </td>
                    <td class="manage-column column-results">
						<?php
						learn_press_admin_view( 'user/course-progress', compact( 'user', 'course' ) );
						//learn_press_debug( $course_results );
						?>
                    </td>
                </tr>
			<?php } ?>
            </tbody>
        </table>

	<?php } ?>
</div>
