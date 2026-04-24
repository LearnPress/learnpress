<?php
/**
 * Template hooks Dashboard in Course Builder.
 *
 * @since 4.3.0
 * @version 2.0.1
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Dashboard;

use LearnPress\TemplateHooks\CourseBuilder\BuilderPopupTemplate;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderListCoursesTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Course_Filter;
use LP_Statistics_DB;
use Throwable;

class BuilderDashboardTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/dashboard/layout', [ $this, 'layout' ] );
	}

	/**
	 * Render dashboard layout.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function layout( array $data = [] ) {
		$html = '';

		try {
			$user = $data['userModel'] ?? false;
			if ( ! $user instanceof UserModel ) {
				$user_id = get_current_user_id();
				if ( ! $user_id ) {
					return;
				}

				$user = UserModel::find( $user_id );
				if ( ! $user ) {
					return;
				}
			}

			$html = $this->html_content( $user );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		echo Template::combine_components(
			[
				'wrapper'     => '<div class="lp-course-builder-dashboard">',
				'header'      => $this->html_header(),
				'content'     => $html,
				'wrapper_end' => '</div>',
			]
		);
	}

	private function html_header(): string {
		return Template::combine_components(
			[
				'wrapper'     => '<div class="cb-tab-header">',
				'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', __( 'Dashboard', 'learnpress' ) ),
				'wrapper_end' => '</div>',
			]
		);
	}

	/**
	 * Render dashboard content.
	 *
	 * @param UserModel $user
	 * @return string
	 */
	private function html_content( UserModel $user ): string {
		$is_admin = user_can( $user->get_id(), 'administrator' );

		if ( $is_admin ) {
			$statistic = $this->get_admin_statistic();
		} else {
			$statistic = $user->get_instructor_statistic();
		}

		$stats_html           = $this->html_statistics_cards( $statistic, $is_admin );
		$charts_html          = $this->html_charts_section( $is_admin, $user->get_id() );
		$top_instructors_html = $is_admin ? $this->html_top_instructors_section() : '';
		$charts_row_class     = $is_admin
			? 'lp-cb-dashboard__charts-row lp-cb-dashboard__charts-row--admin'
			: 'lp-cb-dashboard__charts-row lp-cb-dashboard__charts-row--instructor';
		$top_courses_html     = $this->html_top_courses_section( $is_admin ? 0 : $user->get_id() );
		$recent_courses_html  = $this->html_recent_courses_section( $user );
		$quick_actions_html   = $this->html_quick_actions();

		return Template::combine_components(
			[
				'stats'          => $stats_html,
				'charts_row'     => sprintf(
					'<div class="%s">%s%s</div>',
					esc_attr( $charts_row_class ),
					$charts_html,
					$top_instructors_html
				),
				'top_courses'    => $top_courses_html,
				'quick_actions'  => $quick_actions_html,
				'recent_courses' => $recent_courses_html,
			]
		);
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
			$tb_user_items              = $wpdb->prefix . 'learnpress_user_items';
			$total_students             = $wpdb->get_var(
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

	private function get_icon_svg( string $icon ): string {
		static $icons = [];

		if ( ! isset( $icons[ $icon ] ) ) {
			$icons[ $icon ] = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/' . ltrim( $icon, '/' ) );
		}

		return $icons[ $icon ];
	}

	/**
	 * Render statistics cards.
	 *
	 * @param array $statistic
	 * @param bool  $is_admin
	 * @return string
	 */
	private function html_statistics_cards( array $statistic, bool $is_admin ): string {
		$cards = [
			[
				'key'   => 'total_course',
				'label' => __( 'Total Courses', 'learnpress' ),
				'color' => '#ef4444',
				'icon'  => 'ico-cb-total-courses.svg',
			],
			[
				'key'   => 'published_course',
				'label' => __( 'Published Courses', 'learnpress' ),
				'color' => '#2E91FA',
				'icon'  => 'ico-cb-published-courses.svg',
			],
			[
				'key'   => 'pending_course',
				'label' => __( 'Pending Courses', 'learnpress' ),
				'color' => '#F8A100',
				'icon'  => 'ico-cb-pending-courses.svg',
			],
			[
				'key'   => 'total_student',
				'label' => __( 'Total Students', 'learnpress' ),
				'color' => '#28A746',
				'icon'  => 'ico-cb-total-students.svg',
			],
		];

		// Role-based summary card
		if ( $is_admin ) {
			$cards[] = [
				'key'   => 'total_instructor',
				'label' => __( 'Total Instructors', 'learnpress' ),
				'color' => '#06AED4',
				'icon'  => 'ico-cb-total-instructors.svg',
			];
		} else {
			$cards[] = [
				'key'   => 'student_in_progress',
				'label' => __( 'Total In-progress Students', 'learnpress' ),
				'color' => '#06AED4',
				'icon'  => 'ico-cb-students-progress.svg',
			];
		}

		$cards_html = '';
		foreach ( $cards as $card ) {
			$value       = $statistic[ $card['key'] ] ?? 0;
			$cards_html .= sprintf(
				'<div class="lp-cb-dashboard__stat-card" style="--card-color: %s; --card-bg: %s15">
					<div class="stat-card__icon">%s</div>
					<span class="stat-card__label">%s</span>
					<span class="stat-card__value">%s</span>
				</div>',
				esc_attr( $card['color'] ),
				esc_attr( $card['color'] ),
				$this->get_icon_svg( $card['icon'] ?? '' ),
				esc_html( $card['label'] ),
				esc_html( number_format_i18n( $value ) )
			);
		}

		return Template::combine_components(
			[
				'wrapper'     => '<div class="lp-cb-dashboard__stats">',
				'content'     => $cards_html,
				'wrapper_end' => '</div>',
			]
		);
	}

	/**
	 * Render charts section with Net Sales and Students charts.
	 *
	 * @param bool $is_admin
	 * @param int  $user_id
	 * @return string
	 */
	private function html_charts_section( bool $is_admin, int $user_id ): string {
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
				[
					'filter_type' => 'year',
					'time'        => $current_date,
				],
				$sales_data
			);
			$students_chart = $stats_ctrl->process_chart_data(
				[
					'filter_type' => 'previous_days',
					'time'        => 6,
				],
				$students_data
			);
		} catch ( Throwable $e ) {
			$sales_chart    = [
				'labels' => [],
				'data'   => [],
			];
			$students_chart = [
				'labels' => [],
				'data'   => [],
			];
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
			wp_json_encode(
				[
					'sales'    => [
						'labels' => $sales_chart['labels'] ?? [],
						'data'   => $sales_chart['data'] ?? [],
					],
					'students' => [
						'labels' => $students_chart['labels'] ?? [],
						'data'   => $students_chart['data'] ?? [],
					],
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => $nonce,
				]
			)
		);

		return $html;
	}

	/**
	 * Render top instructors section (admin only).
	 *
	 * @return string
	 */
	private function html_top_instructors_section(): string {
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
				$avatar      = get_avatar( $instructor->instructor_id, 40 );
				$items_html .= sprintf(
					'<div class="instructor-item">
						<div class="instructor-item__avatar">%s</div>
						<div class="instructor-item__info">
							<span class="instructor-item__name">%s</span>
							<span class="instructor-item__meta">%s &middot; %s</span>
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
	private function html_top_courses_section( int $user_id ): string {
		$top_enrolled_html = $this->html_top_enrolled_courses( $user_id );
		$top_selling_html  = $this->html_top_selling_courses( $user_id );

		return Template::combine_components(
			[
				'wrapper'      => '<div class="lp-cb-dashboard__top-courses-wrapper">',
				'top_enrolled' => $top_enrolled_html,
				'top_selling'  => $top_selling_html,
				'wrapper_end'  => '</div>',
			]
		);
	}

	/**
	 * Render top enrolled courses with rich card layout.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function html_top_enrolled_courses( int $user_id ): string {
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
				$thumbnail       = get_the_post_thumbnail( $course->course_id, 'thumbnail' );
				if ( empty( $thumbnail ) ) {
					$thumbnail = sprintf(
						'<div class="course-item__thumb-placeholder">%s</div>',
						$this->get_icon_svg( 'ico-cb-dashboard-course-placeholder.svg' )
					);
				}

				$categories    = wp_get_post_terms( $course->course_id, 'course_category', array( 'fields' => 'names' ) );
				$category_text = '';
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$category_text = sprintf( ' %s <span class="category">%s</span>', esc_html__( 'in', 'learnpress' ), esc_html( implode( ', ', $categories ) ) );
				}

				$items_html .= sprintf(
					'<div class="course-item">
						<div class="course-item__thumb">%s</div>
						<div class="course-item__info">
							<a href="%s" class="course-item__title">%s</a>
							<span class="course-item__meta">%s <span class="author">%s</span>%s</span>
						</div>
						<div class="course-item__badge-wrapper">
							<span class="course-item__badge">
								%s
							</span>
						</div>
					</div>',
					$thumbnail,
					esc_url( get_permalink( $course->course_id ) ),
					esc_html( $course->course_name ),
					esc_html__( 'by', 'learnpress' ),
					esc_html( $course->instructor_name ?? '' ),
					$category_text,
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
					<span class="top-courses__total">%s <strong class="enrolled-students-total">%s</strong> %s</span>
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
	private function html_top_selling_courses( int $user_id ): string {
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
				$thumbnail      = get_the_post_thumbnail( $course->course_id, 'thumbnail' );
				if ( empty( $thumbnail ) ) {
					$thumbnail = sprintf(
						'<div class="course-item__thumb-placeholder">%s</div>',
						$this->get_icon_svg( 'ico-cb-dashboard-course-placeholder.svg' )
					);
				}

				$currency_symbol = function_exists( 'learn_press_get_currency_symbol' ) ? learn_press_get_currency_symbol() : '$';

				$lp_course  = function_exists( 'learn_press_get_course' ) ? learn_press_get_course( $course->course_id ) : false;
				$price_html = '';
				if ( $lp_course ) {
					$price = $lp_course->get_price();
					if ( $price > 0 ) {
						$price_html = learn_press_format_price( $price, true );
					} else {
						$price_html = esc_html__( 'Free', 'learnpress' );
					}
				}

				$items_html .= sprintf(
					'<div class="course-item">
						<div class="course-item__thumb">%s</div>
						<div class="course-item__info">
							<a href="%s" class="course-item__title">%s</a>
							<span class="course-item__meta">%s <span class="author">%s</span> &bull; %s</span>
							<span class="course-item__price">%s</span>
						</div>
						<div class="course-item__stats">
							<div class="course-item__revenue">%s: <strong class="revenue-amount">%s%s</strong></div>
							<div class="course-item__sold">%s %s</div>
						</div>
					</div>',
					$thumbnail,
					esc_url( get_permalink( $course->course_id ) ),
					esc_html( $course->course_name ),
					esc_html__( 'Instructor:', 'learnpress' ),
					esc_html( $course->instructor_name ?? '' ),
					sprintf(
						/* translators: %s: number of students */
						esc_html( _n( '%s student', '%s students', $course->course_count, 'learnpress' ) ),
						number_format_i18n( $course->course_count )
					),
					$price_html,
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
					<span class="top-courses__total">%s <strong class="revenue-total">%s%s</strong> %s</span>
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
	private function html_quick_actions(): string {
		$create_lesson_template_id = 'lp-tmpl-builder-popup-lesson-dashboard-new';
		$create_lesson_template    = sprintf(
			'<script type="text/template" id="%1$s"><div class="lp-builder-popup-overlay"></div><div class="lp-builder-popup lp-builder-popup--loading">%2$s</div></script>',
			esc_attr( $create_lesson_template_id ),
			TemplateAJAX::load_content_via_ajax(
				[
					'id_url'                  => 'builder-popup-lesson-dashboard-new',
					'lesson_id'               => 0,
					'html_no_load_ajax_first' => sprintf(
						'<div class="lp-builder-popup__loader"><div class="lp-loading-circle"></div><span>%s</span></div>',
						esc_html__( 'Loading...', 'learnpress' )
					),
				],
				[
					'class'  => BuilderPopupTemplate::class,
					'method' => 'render_lesson_popup',
				]
			)
		);

		$actions = [
			[
				'label' => __( 'Create Course', 'learnpress' ),
				'url'   => CourseBuilder::get_link_add_new( 'courses' ),
				'color' => '#ef4444',
				'svg'   => wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-courses-2.svg' ),
			],
			[
				'label'         => __( 'Create Lesson', 'learnpress' ),
				'attr'          => 'data-add-new-lesson',
				'popup_type'    => 'lesson',
				'popup_id'      => 0,
				'template_id'   => $create_lesson_template_id,
				'template_html' => $create_lesson_template,
				'color'         => '#7067ED',
				'icon'          => 'lp-icon-file-text-o',
			],
			[
				'label' => __( 'Create Quiz', 'learnpress' ),
				'url'   => CourseBuilder::get_link_add_new( 'quizzes' ),
				'color' => '#f59e0b',
				'icon'  => 'lp-icon-puzzle-piece',
			],
			[
				'label' => __( 'Create Question', 'learnpress' ),
				'url'   => CourseBuilder::get_link_add_new( 'questions', CourseBuilder::POST_NEW, 'overview' ),
				'color' => '#6b7280',
				'icon'  => 'lp-icon-question-circle-o',
			],
		];
		$actions = apply_filters( 'learn-press/course-builder/dashboard/quick-actions', $actions );

		$buttons_html = '';
		foreach ( $actions as $action ) {
			if ( ! empty( $action['attr'] ) || ! empty( $action['template_id'] ) ) {
				$button_attrs  = [];
				$template_html = $action['template_html'] ?? '';
				if ( ! empty( $action['attr'] ) ) {
					$button_attrs[] = $action['attr'];
				}
				if ( ! empty( $action['template_id'] ) ) {
					$button_attrs[] = sprintf( 'data-template="#%s"', esc_attr( $action['template_id'] ) );
				}
				if ( ! empty( $action['popup_type'] ) ) {
					$button_attrs[] = sprintf( 'data-popup-type="%s"', esc_attr( $action['popup_type'] ) );
				}
				if ( isset( $action['popup_id'] ) ) {
					$button_attrs[] = sprintf( 'data-popup-id="%d"', absint( $action['popup_id'] ) );
				}
				$button_attrs = ! empty( $button_attrs ) ? ' ' . implode( ' ', $button_attrs ) : '';

				// Render as button with data attribute (opens popup)
				$buttons_html .= sprintf(
					'<button type="button"%s class="quick-action__btn" style="--action-color: %s; --action-bg: %s10">
						<span class="quick-action__icon %s">%s</span>
						<span class="quick-action__label">%s</span>
					</button>%s',
					$button_attrs,
					esc_attr( $action['color'] ),
					esc_attr( $action['color'] ),
					esc_attr( $action['icon'] ?? '' ),
					$action['svg'] ?? '',
					esc_html( $action['label'] ),
					$template_html
				);
			} else {
				// Render as link
				$buttons_html .= sprintf(
					'<a href="%s" class="quick-action__btn" style="--action-color: %s; --action-bg: %s10">
						<span class="quick-action__icon %s">%s</span>
						<span class="quick-action__label">%s</span>
					</a>',
					esc_url( $action['url'] ),
					esc_attr( $action['color'] ),
					esc_attr( $action['color'] ),
					$action['icon'] ?? '',
					$action['svg'] ?? '',
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

	/**
	 * Render recent courses section using course items from Courses tab.
	 *
	 * @param UserModel $user
	 * @return string
	 */
	private function html_recent_courses_section( UserModel $user ): string {
		$content = '';

		try {
			$filter              = new LP_Course_Filter();
			$filter->limit       = 3;
			$filter->order_by    = 'post_date';
			$filter->order       = 'DESC';
			$filter->post_status = [ 'publish', 'pending', 'draft', 'private' ];

			if ( ! user_can( $user->get_id(), 'administrator' ) ) {
				$filter->post_author = $user->get_id();
			}

			$total_courses = 0;
			$courses       = \LearnPress\Models\Courses::get_courses( $filter, $total_courses );

			$list_html = '';
			if ( ! empty( $courses ) ) {
				$html_list_course = '';
				foreach ( $courses as $course_obj ) {
					$course = \LearnPress\Models\CourseModel::find( $course_obj->ID, true );
					if ( $course ) {
						// Reuse course item from Courses tab
						$html_list_course .= BuilderListCoursesTemplate::render_course( $course );
					}
				}

				if ( ! empty( $html_list_course ) ) {
					$list_html = Template::combine_components(
						[
							'wrapper'     => '<div class="courses-builder__course-tab learn-press-courses"><ul class="cb-list-course">',
							'list_course' => $html_list_course,
							'wrapper_end' => '</ul></div>',
						]
					);
				}
			}

			if ( empty( $list_html ) ) {
				$list_html = '<div class="no-data">' . esc_html__( 'No recent courses found', 'learnpress' ) . '</div>';
			}

			$content = sprintf(
				'<div class="lp-cb-dashboard__recent-courses" style="margin-top: 30px;">
					<div class="recent-courses__header">
						<h3 class="recent-courses__title">%s</h3>
					</div>
					<div class="recent-courses__list">%s</div>
				</div>',
				esc_html__( 'Recent Courses', 'learnpress' ),
				$list_html
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
