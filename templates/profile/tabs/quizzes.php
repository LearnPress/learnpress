<?php
/**
 * Template for displaying quizzes tab in user profile page.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.2
 */

defined( 'ABSPATH' ) || exit();

if ( ! LP_Profile::instance()->current_user_can( 'view-tab-quizzes' ) ) {
	return;
}

global $wp;

$profile      = learn_press_get_profile();
$user_profile = learn_press_get_user( $profile->get_user_data( 'id' ) );

$filter             = new LP_User_Items_Filter();
$filter->user_id    = $user_profile->get_id();
$filter->limit      = apply_filters( 'learnpress/user/quizzes/limit', 5 );
$filter->status     = LP_Request::get_param( 'filter-status' );
$filter->graduation = LP_Request::get_param( 'filter-graduation' );
$query              = $user_profile->get_user_quizzes( $filter );
$current_filter     = '';

if ( ! empty( $filter->status ) ) {
	$current_filter = $filter->status;
} elseif ( ! empty( $filter->graduation ) ) {
	$current_filter = $filter->graduation;
}

$filters = $profile->get_quizzes_filters( $current_filter );
?>

<div class="learn-press-subtab-content">
	<?php if ( $filters ) : ?>
		<ul class="learn-press-filters">
			<?php foreach ( $filters as $class => $link ) : ?>
				<li class="<?php echo esc_attr( $class ); ?>">
					<?php echo wp_kses_post( $link ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $query->get_items() ) : ?>
		<table class="lp-list-table profile-list-quizzes profile-list-table">
			<thead>
				<tr>
					<th class="column-quiz"><?php esc_html_e( 'Quiz', 'learnpress' ); ?></th>
					<th class="column-status"><?php esc_html_e( 'Result', 'learnpress' ); ?></th>
					<th class="column-time-interval"><?php esc_html_e( 'Time spent', 'learnpress' ); ?></th>
					<th class="column-date"><?php esc_html_e( 'Date', 'learnpress' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php
				/**
				 * @var LP_User_Item_Quiz $user_quiz
				 */
				foreach ( $query->get_items() as $user_quiz ) :
					$result_quiz = $user_quiz->get_result();
					$quiz        = learn_press_get_quiz( $user_quiz->get_id() );
					$courses     = learn_press_get_item_courses( array( $user_quiz->get_id() ) );
					?>

					<tr>
						<td class="column-quiz column-quiz-<?php echo esc_attr( $user_quiz->get_id() ); ?>">
							<?php
							if ( $courses ) {
								foreach ( $courses as $course ) {
									$course = LP_Course::get_course( $course->ID );
									?>
									<a href="<?php echo esc_url_raw( $course->get_item_link( $user_quiz->get_id() ) ); ?>">
										<?php echo esc_html( $quiz->get_title( 'display' ) ); ?>
									</a>
									<?php
								}
							}
							?>
						</td>

						<td class="column-status">
							<span class="result-percent"><?php echo wp_kses_post( $user_quiz->get_percent_result() ); ?></span>
							<span class="lp-label label-<?php echo esc_attr( $user_quiz->get_status() ); ?>">
							<?php echo wp_kses_post( wp_sprintf( '%s', esc_attr( $user_quiz->get_status_label() ) ) ); ?>
						</span>
						</td>
						<td class="column-time-interval">
							<?php echo wp_kses_post( $user_quiz->get_time_interval( 'display' ) ); ?>
						</td>
						<td class="column-date">
						<?php echo wp_kses_post( $user_quiz->get_start_time( 'i18n' ) ); ?>
						</td>
					</tr>

				<?php endforeach; ?>
			</tbody>

			<tfoot>
				<tr class="list-table-nav">
					<td colspan="2" class="nav-text">
						<?php echo wp_kses_post( $query->get_offset_text() ); ?>
					</td>
					<td colspan="2" class="nav-pages">
						<?php $query->get_nav_numbers(); ?>
					</td>
				</tr>
			</tfoot>
		</table>

	<?php else : ?>
		<?php learn_press_display_message( esc_html__( 'No quizzes!', 'learnpress' ) ); ?>
	<?php endif; ?>
</div>
