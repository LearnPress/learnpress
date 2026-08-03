<?php
/**
 * Class HealthCheckProvider
 *
 * @package LearnPress/Classes/Statistics
 * @since 4.4.2
 */

namespace LearnPress\Statistics;

use LP_Database;

defined( 'ABSPATH' ) || exit();

/**
 * Content health checks for the statistics dashboard.
 *
 * Deliberately NOT time-filtered — a course with zero enrollments is a
 * problem regardless of the selected period.
 *
 * @since 4.4.2
 */
class HealthCheckProvider extends LP_Database {
	/**
	 * @var HealthCheckProvider
	 */
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * @return HealthCheckProvider
	 */
	public static function getInstance(): HealthCheckProvider {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @param StatisticsScope|null $scope
	 * @return array [ 'no_enrollment' => int, 'no_content' => int, 'pending_review' => int, 'quiz_low_pass' => int ]
	 */
	public function get_checks( ?StatisticsScope $scope = null ): array {
		return array(
			'no_enrollment'  => $this->count_courses_without_enrollment( $scope ),
			'no_content'     => $this->count_courses_without_content( $scope ),
			'pending_review' => $this->count_pending_courses( $scope ),
			'quiz_low_pass'  => $this->count_low_pass_quizzes( $scope ),
		);
	}

	/**
	 * @param StatisticsScope|null $scope
	 * @return string
	 */
	private function scope_condition( ?StatisticsScope $scope, string $course_id_field ): string {
		if ( ! $scope || $scope->is_empty() ) {
			return '';
		}

		return $scope->sql_conditions( $course_id_field );
	}

	/**
	 * Published courses no user ever had a course row for.
	 *
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	private function count_courses_without_enrollment( ?StatisticsScope $scope ): int {
		$where = $this->scope_condition( $scope, 'p.ID' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tb_posts} AS p
			WHERE p.post_type = %s AND p.post_status = %s {$where}
			AND NOT EXISTS (
				SELECT 1 FROM {$this->tb_lp_user_items} AS ui
				WHERE ui.item_id = p.ID AND ui.item_type = %s
			)",
			LP_COURSE_CPT,
			'publish',
			LP_COURSE_CPT
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Published courses with no section items (empty curriculum).
	 *
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	private function count_courses_without_content( ?StatisticsScope $scope ): int {
		$where = $this->scope_condition( $scope, 'p.ID' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tb_posts} AS p
			WHERE p.post_type = %s AND p.post_status = %s {$where}
			AND NOT EXISTS (
				SELECT 1 FROM {$this->tb_lp_sections} AS s
				INNER JOIN {$this->tb_lp_section_items} AS si ON si.section_id = s.section_id
				WHERE s.section_course_id = p.ID
			)",
			LP_COURSE_CPT,
			'publish'
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Courses waiting for review.
	 *
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	private function count_pending_courses( ?StatisticsScope $scope ): int {
		$where = $this->scope_condition( $scope, 'p.ID' );

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tb_posts} AS p
			WHERE p.post_type = %s AND p.post_status = %s {$where}",
			LP_COURSE_CPT,
			'pending'
		);

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Quizzes with a pass rate below the alert threshold, minimum attempts
	 * required so one failed try does not flag a quiz.
	 *
	 * Scope reaches the course through the section lineage
	 * ( quiz → section_items → sections → course ).
	 *
	 * @param StatisticsScope|null $scope
	 * @return int
	 */
	private function count_low_pass_quizzes( ?StatisticsScope $scope ): int {
		$threshold    = (float) apply_filters( 'learn-press/statistics/quiz-pass-alert', 50 );
		$min_attempts = (int) apply_filters( 'learn-press/statistics/quiz-pass-alert-min-attempts', 5 );

		$where = '';
		if ( $scope && ! $scope->is_empty() ) {
			$course_conditions = $scope->sql_conditions( 's.section_course_id' );
			$where             = " AND EXISTS (
				SELECT 1 FROM {$this->tb_lp_section_items} AS si
				INNER JOIN {$this->tb_lp_sections} AS s ON s.section_id = si.section_id
				WHERE si.item_id = ui.item_id {$course_conditions}
			)";
		}

		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM (
				SELECT ui.item_id
				FROM {$this->tb_lp_user_items} AS ui
				WHERE ui.item_type = %s AND ui.graduation IN ( %s, %s ) {$where}
				GROUP BY ui.item_id
				HAVING COUNT(*) >= %d AND ( SUM( ui.graduation = %s ) / COUNT(*) ) * 100 < %f
			) AS low_quizzes",
			LP_QUIZ_CPT,
			'passed',
			'failed',
			$min_attempts,
			'passed',
			$threshold
		);

		return (int) $this->wpdb->get_var( $sql );
	}
}
