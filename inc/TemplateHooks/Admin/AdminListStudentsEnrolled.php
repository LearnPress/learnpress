<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LearnPress\Databases\PostDB;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;
use LP_Debug;
use LP_Helper;
use LP_Page_Controller;
use LP_Request;
use stdClass;
use Throwable;

/**
 * Template Admin List Students Enrolled.
 *
 * Displays enrolled students for Admin (all courses) and Instructor (own courses only).
 * Provides WP Admin submenu + Frontend profile tab.
 *
 * @since 4.3.3
 * @version 1.0.1
 */
class AdminListStudentsEnrolled {
	use Singleton;

	const PER_PAGE = 10;

	public function init(): void {
		// 2. Whitelist AJAX callback.
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
		// 4. Render a modal toolbar template from PHP (used by JS modal).
		add_action( 'admin_footer', array( $this, 'print_modal_toolbar_template' ) );
		add_action( 'wp_footer', array( $this, 'print_modal_toolbar_template' ) );
	}

	/**
	 * Admin page output callback.
	 */
	public function admin_page_output() {
		ob_start();
		$this->enrolled_students_layout();
		$content = ob_get_clean();

		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'content' => $content,
				'title'   => __( 'Students', 'learnpress' ),
				'id'      => 'lp-enrolled-students',
			)
		);
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_enrolled_students';

		return $callbacks;
	}

	/**
	 * Render initial layout with TemplateAJAX::load_content_via_ajax().
	 */
	public function enrolled_students_layout() {
		try {
			$page_current = '';
			if ( function_exists( 'get_current_screen' ) ) {
				$wp_screen = get_current_screen();
				if ( $wp_screen ) {
					// Page on the Admin screen.
					if ( $wp_screen->id === 'learnpress_page_learn-press-students-enrolled' ) {
						$page_current = 'learnpress_page_learn-press-students-enrolled';
					}
				}
			}

			$args = array(
				'id_url'                => 'lp-enrolled-students',
				'course_id'             => LP_Request::get_param( 'course_id', 0 ),
				'course_name'           => LP_Request::get_param( 'course_name' ),
				'paged'                 => 1,
				'search'                => lp_request::get_param( 'search' ),
				'start_date'            => LP_Request::get_param( 'start_date' ),
				'end_date'              => LP_Request::get_param( 'end_date' ),
				'enableUpdateParamsUrl' => false,
			);

			$call_back = array(
				'class'  => self::class,
				'method' => 'render_enrolled_students',
			);

			$section = [
				'wrap'     => sprintf(
					'<div class="lp-students-enrolled-layout %s">',
					$page_current
				),
				'toolbar'  => $this->html_toolbar(),
				'table'    => TemplateAJAX::load_content_via_ajax( $args, $call_back ),
				'wrap-end' => '</div>',
			];

			echo Template::combine_components( $section );
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Static render method called by AJAX — returns stdClass{ content }.
	 *
	 * Permission check: Admin=full, Instructor=own courses only.
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 */
	public static function render_enrolled_students( array $data ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( esc_html__( 'You do not have permission to view enrolled students.', 'learnpress' ) );
			}

			$course_id   = abs( LP_Helper::sanitize_params_submitted( $data['course_id'] ?? 0, 'int' ) );
			$paged       = max( 1, abs( LP_Helper::sanitize_params_submitted( $data['paged'] ?? 1, 'int' ) ) );
			$course_name = LP_Helper::sanitize_params_submitted( $data['course_name'] ?? '' );
			$search      = LP_Helper::sanitize_params_submitted( $data['search'] ?? '' );
			$start_date  = lp_helper::sanitize_params_submitted( $data['start_date'] ?? '' );
			$end_date    = lp_helper::sanitize_params_submitted( $data['end_date'] ?? '' );
			$per_page    = self::PER_PAGE;

			// Normalize date range if request is reversed.
			if ( $start_date && $end_date && strtotime( $start_date ) > strtotime( $end_date ) ) {
				$tmp        = $start_date;
				$start_date = $end_date;
				$end_date   = $tmp;
			}

			$lp_db_user_items    = UserItemsDB::getInstance();
			$filter              = new UserItemsFilter();
			$filter->item_type   = LP_COURSE_CPT;
			$filter->statues     = array( 'enrolled', 'finished' );
			$filter->limit       = $per_page;
			$filter->page        = $paged;
			$filter->order_by    = 'ui.start_time';
			$filter->order       = 'DESC';
			$filter->field_count = 'ui.user_item_id';
			$filter->only_fields = array(
				'ui.user_item_id',
				'ui.user_id',
				'ui.item_id',
				'ui.start_time',
				'ui.status',
				'ui.graduation',
				'u.display_name',
				'u.user_email',
				'p.post_title AS course_title',
			);
			$filter->join[]      = "JOIN {$lp_db_user_items->wpdb->posts} p ON ui.item_id = p.ID";
			$filter->join[]      = "JOIN {$lp_db_user_items->wpdb->users} u ON ui.user_id = u.ID";

			$instructor_id = get_current_user_id();
			if ( $instructor_id > 0 && current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				$filter->where[] = $lp_db_user_items->wpdb->prepare( 'AND p.post_author = %d', $instructor_id );
			}

			if ( $course_id > 0 ) {
				$filter->item_id = $course_id;
			} elseif ( ! empty( $course_name ) ) {
				$course_name_like = '%' . $lp_db_user_items->wpdb->esc_like( $course_name ) . '%';
				$filter->where[]  = $lp_db_user_items->wpdb->prepare(
					'AND p.post_title LIKE %s',
					$course_name_like
				);
			}

			if ( ! empty( $search ) ) {
				$search_like     = '%' . $lp_db_user_items->wpdb->esc_like( $search ) . '%';
				$filter->where[] = $lp_db_user_items->wpdb->prepare(
					'AND ( u.display_name LIKE %s OR u.user_email LIKE %s )',
					$search_like,
					$search_like
				);
			}

			if ( ! empty( $start_date ) ) {
				$filter->where[] = $lp_db_user_items->wpdb->prepare(
					'AND ui.start_time >= %s',
					$start_date . ' 00:00:00'
				);
			}

			if ( ! empty( $end_date ) ) {
				$filter->where[] = $lp_db_user_items->wpdb->prepare(
					'AND ui.start_time <= %s',
					$end_date . ' 23:59:59'
				);
			}

			$filter     = apply_filters(
				'learn-press/filter-enrolled-students',
				$filter
			);
			$total_rows = 0;
			$rows       = $lp_db_user_items->get_user_items( $filter, $total_rows );
			if ( ! is_array( $rows ) ) {
				$rows = array();
			}

			// Build HTML.
			$html = self::instance()->html_table(
				$rows,
				array(
					'total'              => $total_rows,
					'paged'              => $paged,
					'per_page'           => $per_page,
					'students-of-course' => $course_id,
				)
			);

			$content->content = $html;
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
			LP_Debug::error_log( $e );
		}

		return $content;
	}

	/**
	 * HTML builder: toolbar (course filter, search).
	 *
	 * @return string
	 */
	public function html_toolbar(): string {
		$courses = array();

		$data_get        = LP_Helper::sanitize_params_submitted( $_GET );
		$selected_course = abs( LP_Helper::sanitize_params_submitted( $data_get['course_id'] ?? 0, 'int' ) );
		$search_course   = LP_Helper::sanitize_params_submitted( $data_get['course_name'] ?? '' );
		$search_student  = LP_Helper::sanitize_params_submitted( $data_get['search'] ?? '' );
		$search_start    = lp_helper::sanitize_params_submitted( $data_get['start_date'] ?? '' );
		$search_end      = lp_helper::sanitize_params_submitted( $data_get['end_date'] ?? '' );

		$html_fields = sprintf(
			'<div class="filter-field">
				<label for="lp-enrolled-filter-course-name">%s</label>
				<input id="lp-enrolled-filter-course-name"
					class="lp-enrolled-filter-course-name"
					type="text"
					name="course_name"
					list="lp-enrolled-course-list"
					value="%s"
					placeholder="%s">
			</div>
			<div class="filter-field">
				<label for="lp-enrolled-search-input">%s</label>
				<input id="lp-enrolled-search-input"
					class="lp-enrolled-search-input"
					type="text"
					name="search" value="%s" placeholder="%s">
			</div>
			<div class="filter-field">
				<label for="lp-enrolled-filter-start-date">%s</label>
				<input id="lp-enrolled-filter-start-date"
					class="lp-enrolled-filter-start-date"
					type="date" name="start_date"
					value="%s"
					placeholder="mm/dd/yyyy">
			</div>
			<div class="filter-field">
				<label for="lp-enrolled-filter-end-date">%s</label>
				<input id="lp-enrolled-filter-end-date" class="lp-enrolled-filter-end-date" type="date" name="end_date" value="%s" placeholder="mm/dd/yyyy">
			</div>
			<datalist id="lp-enrolled-course-list">%s</datalist>',
			esc_html__( 'Course Filter', 'learnpress' ),
			esc_attr( $search_course ),
			esc_attr__( 'Search course...', 'learnpress' ),
			esc_html__( 'Student', 'learnpress' ),
			esc_attr( $search_student ),
			esc_attr__( 'Enter student name or email', 'learnpress' ),
			esc_html__( 'Start Date', 'learnpress' ),
			esc_attr( $search_start ),
			esc_html__( 'End date', 'learnpress' ),
			esc_attr( $search_end ),
			''
		);

		$html_btn_actions = sprintf(
			'<button type="button" class="lp-button lp-enrolled-btn-search">%s</button>
			<button type="button" class="lp-button lp-enrolled-btn-clear">%s</button>',
			esc_html__( 'Search', 'learnpress' ),
			esc_html__( 'Clear Filter', 'learnpress' )
		);

		$html_toolbar = AdminTemplate::html_form_filter(
			array(
				'classes'     => 'lp-enrolled-students-form',
				'fields'      => $html_fields,
				'btn_actions' => $html_btn_actions,
			)
		);

		return apply_filters(
			'learn-press/admin/enrolled-students/toolbar/section',
			$html_toolbar,
			$courses,
			$selected_course
		);
	}

	/**
	 * Render toolbar used inside View Students modal.
	 *
	 * @return string
	 */
	public function html_toolbar_modal(): string {
		$html_fields = sprintf(
			'<div class="filter-field">
				<label for="lp-modal-enrolled-search-input">%s</label>
				<input id="lp-modal-enrolled-search-input"
					class="lp-enrolled-search-input"
					type="text" name="search" placeholder="%s">
			</div>
			<div class="filter-field">
				<label for="lp-modal-enrolled-filter-start-date">%s</label>
				<input id="lp-modal-enrolled-filter-start-date"
					class="lp-enrolled-filter-start-date"
					type="date" name="start_date" placeholder="mm/dd/yyyy">
			</div>
			<div class="filter-field">
				<label for="lp-modal-enrolled-filter-end-date">%s</label>
				<input id="lp-modal-enrolled-filter-end-date"
					class="lp-enrolled-filter-end-date"
					type="date" name="end_date" placeholder="mm/dd/yyyy">
			</div>',
			esc_html__( 'Student', 'learnpress' ),
			esc_attr__( 'Enter student name or email', 'learnpress' ),
			esc_html__( 'Start Date', 'learnpress' ),
			esc_html__( 'End Date', 'learnpress' )
		);

		$html_btn_actions = sprintf(
			'<button type="button" class="lp-button lp-enrolled-btn-search-modal">%s</button>
			<button type="button" class="lp-button lp-enrolled-btn-clear-modal">%s</button>',
			esc_html__( 'Search', 'learnpress' ),
			esc_html__( 'Clear Filter', 'learnpress' )
		);

		return AdminTemplate::html_form_filter(
			array(
				'id'          => 'lp-modal-enrolled-form',
				'classes'     => 'lp-enrolled-students-form lp-enrolled-students-form--modal lp-enrolled-students-table-toolbar--modal',
				'fields'      => $html_fields,
				'btn_actions' => $html_btn_actions,
			)
		);
	}

	/**
	 * Render lp-target layout used in View Students modal.
	 *
	 * @return string
	 */
	public function html_modal_students_target(): string {
		$args = array(
			'id_url'                  => 'lp-modal-enrolled-students',
			'course_id'               => 0,
			'paged'                   => 1,
			'enableScrollToView'      => false,
			'enableUpdateParamsUrl'   => false,
			'html_no_load_ajax_first' => sprintf(
				'<div class="learn-press-message" style="%s">%s</div>',
				'width: 95%;',
				esc_html__( 'Loading', 'learnpress' )
			),
		);

		$call_back = array(
			'class'  => self::class,
			'method' => 'render_enrolled_students',
		);

		$section = array(
			'wrap'     => '<div id="lp-modal-enrolled-wrap">',
			'target'   => TemplateAJAX::load_content_via_ajax( $args, $call_back ),
			'wrap-end' => '</div>',
		);

		return Template::combine_components( $section );
	}

	/**
	 * Print HTML template for modal toolbar.
	 *
	 * @return void
	 */
	public function print_modal_toolbar_template() {

		$should_print = false;

		if ( is_admin() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && isset( $screen->id ) && $screen->id === 'edit-' . LP_COURSE_CPT ) {
				$should_print = true;
			}
		} elseif ( class_exists( 'LP_Page_Controller' ) && defined( 'LP_PAGE_PROFILE' ) ) {
			$should_print = LP_Page_Controller::page_current() === LP_PAGE_PROFILE;
		}

		if ( ! $should_print ) {
			return;
		}

		echo '<script type="text/html" id="lp-tmpl-enrolled-students-toolbar-modal">';
		echo $this->html_toolbar_modal();
		echo '</script>';

		echo '<script type="text/html" id="lp-tmpl-enrolled-students-target-modal">';
		echo $this->html_modal_students_target();
		echo '</script>';
	}

	/**
	 * HTML builder: table wrapper.
	 *
	 * @param array $rows DB result rows.
	 * @param array $meta [ total, paged, per_page ].
	 *
	 * @return string
	 */
	private function html_table( array $rows, array $meta ): string {
		if ( empty( $rows ) ) {
			$section_empty = array(
				'wrap'     => '<div class="lp-enrolled-students-table-wrap">',
				'empty'    => sprintf(
					'<div class="lp-enrolled-empty"><p>%s</p></div>',
					esc_html__( 'No students found.', 'learnpress' )
				),
				'wrap-end' => '</div>',
			);
			$section_empty = apply_filters(
				'learn-press/admin/enrolled-students/table/empty/section',
				$section_empty,
				$rows,
				$meta
			);

			return Template::combine_components( $section_empty );
		}

		$rows_html = array();

		$results_map = array();
		foreach ( $rows as $item ) {
			$userCourseModel = new UserCourseModel( $item );
			$rows_html[]     = $this->html_student_row( $userCourseModel, $meta );
		}
		$rows_html = apply_filters(
			'learn-press/admin/enrolled-students/table/rows-html',
			$rows_html,
			$rows,
			$results_map,
			$meta
		);

		$table_args           = array(
			'class_table' => 'lp-enrolled-students-table',
			'header'      => array(
				'student'  => array(
					'class' => 'lp-col-student',
					'title' => esc_html__( 'Student', 'learnpress' ),
				),
				'course'   => array(
					'class' => 'lp-col-course',
					'title' => esc_html__( 'Course', 'learnpress' ),
				),
				'date'     => array(
					'class' => 'lp-col-date',
					'title' => esc_html__( 'Enrolled Date', 'learnpress' ),
				),
				'progress' => array(
					'class' => 'lp-col-progress',
					'title' => esc_html__( 'Progress', 'learnpress' ),
				),
				'status'   => array(
					'class' => 'lp-col-status',
					'title' => esc_html__( 'Status', 'learnpress' ),
				),
			),
			'body'        => array(
				'rows_html' => Template::combine_components( $rows_html ),
			),
		);
		$section_footer       = array(
			'wrap'        => '<div class="lp-enrolled-students-table-footer">',
			'page_result' => sprintf(
				'<span class="lp-enrolled-students-table-footer__count">%s</span>',
				TableListTemplate::instance()->html_page_result(
					array(
						'paged'      => $meta['paged'],
						'per_page'   => $meta['per_page'],
						'total_rows' => $meta['total'],
						'item_name'  => _n( 'student', 'students', $meta['total'], 'learnpress' ),
					)
				)
			),
			'pagination'  => $this->html_pagination( $meta['total'], $meta['paged'], $meta['per_page'] ),
			'wrap-end'    => '</div>',
		);
		$section_footer       = apply_filters(
			'learn-press/admin/enrolled-students/table/footer/section',
			$section_footer,
			$rows,
			$results_map,
			$meta
		);
		$table_args['footer'] = Template::combine_components( $section_footer );
		$table_args           = apply_filters(
			'learn-press/admin/enrolled-students/table/args',
			$table_args,
			$rows,
			$results_map,
			$meta
		);

		if ( ! empty( $meta['students-of-course'] ) ) {
			unset( $table_args['header']['course'] ); // Remove Course column if filtering by course, as it's redundant.
		}

		$section = array(
			'wrap'     => '<div class="lp-enrolled-students-table-wrap">',
			'table'    => TableListTemplate::instance()->html_table( $table_args ),
			'wrap-end' => '</div>',
		);
		$section = apply_filters(
			'learn-press/admin/enrolled-students/table/section',
			$section,
			$rows,
			$results_map,
			$meta
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML builder: single student row.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 */
	private function html_student_row( UserCourseModel $userCourseModel, array $data = [] ): string {
		// Avatar initials.
		$userModel = $userCourseModel->get_user_model();
		if ( ! $userModel instanceof UserModel ) {
			return '';
		}

		$courseModel = $userCourseModel->get_course_model();
		if ( ! $courseModel instanceof CourseModel ) {
			return '';
		}

		// Progress.
		$course_result = $userCourseModel->calculate_course_results();
		$progress      = (float) ( $course_result['result'] ?? 0 );

		// Status badge.
		$graduation   = $userCourseModel->get_graduation();
		$status_raw   = $graduation !== UserItemModel::GRADUATION_IN_PROGRESS
			? $userCourseModel->get_graduation()
			: $userCourseModel->get_status();
		$status_label = ucfirst( str_replace( array( '-', '_' ), ' ', $status_raw ) );
		$badge_class  = 'lp-badge--' . sanitize_html_class( $status_raw );

		// Date.
		$date = UserCourseTemplate::instance()->html_start_date_time( $userCourseModel, false );

		$user_display_name = $userModel->get_display_name();
		if ( empty( $user_display_name ) ) {
			$user_display_name = $userModel->get_username();
		}

		$section = array(
			'row'                 => '<tr>',
			'student-cell-open'   => '<td class="lp-cell-student">',
			'avatar'              => SingleInstructorTemplate::instance()->html_avatar( $userModel ),
			'meta-open'           => '<div class="lp-meta">',
			'name'                => sprintf(
				'<span class="lp-name">%s</span>',
				esc_html( $user_display_name )
			),
			'email'               => sprintf(
				'<span class="lp-email">%s</span>',
				esc_html( $userModel->get_email() )
			),
			'meta-close'          => '</div>',
			'student-cell-close'  => '</td>',
			'course-cell'         => sprintf(
				'<td class="lp-cell-course"><a href="%s">%s</a></td>',
				esc_url_raw( $courseModel->get_permalink() ),
				$courseModel->get_title()
			),
			'date-cell'           => sprintf(
				'<td class="lp-cell-date">%s</td>',
				wp_kses_post( $date )
			),
			'progress-cell-open'  => '<td class="lp-cell-progress">',
			'progress-bar'        => sprintf(
				'<div class="lp-progress-bar"><span class="" style="width: %d%%;"></span></div>',
				$progress
			),
			'progress-text'       => sprintf(
				'<span class="lp-progress-text">%d%%</span>',
				$progress
			),
			'progress-cell-close' => '</td>',
			'status-cell-open'    => '<td class="lp-cell-status">',
			'status-badge'        => sprintf(
				'<span class="lp-badge %s">%s</span>',
				esc_attr( $badge_class ),
				esc_html( $status_label )
			),
			'status-cell-close'   => '</td>',
			'row-end'             => '</tr>',
		);

		if ( ! empty( $data['students-of-course'] ) ) {
			unset( $section['course-cell'] ); // Remove Course cell if filtering by course, as it's redundant.
		}

		$section = apply_filters(
			'learn-press/admin/enrolled-students/row/section',
			$section,
			$userCourseModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML builder: pagination.
	 *
	 * Uses .page-numbers class for loadAJAX.js compatibility.
	 *
	 * @param int $total
	 * @param int $paged
	 * @param int $per_page
	 *
	 * @return string
	 */
	private function html_pagination( int $total, int $paged, int $per_page ): string {
		$total_pages = max( 1, ceil( $total / $per_page ) );

		$pagination_items = Template::instance()->html_pagination(
			array(
				'total_pages' => $total_pages,
				'paged'       => $paged,
				'wrapper'     => array(
					'<nav class="learn-press-pagination navigation pagination lp-pagination">' => '</nav>',
				),
			)
		);
		$pagination_items = apply_filters(
			'learn-press/admin/enrolled-students/pagination/items',
			$pagination_items,
			$total,
			$paged,
			$per_page,
			$total_pages
		);

		$section = array(
			'wrap'       => '<div class="lp-enrolled-students-table-footer__pagination">',
			'pagination' => $pagination_items,
			'wrap-end'   => '</div>',
		);
		$section = apply_filters(
			'learn-press/admin/enrolled-students/pagination/section',
			$section,
			$total,
			$paged,
			$per_page,
			$total_pages
		);

		return Template::combine_components( $section );
	}
}
