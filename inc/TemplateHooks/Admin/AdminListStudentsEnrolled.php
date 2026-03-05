<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Databases\PostDB;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Helper;
use stdClass;
use Throwable;

/**
 * Template Admin List Students Enrolled.
 *
 * Displays enrolled students for Admin (all courses) and Instructor (own courses only).
 * Provides WP Admin submenu + Frontend profile tab.
 *
 * @since 4.3.3
 * @version 1.0.0
 */
class AdminListStudentsEnrolled {
	use Singleton;

	const PER_PAGE = 10;

	public function init() {

		// 1. Register render hook for both admin + frontend profile.
		add_action( 'learn-press/admin/enrolled-students/layout', array( $this, 'enrolled_students_layout' ) );
		// 2. Whitelist AJAX callback.
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
		// 3. Register WP admin submenu page.
		add_action( 'admin_menu', array( $this, 'register_admin_submenu' ), 30 );
		// 4. Render modal toolbar template from PHP (used by JS modal).
		add_action( 'admin_footer', array( $this, 'print_modal_toolbar_template' ) );
		add_action( 'wp_footer', array( $this, 'print_modal_toolbar_template' ) );
	}
	/**
	 * Register submenu under LearnPress.
	 */
	public function register_admin_submenu() {
		add_submenu_page(
			'learn_press',
			__( 'Enrolled Students', 'learnpress' ),
			__( 'Enrolled Students', 'learnpress' ),
			'edit_posts', // Both admin + lp_teacher
			'lp-enrolled-students',
			array( $this, 'admin_page_output' )
		);
	}

