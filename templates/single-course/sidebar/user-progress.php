<?php
/**
 * Template for displaying progress of single course.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.3
 */

use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $course ) || ! isset( $course_data ) || ! isset( $course_results ) ) {
	return;
}

$passing_condition                = $course->get_passing_condition();
$progress_items_completed_percent = 0;
$userCourseModel                  = UserCourseModel::find( $user->get_id(), $course->get_id(), true );
if ( $userCourseModel && $user->get_id() > 0 ) {
	$courseModel     = $userCourseModel->get_course_model();
	$calculate       = $userCourseModel->calculate_course_results();
	$total_items     = $courseModel->count_items();
	$evaluation_type = $courseModel::get_evaluation_types( $courseModel->get_evaluation_type() );
	if ( array_key_first( $evaluation_type ) === 'evaluate_final_quiz' ) {
		$final_quiz = $courseModel->get_final_quiz();
		if ( $final_quiz ) {
			$quizModel         = QuizPostModel::find( $final_quiz, true );
			$passing_condition = $quizModel->get_passing_grade();
		}
	}

	$progress_items_completed_percent = round(
		$total_items > 0 ? $calculate['completed_items'] * 100 / $total_items : 0,
		2
	);
}
?>

<div class="course-results-progress">
	<?php
	if ( ! empty( $course_results['items'] ) && $course_results['items']['lesson']['total'] ) :
		?>
		<div class="items-progress">
			<h4 class="items-progress__heading">
				<?php esc_html_e( 'Lessons completed:', 'learnpress' ); ?>
			</h4>
			<span
				class="number"><?php echo esc_html( sprintf( '%1$d/%2$d', $course_results['items']['lesson']['completed'], $course_results['items']['lesson']['total'] ) ); ?></span>
		</div>
	<?php endif; ?>

	<?php
	if ( ! empty( $course_results['items'] ) && $course_results['items']['quiz']['total'] ) :
		$quiz_false = $course_results['items']['quiz']['completed'] - $course_results['items']['quiz']['passed'];
		?>
		<div class="items-progress">
			<h4 class="items-progress__heading">
				<?php esc_html_e( 'Quizzes finished:', 'learnpress' ); ?>
			</h4>
			<span class="number"
					title="<?php echo esc_attr( sprintf( __( 'Failed %1$d, Passed %2$d', 'learnpress' ), $quiz_false, $course_results['items']['quiz']['passed'] ) ); ?>"><?php printf( __( '%1$d/%2$d', 'learnpress' ), $course_results['items']['quiz']['completed'], $course_results['items']['quiz']['total'] ); ?></span>
		</div>
	<?php endif; ?>

	<?php do_action( 'learn-press/user-item-progress', $course_results, $course_data, $user, $course ); ?>

	<div class="course-progress" style="margin-top: 10px;">
		<h4 class="items-progress__heading">
			<?php esc_html_e( 'Course progress:', 'learnpress' ); ?>
		</h4>

		<div class="lp-course-status">
			<span class="number">
				<?php echo esc_html( $progress_items_completed_percent ); ?>
				<span class="percentage-sign">%</span>
			</span>
		</div>
	</div>
	<?php
	echo UserCourseTemplate::instance()->html_items_completed_progress_bar( $userCourseModel );
	?>

	<div class="course-progress" style="margin-top: 10px">
		<h4 class="items-progress__heading">
			<?php
			esc_html_e( 'Passing grade', 'learnpress' );
			printf(
				' <span class="lp-icon-question-circle" title="%s"></span>',
				sprintf(
					'%s. %s',
					isset( $evaluation_type['label'] ) ? esc_attr( $evaluation_type['label'] ) : '',
					esc_attr( sprintf( __( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ) )
				)
			)
			?>
		</h4>

		<div class="lp-course-status">
			<span class="number">
				<?php echo esc_html( $course_results['result'] ); ?>
				<span class="percentage-sign">%</span>
			</span>
		</div>
	</div>
</div>
