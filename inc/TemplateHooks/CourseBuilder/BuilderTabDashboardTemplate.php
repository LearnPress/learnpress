<?php
/**
 * Template hooks Tab Dashboard in Course Builder.
 *
 * @since 4.3.0
 * @version 2.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Statistics_DB;
use LP_User_Items_DB;
use LP_User_Items_Filter;
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
		$is_admin = user_can( $user->get_id(), 'administrator' );

		if ( $is_admin ) {
			$statistic = $this->get_admin_statistic();
		} else {
			$statistic = $user->get_instructor_statistic();
		}

		$stats_html          = $this->render_statistics_cards( $statistic, $is_admin );
		$charts_html         = $this->render_charts_section( $is_admin, $user->get_id() );
		$top_instructors_html = $is_admin ? $this->render_top_instructors_section() : '';
		$top_courses_html    = $this->render_top_courses_section( $is_admin ? 0 : $user->get_id() );
		$quick_actions_html  = $this->render_quick_actions();

		return Template::combine_components( [
			'stats'           => $stats_html,
			'charts_row'      => '<div class="lp-cb-dashboard__charts-row">' . $charts_html . $top_instructors_html . '</div>',
			'top_courses'     => $top_courses_html,
			'quick_actions'   => $quick_actions_html,
		] );
	}

	/**
	 * Get global statistics for admin.
	 *
	 * @return array
	 */
	private function get_admin_statistic(): array {
		$statistic = array(
			'total_course'     => 0,
			'published_course' => 0,
			'pending_course'   => 0,
			'total_student'    => 0,
			'total_instructor' => 0,
		);

		try {
			global $wpdb;

			// Course counts via wp_count_posts
			$course_counts = wp_count_posts( LP_COURSE_CPT );

			$statistic['total_course']     = intval( $course_counts->publish ?? 0 )
				+ intval( $course_counts->pending ?? 0 )
				+ intval( $course_counts->draft ?? 0 )
				+ intval( $course_counts->private ?? 0 );
			$statistic['published_course'] = intval( $course_counts->publish ?? 0 );
			$statistic['pending_course']   = intval( $course_counts->pending ?? 0 );

			// Total unique students enrolled in any course
			$tb_user_items = $wpdb->prefix . 'learnpress_user_items';
			$total_students = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_id) FROM {$tb_user_items} WHERE item_type = %s",
					LP_COURSE_CPT
				)
			);
			$statistic['total_student'] = intval( $total_students );

			// Total instructors
			$statistic['total_instructor'] = $this->count_total_instructors();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $statistic;
	}

	/**
	 * Count total instructors (admin + lp_teacher).
	 *
	 * @return int
	 */
	private function count_total_instructors(): int {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT u.ID)
				FROM {$wpdb->users} AS u
				INNER JOIN {$wpdb->usermeta} AS um ON um.user_id = u.ID
				WHERE um.meta_key = %s
				AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)",
				$wpdb->prefix . 'capabilities',
				'%administrator%',
				'%' . LP_TEACHER_ROLE . '%'
			)
		);

		return intval( $result );
	}

	/**
	 * Render statistics cards.
	 *
	 * @param array $statistic
	 * @param bool  $is_admin
	 * @return string
	 */
	private function render_statistics_cards( array $statistic, bool $is_admin ): string {
		$cards = [
			[
				'key'   => 'total_course',
				'label' => __( 'Total Courses', 'learnpress' ),
				'color' => '#ef4444',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="9" y1="9" x2="15" y2="9"/></svg>',
			],
			[
				'key'   => 'published_course',
				'label' => __( 'Published Courses', 'learnpress' ),
				'color' => '#7067ED',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
			],
			[
				'key'   => 'pending_course',
				'label' => __( 'Pending Courses', 'learnpress' ),
				'color' => '#22c55e',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 12l2 2 4-4"/></svg>',
			],
			[
				'key'   => 'total_student',
				'label' => __( 'Total Students', 'learnpress' ),
				'color' => '#3b82f6',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
			],
		];

		// Admin-only: Total Instructors card
		if ( $is_admin ) {
			$cards[] = [
				'key'   => 'total_instructor',
				'label' => __( 'Total Instructors', 'learnpress' ),
				'color' => '#06b6d4',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
			];
		}

		$cards_html = '';
		foreach ( $cards as $card ) {
			$value = $statistic[ $card['key'] ] ?? 0;
			$cards_html .= sprintf(
				'<div class="lp-cb-dashboard__stat-card" style="--card-color: %s; --card-bg: %s15">
					<div class="stat-card__icon">%s</div>
					<span class="stat-card__label">%s</span>
					<span class="stat-card__value">%s</span>
				</div>',
				esc_attr( $card['color'] ),
				esc_attr( $card['color'] ),
				$card['svg'],
				esc_html( $card['label'] ),
				esc_html( number_format_i18n( $value ) )
			);
		}

		return Template::combine_components( [
			'wrapper'     => '<div class="lp-cb-dashboard__stats">',
			'content'     => $cards_html,
			'wrapper_end' => '</div>',
		] );
	}

	/**
	 * Render charts section with Net Sales and Students charts.
	 *
	 * @param bool $is_admin
	 * @param int  $user_id
	 * @return string
	 */
	private function render_charts_section( bool $is_admin, int $user_id ): string {
		$instructor_id = $is_admin ? 0 : $user_id;
		$nonce         = wp_create_nonce( 'lp_cb_dashboard_nonce' );

		// Get initial data - use 'year' for sales and 'previous_days' for students so data appears immediately
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$current_date    = current_time( 'Y-m-d' );

			$sales_data    = $lp_statistic_db->get_net_sales_data_scoped( 'year', $current_date, $instructor_id );
			$students_data = $lp_statistic_db->get_enrollment_chart_data( 'previous_days', 6, $instructor_id );

			// Process chart data using the REST controller's logic
			require_once LP_PLUGIN_PATH . 'inc/rest-api/v1/admin/class-lp-admin-rest-statistics-controller.php';
			$stats_ctrl = new \LP_REST_Admin_Statistics_Controller();

			$sales_chart    = $stats_ctrl->process_chart_data(
				[ 'filter_type' => 'year', 'time' => $current_date ],
				$sales_data
			);
			$students_chart = $stats_ctrl->process_chart_data(
				[ 'filter_type' => 'previous_days', 'time' => 6 ],
				$students_data
			);
		} catch ( Throwable $e ) {
			$sales_chart    = [ 'labels' => [], 'data' => [] ];
			$students_chart = [ 'labels' => [], 'data' => [] ];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$filter_options = '
			<option value="this_month">' . esc_html__( 'This month', 'learnpress' ) . '</option>
			<option value="this_week">' . esc_html__( 'This week', 'learnpress' ) . '</option>
			<option value="this_year" selected>' . esc_html__( 'This year', 'learnpress' ) . '</option>';

		$html = sprintf(
			'<div class="lp-cb-dashboard__chart-card">
				<div class="chart-card__header">
					<h3 class="chart-card__title">%s</h3>
					<select class="chart-card__filter" data-chart="sales" data-nonce="%s">%s</select>
				</div>
				<div class="chart-card__body">
					<canvas id="lp-cb-chart-sales"></canvas>
				</div>
			</div>
			<div class="lp-cb-dashboard__chart-card">
				<div class="chart-card__header">
					<h3 class="chart-card__title">%s</h3>
					<select class="chart-card__filter" data-chart="students" data-nonce="%s">
						<option value="this_week" selected>%s</option>
						<option value="this_month">%s</option>
						<option value="this_year">%s</option>
					</select>
				</div>
				<div class="chart-card__body">
					<canvas id="lp-cb-chart-students"></canvas>
				</div>
			</div>
			<script id="lp-cb-dashboard-chart-data" type="application/json">%s</script>',
			esc_html__( 'Net sales', 'learnpress' ),
			esc_attr( $nonce ),
			$filter_options,
			esc_html__( 'Students', 'learnpress' ),
			esc_attr( $nonce ),
			esc_html__( 'This week', 'learnpress' ),
			esc_html__( 'This month', 'learnpress' ),
			esc_html__( 'This year', 'learnpress' ),
			wp_json_encode( [
				'sales'    => [ 'labels' => $sales_chart['labels'] ?? [], 'data' => $sales_chart['data'] ?? [] ],
				'students' => [ 'labels' => $students_chart['labels'] ?? [], 'data' => $students_chart['data'] ?? [] ],
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => $nonce,
			] )
		);

		return $html;
	}

	/**
	 * Render top instructors section (admin only).
	 *
	 * @return string
	 */
	private function render_top_instructors_section(): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$instructors     = $lp_statistic_db->get_top_instructors( 4 );
		} catch ( Throwable $e ) {
			$instructors = [];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$items_html = '';
		if ( empty( $instructors ) ) {
			$items_html = '<div class="no-data">' . esc_html__( 'No instructors found', 'learnpress' ) . '</div>';
		} else {
			foreach ( $instructors as $instructor ) {
				$avatar = get_avatar( $instructor->instructor_id, 40 );
				$items_html .= sprintf(
					'<div class="instructor-item">
						<div class="instructor-item__avatar">%s</div>
						<div class="instructor-item__info">
							<span class="instructor-item__name">%s</span>
							<span class="instructor-item__meta">%s &bull; %s</span>
						</div>
					</div>',
					$avatar,
					esc_html( $instructor->instructor_name ),
					sprintf(
						/* translators: %s: number of courses */
						esc_html( _n( '%s course', '%s courses', $instructor->course_count, 'learnpress' ) ),
						number_format_i18n( $instructor->course_count )
					),
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $instructor->student_count, 'learnpress' ) ),
						number_format_i18n( $instructor->student_count )
					)
				);
			}
		}

		return sprintf(
			'<div class="lp-cb-dashboard__top-instructors">
				<div class="top-instructors__header">
					<h3 class="top-instructors__title">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
						%s
					</h3>
				</div>
				<div class="top-instructors__list">%s</div>
			</div>',
			esc_html__( 'Top Instructors', 'learnpress' ),
			$items_html
		);
	}

	/**
	 * Render top courses section.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_courses_section( int $user_id ): string {
		$top_enrolled_html = $this->render_top_enrolled_courses( $user_id );
		$top_selling_html  = $this->render_top_selling_courses( $user_id );

		return Template::combine_components( [
			'wrapper'      => '<div class="lp-cb-dashboard__top-courses-wrapper">',
			'top_enrolled' => $top_enrolled_html,
			'top_selling'  => $top_selling_html,
			'wrapper_end'  => '</div>',
		] );
	}

	/**
	 * Render top enrolled courses with rich card layout.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_enrolled_courses( int $user_id ): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$top_courses     = $lp_statistic_db->get_top_enrolled_courses_by_instructor( $user_id, 3 );
		} catch ( Throwable $e ) {
			$top_courses = [];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$total_enrolled = 0;
		$items_html     = '';

		if ( empty( $top_courses ) ) {
			$items_html = '<div class="no-data">' . esc_html__( 'No enrollment data available', 'learnpress' ) . '</div>';
		} else {
			foreach ( $top_courses as $course ) {
				$total_enrolled += intval( $course->enrollment_count );
				$thumbnail = get_the_post_thumbnail( $course->course_id, 'thumbnail' );
				if ( empty( $thumbnail ) ) {
					$thumbnail = '<div class="course-item__thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>';
				}

				$items_html .= sprintf(
					'<div class="course-item">
						<div class="course-item__thumb">%s</div>
						<div class="course-item__info">
							<a href="%s" class="course-item__title">%s</a>
							<span class="course-item__meta">%s %s &bull; <span class="course-item__students">%s</span></span>
						</div>
					</div>',
					$thumbnail,
					esc_url( get_permalink( $course->course_id ) ),
					esc_html( $course->course_name ),
					esc_html__( 'by', 'learnpress' ),
					esc_html( $course->instructor_name ?? '' ),
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $course->enrollment_count, 'learnpress' ) ),
						number_format_i18n( $course->enrollment_count )
					)
				);
			}
		}

		return sprintf(
			'<div class="lp-cb-dashboard__top-courses lp-cb-dashboard__top-enrolled">
				<div class="top-courses__header">
					<h3 class="top-courses__title">%s</h3>
					<span class="top-courses__total">%s <strong>%s</strong> %s</span>
				</div>
				<div class="top-courses__list">%s</div>
			</div>',
			esc_html__( 'Top Enrolled Courses', 'learnpress' ),
			esc_html__( 'Total:', 'learnpress' ),
			esc_html( number_format_i18n( $total_enrolled ) ),
			esc_html__( 'enrolled students', 'learnpress' ),
			$items_html
		);
	}

	/**
	 * Render top selling courses with rich card layout.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function render_top_selling_courses( int $user_id ): string {
		try {
			$lp_statistic_db = LP_Statistics_DB::getInstance();
			$top_courses     = $lp_statistic_db->get_top_sold_courses_by_instructor( $user_id, 3 );
		} catch ( Throwable $e ) {
			$top_courses = [];
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$total_revenue = 0;
		$items_html    = '';

		if ( empty( $top_courses ) ) {
			$items_html = '<div class="no-data">' . esc_html__( 'No sales data available', 'learnpress' ) . '</div>';
		} else {
			foreach ( $top_courses as $course ) {
				$total_revenue += floatval( $course->total_revenue ?? 0 );
				$thumbnail = get_the_post_thumbnail( $course->course_id, 'thumbnail' );
				if ( empty( $thumbnail ) ) {
					$thumbnail = '<div class="course-item__thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>';
				}

				$currency_symbol = function_exists( 'learn_press_get_currency_symbol' ) ? learn_press_get_currency_symbol() : '$';

				$items_html .= sprintf(
					'<div class="course-item">
						<div class="course-item__thumb">%s</div>
						<div class="course-item__info">
							<a href="%s" class="course-item__title">%s</a>
							<span class="course-item__meta">%s %s &bull; %s</span>
						</div>
						<div class="course-item__stats">
							<span class="course-item__revenue">%s: <strong>%s%s</strong></span>
							<span class="course-item__sold">%s %s</span>
						</div>
					</div>',
					$thumbnail,
					esc_url( get_permalink( $course->course_id ) ),
					esc_html( $course->course_name ),
					esc_html__( 'by', 'learnpress' ),
					esc_html( $course->instructor_name ?? '' ),
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $course->course_count, 'learnpress' ) ),
						number_format_i18n( $course->course_count )
					),
					esc_html__( 'Revenue', 'learnpress' ),
					esc_html( $currency_symbol ),
					esc_html( number_format_i18n( $course->total_revenue ?? 0, 2 ) ),
					esc_html( number_format_i18n( $course->course_count ) ),
					esc_html__( 'sold', 'learnpress' )
				);
			}
		}

		$currency_symbol = function_exists( 'learn_press_get_currency_symbol' ) ? learn_press_get_currency_symbol() : '$';

		return sprintf(
			'<div class="lp-cb-dashboard__top-courses lp-cb-dashboard__top-selling">
				<div class="top-courses__header">
					<h3 class="top-courses__title">%s</h3>
					<span class="top-courses__total">%s <strong>%s%s</strong> %s</span>
				</div>
				<div class="top-courses__list">%s</div>
			</div>',
			esc_html__( 'Top Selling Courses', 'learnpress' ),
			esc_html__( 'Total:', 'learnpress' ),
			esc_html( $currency_symbol ),
			esc_html( number_format_i18n( $total_revenue, 2 ) ),
			esc_html__( 'revenue', 'learnpress' ),
			$items_html
		);
	}

	/**
	 * Render quick action buttons.
	 *
	 * @return string
	 */
	private function render_quick_actions(): string {
		$actions = [
			[
				'label' => __( 'Create Course', 'learnpress' ),
				'url'   => CourseBuilder::get_tab_link( 'courses', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#ef4444',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
			],
			[
				'label' => __( 'Create Lesson', 'learnpress' ),
				'attr'  => 'data-add-new-lesson',
				'color' => '#7067ED',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			],
			[
				'label' => __( 'Create Quiz', 'learnpress' ),
				'url'   => CourseBuilder::get_tab_link( 'quizzes', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#f59e0b',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
			],
			[
				'label' => __( 'Create Question', 'learnpress' ),
				'url'   => CourseBuilder::get_tab_link( 'questions', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#6b7280',
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
			],
		];

		$buttons_html = '';
		foreach ( $actions as $action ) {
			if ( ! empty( $action['attr'] ) ) {
				// Render as button with data attribute (opens popup)
				$buttons_html .= sprintf(
					'<button type="button" %s class="quick-action__btn" style="--action-color: %s; --action-bg: %s10">
						<span class="quick-action__icon">%s</span>
						<span class="quick-action__label">%s</span>
					</button>',
					esc_attr( $action['attr'] ),
					esc_attr( $action['color'] ),
					esc_attr( $action['color'] ),
					$action['svg'],
					esc_html( $action['label'] )
				);
			} else {
				// Render as link
				$buttons_html .= sprintf(
					'<a href="%s" class="quick-action__btn" style="--action-color: %s; --action-bg: %s10">
						<span class="quick-action__icon">%s</span>
						<span class="quick-action__label">%s</span>
					</a>',
					esc_url( $action['url'] ),
					esc_attr( $action['color'] ),
					esc_attr( $action['color'] ),
					$action['svg'],
					esc_html( $action['label'] )
				);
			}
		}

		return sprintf(
			'<div class="lp-cb-dashboard__quick-actions">
				<h3 class="quick-actions__title">%s</h3>
				<div class="quick-actions__grid">%s</div>
			</div>',
			esc_html__( 'Quick Action', 'learnpress' ),
			$buttons_html
		);
	}
}