	/**
	 * Admin page output callback.
	 */
	public function admin_page_output() {
		$current_user  = wp_get_current_user();
		$is_admin      = in_array( 'administrator', $current_user->roles, true );
		$instructor_id = $is_admin ? 0 : $current_user->ID;

		echo '<div class="wrap" id="lp-enrolled-students">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Enrolled Students', 'learnpress' ) . '</h1>';
		do_action( 'learn-press/admin/enrolled-students/layout', $instructor_id );
		echo '</div>';
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
	 *
	 * @param int $instructor_id 0 for Admin (all), user ID for Instructor.
	 */
	public function enrolled_students_layout( $instructor_id = 0 ) {
		// Enqueue styles — lp-enrolled-students-table CSS is loaded via admin.css/frontend.css import.
		// Build toolbar HTML (outside AJAX so it persists across reloads).
		echo $this->html_toolbar( (int) $instructor_id );
		$data_get = LP_Helper::sanitize_params_submitted( $_GET );

		$args = array(
			'instructor_id' => (int) $instructor_id,
			'course_id'     => abs( LP_Helper::sanitize_params_submitted( $data_get['course_id'] ?? 0, 'int' ) ),
			'course_name'   => LP_Helper::sanitize_params_submitted( $data_get['course_name'] ?? '' ),
			'paged'         => 1,
			'search'        => LP_Helper::sanitize_params_submitted( $data_get['search'] ?? '' ),
			'start_date'    => self::sanitize_date_filter( $data_get['start_date'] ?? '' ),
			'end_date'      => self::sanitize_date_filter( $data_get['end_date'] ?? '' ),
		);

		$call_back = array(
			'class'  => self::class,
			'method' => 'render_enrolled_students',
		);

		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
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
			// Permission check.
			$current_user  = wp_get_current_user();
			$is_admin      = in_array( 'administrator', $current_user->roles, true );
			$instructor_id = $is_admin ? ( intval( $data['instructor_id'] ?? 0 ) ) : $current_user->ID;

			$course_id   = abs( LP_Helper::sanitize_params_submitted( $data['course_id'] ?? 0, 'int' ) );
			$paged       = max( 1, abs( LP_Helper::sanitize_params_submitted( $data['paged'] ?? 1, 'int' ) ) );
			$course_name = LP_Helper::sanitize_params_submitted( $data['course_name'] ?? '' );
			$search      = LP_Helper::sanitize_params_submitted( $data['search'] ?? '' );
			$start_date  = self::sanitize_date_filter( $data['start_date'] ?? '' );
			$end_date    = self::sanitize_date_filter( $data['end_date'] ?? '' );
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

			if ( $instructor_id > 0 ) {
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

			$total_rows = 0;
			$rows       = $lp_db_user_items->get_user_items( $filter, $total_rows );
			if ( ! is_array( $rows ) ) {
				$rows = array();
			}

			// Build progress map via UserCourseModel::calculate_course_results().
			$results_map = array();
			foreach ( $rows as $item ) {
				$user_item_id = (int) ( $item->user_item_id ?? 0 );
				if ( $user_item_id < 1 ) {
					continue;
				}

				$results_map[ $user_item_id ] = 0;
				try {
					$user_course_model = new UserCourseModel( $item );
					$course_result     = $user_course_model->calculate_course_results( true );
					if ( is_array( $course_result ) ) {
						$results_map[ $user_item_id ] = (float) ( $course_result['result'] ?? 0 );
					}
				} catch ( Throwable $e ) {
					error_log( __METHOD__ . ' ' . $e->getMessage() );
				}
			}

			// Build HTML.
			$html = self::instance()->html_table(
				$rows,
				$results_map,
				array(
					'total'    => $total_rows,
					'paged'    => $paged,
					'per_page' => $per_page,
				)
			);

			$content->content = $html;
		} catch ( Throwable $e ) {
			$content->content = '<p class="lp-enrolled-error">' . esc_html( $e->getMessage() ) . '</p>';
			error_log( __METHOD__ . ' ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * HTML builder: toolbar (course filter, search).
	 *
	 * @param int $instructor_id
	 *
	 * @return string
	 */
	public function html_toolbar( int $instructor_id ): string {

		$post_db                 = PostDB::getInstance();
		$filter                  = new PostFilter();
		$filter->only_fields     = array( 'p.ID', 'p.post_title' );
		$filter->post_type       = LP_COURSE_CPT;
		$filter->post_status     = array( 'publish' );
		$filter->order_by        = 'p.post_title';
		$filter->order           = 'ASC';
		$filter->limit           = -1;
		$filter->run_query_count = false;

		if ( $instructor_id > 0 ) {
			$filter->post_author = $instructor_id;
		}

		$courses = $post_db->get_posts( $filter );
		if ( ! is_array( $courses ) ) {
			$courses = array();
		}

		$data_get        = LP_Helper::sanitize_params_submitted( $_GET );
		$selected_course = abs( LP_Helper::sanitize_params_submitted( $data_get['course_id'] ?? 0, 'int' ) );
		$search_course   = LP_Helper::sanitize_params_submitted( $data_get['course_name'] ?? '' );
		$search_student  = LP_Helper::sanitize_params_submitted( $data_get['search'] ?? '' );
		$search_start    = self::sanitize_date_filter( $data_get['start_date'] ?? '' );
		$search_end      = self::sanitize_date_filter( $data_get['end_date'] ?? '' );

		$options = array();

		foreach ( $courses as $course ) {
			$options[] = sprintf(
				'<option value="%1$s" data-course-id="%2$d"></option>',
				esc_attr( $course->post_title ),
				(int) $course->ID
			);
		}
		$options = apply_filters(
			'learn-press/admin/enrolled-students/toolbar/options',
			$options,
			$courses,
			$selected_course,
			$instructor_id
		);

		$section = array(
			'wrap'                => '<div class="lp-enrolled-students-table-toolbar">',
			'course-row-open'     => '<div class="lp-enrolled-students-table-toolbar__row lp-enrolled-students-table-toolbar__row--course">',
			'course-field-open'   => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--full">',
			'course-label'        => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-enrolled-filter-course-name">' . esc_html__( 'Course Filter', 'learnpress' ) . '</label>',
			'course-input'        => '<input id="lp-enrolled-filter-course-name" class="lp-enrolled-filter-course-name lp-enrolled-students-table-toolbar__input" type="text" list="lp-enrolled-course-list" value="' . esc_attr( $search_course ) . '" placeholder="' . esc_attr__( 'Search course...', 'learnpress' ) . '">',
			'course-list-open'    => '<datalist id="lp-enrolled-course-list">',
			'course-options'      => Template::combine_components( $options ),
			'course-list-close'   => '</datalist>',
			'course-field-close'  => '</div>',
			'course-row-close'    => '</div>',
			'filter-row-open'     => '<div class="lp-enrolled-students-table-toolbar__row lp-enrolled-students-table-toolbar__row--filters">',
			'student-field-open'  => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--student">',
			'student-label'       => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-enrolled-search-input">' . esc_html__( 'Student', 'learnpress' ) . '</label>',
			'student-input'       => '<input id="lp-enrolled-search-input" class="lp-enrolled-search-input lp-enrolled-students-table-toolbar__input" type="text" value="' . esc_attr( $search_student ) . '" placeholder="' . esc_attr__( 'Enter student name or email', 'learnpress' ) . '">',
			'student-field-close' => '</div>',
			'start-field-open'    => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--date">',
			'start-label'         => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-enrolled-filter-start-date">' . esc_html__( 'Enrolled after', 'learnpress' ) . '</label>',
			'start-input'         => '<input id="lp-enrolled-filter-start-date" class="lp-enrolled-filter-start-date lp-enrolled-students-table-toolbar__input" type="date" value="' . esc_attr( $search_start ) . '" placeholder="mm/dd/yyyy">',
			'start-field-close'   => '</div>',
			'end-field-open'      => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--date">',
			'end-label'           => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-enrolled-filter-end-date">' . esc_html__( 'Enrolled before', 'learnpress' ) . '</label>',
			'end-input'           => '<input id="lp-enrolled-filter-end-date" class="lp-enrolled-filter-end-date lp-enrolled-students-table-toolbar__input" type="date" value="' . esc_attr( $search_end ) . '" placeholder="mm/dd/yyyy">',
			'end-field-close'     => '</div>',
			'actions-open'        => '<div class="lp-enrolled-students-table-toolbar__actions">',
			'search-btn'          => '<button type="button" class="button lp-button lp-enrolled-btn-search">' . esc_html__( 'Search', 'learnpress' ) . '</button>',
			'clear-btn'           => '<button type="button" class="button lp-button lp-enrolled-btn-clear">' . esc_html__( 'Clear Filter', 'learnpress' ) . '</button>',
			'actions-close'       => '</div>',
			'filter-row-close'    => '</div>',
			'wrap-end'            => '</div>',
		);
		$section = apply_filters(
			'learn-press/admin/enrolled-students/toolbar/section',
			$section,
			$courses,
			$selected_course,
			$instructor_id
		);

		return Template::combine_components( $section );
	}

	/**
	 * Render toolbar used inside View Students modal.
	 *
	 * @return string
	 */
	public function html_toolbar_modal(): string {

		$section = array(
			'wrap'                => '<div class="lp-enrolled-students-table-toolbar lp-enrolled-students-table-toolbar--modal">',
			'filter-row-open'     => '<div class="lp-enrolled-students-table-toolbar__row lp-enrolled-students-table-toolbar__row--filters">',
			'student-field-open'  => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--student">',
			'student-label'       => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-modal-enrolled-search-input">' . esc_html__( 'Student', 'learnpress' ) . '</label>',
			'student-input'       => '<input id="lp-modal-enrolled-search-input" class="lp-enrolled-search-input lp-enrolled-students-table-toolbar__input" type="text" placeholder="' . esc_attr__( 'Enter student name or email', 'learnpress' ) . '">',
			'student-field-close' => '</div>',
			'start-field-open'    => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--date">',
			'start-label'         => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-modal-enrolled-filter-start-date">' . esc_html__( 'Enrolled after', 'learnpress' ) . '</label>',
			'start-input'         => '<input id="lp-modal-enrolled-filter-start-date" class="lp-enrolled-filter-start-date lp-enrolled-students-table-toolbar__input" type="date" placeholder="mm/dd/yyyy">',
			'start-field-close'   => '</div>',
			'end-field-open'      => '<div class="lp-enrolled-students-table-toolbar__field lp-enrolled-students-table-toolbar__field--date">',
			'end-label'           => '<label class="lp-enrolled-students-table-toolbar__label" for="lp-modal-enrolled-filter-end-date">' . esc_html__( 'Enrolled before', 'learnpress' ) . '</label>',
			'end-input'           => '<input id="lp-modal-enrolled-filter-end-date" class="lp-enrolled-filter-end-date lp-enrolled-students-table-toolbar__input" type="date" placeholder="mm/dd/yyyy">',
			'end-field-close'     => '</div>',
			'actions-open'        => '<div class="lp-enrolled-students-table-toolbar__actions">',
			'search-btn'          => '<button type="button" class="button lp-button lp-enrolled-btn-search-modal">' . esc_html__( 'Search', 'learnpress' ) . '</button>',
			'clear-btn'           => '<button type="button" class="button lp-button lp-enrolled-btn-clear-modal">' . esc_html__( 'Clear Filter', 'learnpress' ) . '</button>',
			'actions-close'       => '</div>',
			'filter-row-close'    => '</div>',
			'wrap-end'            => '</div>',
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
		} elseif ( class_exists( '\LP_Page_Controller' ) && defined( 'LP_PAGE_PROFILE' ) ) {
					$should_print = \LP_Page_Controller::page_current() === LP_PAGE_PROFILE;
		}

		if ( ! $should_print ) {
				return;
		}

		echo '<script type="text/html" id="lp-tmpl-enrolled-students-toolbar-modal">';
		echo $this->html_toolbar_modal();
		echo '</script>';
	}
	/**
	 * HTML builder: table wrapper.
	 *
	 * @param array $rows        DB result rows.
	 * @param array $results_map user_item_id => progress percentage.
	 * @param array $meta        [ total, paged, per_page ].
	 *
	 * @return string
	 */
	private function html_table( array $rows, array $results_map, array $meta ): string {
		if ( empty( $rows ) ) {
			$section_empty = array(
				'wrap'     => '<div class="lp-enrolled-students-table-wrap">',
				'empty'    => '<div class="lp-enrolled-empty"><p>' . esc_html__( 'No students found.', 'learnpress' ) . '</p></div>',
				'wrap-end' => '</div>',
			);
			$section_empty = apply_filters(
				'learn-press/admin/enrolled-students/table/empty/section',
				$section_empty,
				$rows,
				$results_map,
				$meta
			);

			return Template::combine_components( $section_empty );
		}

		$rows_html = array();

		foreach ( $rows as $item ) {
			$rows_html[] = $this->html_student_row( $item, $results_map );
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
				array(
					'class' => 'lp-col-student',
					'title' => esc_html__( 'Student', 'learnpress' ),
				),
				array(
					'class' => 'lp-col-course',
					'title' => esc_html__( 'Course', 'learnpress' ),
				),
				array(
					'class' => 'lp-col-date',
					'title' => esc_html__( 'Enrolled Date', 'learnpress' ),
				),
				array(
					'class' => 'lp-col-progress',
					'title' => esc_html__( 'Progress', 'learnpress' ),
				),
				array(
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
	 * @param object $item
	 * @param array  $results_map
	 *
	 * @return string
	 */
	private function html_student_row( object $item, array $results_map ): string {
		// Avatar initials.
		$name     = $item->display_name;
		$initials = '';
		$parts    = explode( ' ', trim( $name ) );

		if ( count( $parts ) >= 2 ) {
			$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( end( $parts ), 0, 1 ) );
		} else {
			$initials = mb_strtoupper( mb_substr( $name, 0, 2 ) );
		}

		$user_id = (int) $item->user_id;

		$avatar_url = '';
		$user_model = UserModel::find( $user_id, true );
		if ( $user_model instanceof UserModel ) {
			$avatar_url = $user_model->get_avatar_url();
		}

		$avatar_class   = 'lp-avatar';
		$avatar_content = esc_html( $initials );
		if ( ! empty( $avatar_url ) ) {
			$avatar_class  .= ' lp-avatar--image';
			$avatar_content = sprintf(
				'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async">',
				esc_url( $avatar_url ),
				esc_attr( $name )
			);
		}

		// Progress.
		$progress = isset( $results_map[ $item->user_item_id ] ) ? round( floatval( $results_map[ $item->user_item_id ] ) ) : 0;

		// Status badge.
		$status_raw   = $item->graduation && $item->graduation !== 'in-progress'
			? $item->graduation
			: $item->status;
		$status_label = ucfirst( str_replace( array( '-', '_' ), ' ', $status_raw ) );
		$badge_class  = 'lp-badge--' . sanitize_html_class( $status_raw );

		// Date.
		$date = $item->start_time
			? wp_date( get_option( 'date_format' ), strtotime( $item->start_time ) )
			: '—';

		// Course link.
		$course_url   = get_edit_post_link( $item->item_id );
		$course_title = $item->course_title;

		$course_cell = $course_url
			? '<a href="' . esc_url( $course_url ) . '">' . esc_html( $course_title ) . '</a>'
			: esc_html( $course_title );

		$section = array(
			'row-open'            => '<tr>',
			'student-cell-open'   => '<td class="lp-cell-student">',
			'avatar'              => '<div class="' . $avatar_class . '">' . $avatar_content . '</div>',
			'meta-open'           => '<div class="lp-meta">',
			'name'                => '<span class="lp-name">' . esc_html( $name ) . '</span>',
			'email'               => '<span class="lp-email">' . esc_html( $item->user_email ) . '</span>',
			'meta-close'          => '</div>',
			'student-cell-close'  => '</td>',
			'course-cell'         => '<td class="lp-cell-course">' . $course_cell . '</td>',
			'date-cell'           => '<td class="lp-cell-date">' . esc_html( $date ) . '</td>',
			'progress-cell-open'  => '<td class="lp-cell-progress">',
			'progress-bar'        => '<div class="lp-progress-bar"><span style="width: ' . $progress . '%;"></span></div>',
			'progress-text'       => '<small class="lp-progress-text">' . $progress . '%</small>',
			'progress-cell-close' => '</td>',
			'status-cell-open'    => '<td class="lp-cell-status">',
			'status-badge'        => '<span class="lp-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $status_label ) . '</span>',
			'status-cell-close'   => '</td>',
			'row-close'           => '</tr>',
		);
		$section = apply_filters(
			'learn-press/admin/enrolled-students/row/section',
			$section,
			$item,
			$results_map,
			array(
				'course_cell'  => $course_cell,
				'progress'     => $progress,
				'status_raw'   => $status_raw,
				'status_label' => $status_label,
				'badge_class'  => $badge_class,
				'date'         => $date,
				'avatar_url'   => $avatar_url,
			)
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

	/**
	 * Sanitize date filter value in Y-m-d format.
	 *
	 * @param string $date
	 *
	 * @return string
	 */
	private static function sanitize_date_filter( $date ): string {
		if ( ! is_scalar( $date ) ) {
			return '';
		}

		$date = LP_Helper::sanitize_params_submitted( (string) $date );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return $date;
		}

		return '';
	}
}
