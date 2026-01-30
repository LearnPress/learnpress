<?php
/**
 * Template hooks Tab Dashboard in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LP_Statistics_DB;
use Throwable;

class BuilderTabDashboardTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/dashboard/layout', [ $this, 'html_tab_dashboard' ] );
	}

	/**
	 * Render dashboard tab HTML.
	 *
	 * @return void
	 */
	public function html_tab_dashboard() {
		$html = '';

		try {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			$user = UserModel::find( $user_id );
			if ( ! $user ) {
				return;
			}

			$html = $this->render_dashboard_content( $user );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		echo Template::combine_components( [
			'wrapper'     => '<div class="lp-course-builder-dashboard">',
			'content'     => $html,
			'wrapper_end' => '</div>',
		] );
	}

	/**
	 * Render dashboard content.
	 *
	 * @param UserModel $user
	 * @return string
	 */
	private function render_dashboard_content( UserModel $user ): string {
		$statistic = $user->get_instructor_statistic();

		$stats_html = $this->render_statistics_cards( $statistic );
		$top_courses_html = $this->render_top_courses_section( $user->get_id() );

		return Template::combine_components( [
			'stats'       => $stats_html,
			'top_courses' => $top_courses_html,
		] );
	}

	/**
	 * Render statistics cards.
	 *
	 * @param array $statistic
	 * @return string
	 */
	private function render_statistics_cards( array $statistic ): string {
		$cards = [
			[
				'key'   => 'total_course',
				'label' => __( 'Total Courses', 'learnpress' ),
				'icon'  => 'dashicons-welcome-learn-more',
				'color' => '#3b82f6',
			],
			[
				'key'   => 'published_course',
				'label' => __( 'Published Courses', 'learnpress' ),
				'icon'  => 'dashicons-yes-alt',
				'color' => '#10b981',
			],
			[
				'key'   => 'pending_course',
				'label' => __( 'Pending Courses', 'learnpress' ),
				'icon'  => 'dashicons-clock',
				'color' => '#f59e0b',
			],
			[
				'key'   => 'total_student',
				'label' => __( 'Total Students', 'learnpress' ),
				'icon'  => 'dashicons-groups',
				'color' => '#8b5cf6',
			],
			[
				'key'   => 'student_completed',
				'label' => __( 'Students Completed', 'learnpress' ),
				'icon'  => 'dashicons-awards',
				'color' => '#06b6d4',
			],
			[
				'key'   => 'student_in_progress',
				'label' => __( 'Students In-Progress', 'learnpress' ),
				'icon'  => 'dashicons-chart-line',
				'color' => '#ec4899',
			],
		];

		$cards_html = '';
		foreach ( $cards as $card ) {
			$value = $statistic[ $card['key'] ] ?? 0;
			$cards_html .= sprintf(
				'<div class="lp-cb-dashboard__stat-card" style="--card-color: %s">
					<div class="stat-card__icon">
						<span class="dashicons %s"></span>
					</div>
					<div class="stat-card__content">
						<span class="stat-card__value">%s</span>
						<span class="stat-card__label">%s</span>
					</div>
				</div>',
				esc_attr( $card['color'] ),
				esc_attr( $card['icon'] ),
				esc_html( number_format_i18n( $value ) ),
				esc_html( $card['label'] )
			);
		}

		return Template::combine_components( [
			'wrapper'     => '<div class="lp-cb-dashboard__stats">',
			'content'     => $cards_html,
			'wrapper_end' => '</div>',
		] );
	}

	/**
	 * Render top courses section.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_courses_section( int $user_id ): string {
		$top_selling_html = $this->render_top_selling_courses( $user_id );
		$top_enrolled_html = $this->render_top_enrolled_courses( $user_id );

		return Template::combine_components( [
			'wrapper'      => '<div class="lp-cb-dashboard__top-courses-wrapper">',
			'top_selling'  => $top_selling_html,
			'top_enrolled' => $top_enrolled_html,
			'wrapper_end'  => '</div>',
		] );
	}

	/**
	 * Render top selling courses.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_selling_courses( int $user_id ): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$top_courses = $lp_statistic_db->get_top_sold_courses_by_instructor( $user_id, 5 );

			if ( empty( $top_courses ) ) {
				$table_content = '<tr><td colspan="3" class="no-data">' . esc_html__( 'No sales data available', 'learnpress' ) . '</td></tr>';
			} else {
				$table_content = '';
				foreach ( $top_courses as $index => $course ) {
					$table_content .= sprintf(
						'<tr>
							<td class="rank">%d</td>
							<td class="course-name"><a href="%s">%s</a></td>
							<td class="count">%s</td>
						</tr>',
						$index + 1,
						esc_url( get_permalink( $course->course_id ) ),
						esc_html( $course->course_name ),
						esc_html( number_format_i18n( $course->course_count ) )
					);
				}
			}
		} catch ( Throwable $e ) {
			$table_content = '<tr><td colspan="3" class="no-data">' . esc_html__( 'Error loading data', 'learnpress' ) . '</td></tr>';
		}

		return sprintf(
			'<div class="lp-cb-dashboard__top-courses lp-cb-dashboard__top-selling">
				<h3 class="top-courses__title">
					<span class="dashicons dashicons-chart-bar"></span>
					%s
				</h3>
				<table class="lp-cb-dashboard__table">
					<thead>
						<tr>
							<th class="rank">#</th>
							<th class="course-name">%s</th>
							<th class="count">%s</th>
						</tr>
					</thead>
					<tbody>%s</tbody>
				</table>
			</div>',
			esc_html__( 'Top Selling Courses', 'learnpress' ),
			esc_html__( 'Course', 'learnpress' ),
			esc_html__( 'Sales', 'learnpress' ),
			$table_content
		);
	}

	/**
	 * Render top enrolled courses.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_enrolled_courses( int $user_id ): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$top_courses = $lp_statistic_db->get_top_enrolled_courses_by_instructor( $user_id, 5 );

			if ( empty( $top_courses ) ) {
				$table_content = '<tr><td colspan="3" class="no-data">' . esc_html__( 'No enrollment data available', 'learnpress' ) . '</td></tr>';
			} else {
				$table_content = '';
				foreach ( $top_courses as $index => $course ) {
					$table_content .= sprintf(
						'<tr>
							<td class="rank">%d</td>
							<td class="course-name"><a href="%s">%s</a></td>
							<td class="count">%s</td>
						</tr>',
						$index + 1,
						esc_url( get_permalink( $course->course_id ) ),
						esc_html( $course->course_name ),
						esc_html( number_format_i18n( $course->enrollment_count ) )
					);
				}
			}
		} catch ( Throwable $e ) {
			$table_content = '<tr><td colspan="3" class="no-data">' . esc_html__( 'Error loading data', 'learnpress' ) . '</td></tr>';
		}

		return sprintf(
			'<div class="lp-cb-dashboard__top-courses lp-cb-dashboard__top-enrolled">
				<h3 class="top-courses__title">
					<span class="dashicons dashicons-groups"></span>
					%s
				</h3>
				<table class="lp-cb-dashboard__table">
					<thead>
						<tr>
							<th class="rank">#</th>
							<th class="course-name">%s</th>
							<th class="count">%s</th>
						</tr>
					</thead>
					<tbody>%s</tbody>
				</table>
			</div>',
			esc_html__( 'Top Enrolled Courses', 'learnpress' ),
			esc_html__( 'Course', 'learnpress' ),
			esc_html__( 'Enrollments', 'learnpress' ),
			$table_content
		);
	}
}
