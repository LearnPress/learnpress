<?php
/**
 * Template for displaying own courses in courses tab of user profile page.
 * Edit by Nhamdv
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.13
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $course_ids ) || ! isset( $current_page ) || ! isset( $num_pages ) ) {
	return;
}

$userCourseTemplate   = UserCourseTemplate::instance();
$singleCourseTemplate = SingleCourseTemplate::instance();
?>

<?php if ( $current_page === 1 ) : ?>
<table class="lp_profile_course_progress lp-list-table">
	<thead>
		<tr class="lp_profile_course_progress__item lp_profile_course_progress__header">
			<th></th>
			<th><?php esc_html_e( 'Name', 'learnpress' ); ?></th>
			<th><?php esc_html_e( 'Result', 'learnpress' ); ?></th>
			<th><?php esc_html_e( 'Expiration time', 'learnpress' ); ?></th>
			<th><?php esc_html_e( 'End time', 'learnpress' ); ?></th>
		</tr>
	</thead>
	<?php endif; ?>
	<tbody>
		<?php
		foreach ( $course_ids as $id ) {
			$courseModel = CourseModel::find( $id, true );
			if ( ! $courseModel ) {
				continue;
			}

			$userCourseModel = UserCourseModel::find( $user->get_id(), $id, true );
			if ( ! $userCourseModel ) {
				continue;
			}

			$course_result = $userCourseModel->calculate_course_results();
			?>
			<tr class="lp_profile_course_progress__item">
				<td>
					<a href="<?php echo $courseModel->get_permalink(); ?>" title="<?php echo $courseModel->get_title(); ?>">
						<?php echo wp_kses_post( $singleCourseTemplate->html_image( $courseModel ) ); ?>
					</a>
				</td>
				<td>
					<a href="<?php echo $courseModel->get_permalink(); ?>"
						title="<?php echo $courseModel->get_title(); ?>">
						<?php echo wp_kses_post( $singleCourseTemplate->html_title( $courseModel ) ); ?>
					</a>
				</td>
				<td><?php echo esc_html( $course_result['result'] ); ?>%</td>
				<td>
					<?php echo $userCourseTemplate->html_expire_date_time( $userCourseModel ); ?>
				</td>
				<td><?php echo $userCourseTemplate->html_end_date_time( $userCourseModel ); ?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
	<?php if ( $current_page === 1 ) : ?>
</table>
<?php endif; ?>

<?php if ( $num_pages > 1 && $current_page < $num_pages && $current_page === 1 ) : ?>
	<div class="lp_profile_course_progress__nav">
		<button class="lp-button"
				data-paged="<?php echo absint( $current_page + 1 ); ?>"
				data-number="<?php echo absint( $num_pages ); ?>"><?php esc_html_e( 'View more', 'learnpress' ); ?>
		</button>
	</div>
<?php endif; ?>
