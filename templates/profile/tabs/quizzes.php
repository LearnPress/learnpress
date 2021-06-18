<?php
/**
 * Template for displaying quizzes tab in user profile page.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! LP_Profile::instance()->current_user_can( 'view-tab-quizzes' ) ) {
	return;
}

$profile       = learn_press_get_profile();
$filter_status = LP_Request::get_string( 'filter-status' );
$query         = $profile->query_quizzes( array( 'status' => $filter_status ) );
$filters       = $profile->get_quizzes_filters( $filter_status );
?>

<div class="learn-press-subtab-content">
	<?php if ( $filters ) : ?>
		<ul class="learn-press-filters">
			<?php foreach ( $filters as $class => $link ) : ?>
				<li class="<?php echo esc_attr( $class ); ?>">
					<?php echo $link; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $query['items'] ) : ?>
		<table class="lp-list-table profile-list-quizzes profile-list-table">
			<thead>
				<tr>
					<th class="column-quiz"><?php esc_html_e( 'Quiz', 'learnpress' ); ?></th>
					<th class="column-status"><?php esc_html_e( 'Result', 'learnpress' ); ?></th>
					<th class="column-time-interval"><?php esc_html_e( 'Time spend', 'learnpress' ); ?></th>
					<th class="column-date"><?php esc_html_e( 'Date', 'learnpress' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ( $query['items'] as $user_quiz ) : ?>
					<?php
					$quiz    = learn_press_get_quiz( $user_quiz->get_id() );
					$courses = learn_press_get_item_courses( array( $user_quiz->get_id() ) );
					?>

					<tr>
						<td class="column-quiz column-quiz-<?php echo esc_attr( $user_quiz->get_id() ); ?>">
							<?php
							if ( $courses ) {
								foreach ( $courses as $course ) {
									$course = LP_Course::get_course( $course->ID );
									?>
									<a href="<?php echo esc_url( $course->get_item_link( $user_quiz->get_id() ) ); ?>">
										<?php echo esc_html( $quiz->get_title( 'display' ) ); ?>
									</a>
									<?php
								}
							}
							?>
						</td>

						<td class="column-status">
							<span class="result-percent"><?php echo $user_quiz->get_percent_result(); ?></span>
							<span class="lp-label label-<?php echo esc_attr( $user_quiz->get_results( 'status' ) ); ?>">
							<?php echo $user_quiz->get_status_label(); ?>
						</span>
						</td>
						<td class="column-time-interval">
							<?php echo $user_quiz->get_time_interval( 'display' ); ?>
						</td>
						<td class="column-date">
						<?php echo $user_quiz->get_start_time( 'i18n' ); ?>
						</td>
					</tr>

				<?php endforeach; ?>
			</tbody>

			<tfoot>
				<tr class="list-table-nav">
					<td colspan="2" class="nav-text">
						<?php echo $query->get_offset_text(); ?>
					</td>
					<td colspan="2" class="nav-pages">
						<?php $query->get_nav_numbers( true ); ?>
					</td>
				</tr>
			</tfoot>
		</table>

	<?php else : ?>
		<?php learn_press_display_message( esc_html__( 'No quizzes!', 'learnpress' ) ); ?>
	<?php endif; ?>
</div>
