<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Databases\NoteDB;
use LearnPress\Filters\NoteFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Services\NoteService;
use LearnPress\TemplateHooks\Course\CourseNoteTemplate;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LP_Debug;
use LP_Request;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Admin page: Student Notes.
 *
 * Admins review all notes; instructors only notes of courses they author / co-instruct.
 * Server rendered: filters and pagination are GET params of the page URL. Newest notes first.
 *
 * @since 4.4.9.2
 * @version 1.0.0
 */
class AdminStudentNotesTemplate {
	use Singleton;

	const PAGE_SLUG = 'learn-press-student-notes';
	const PER_PAGE  = 20;
	const EXCERPT   = 80;

	public function init() {
	}

	/**
	 * Admin page callback (config/wp-menus.php).
	 */
	public function admin_page_output() {
		try {
			$args    = $this->get_request_args();
			$content = $this->html_page( $args );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
			$content = Template::print_message( $e->getMessage(), 'error', false );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'content' => $content,
				'title'   => __( 'Student Notes', 'learnpress' ),
				'id'      => 'lp-student-notes',
			)
		);
	}

	/**
	 * Read and sanitize filters from the URL.
	 *
	 * @return array
	 */
	public function get_request_args(): array {
		$type = LP_Request::get_param( 'note_type', '', 'key', 'get' );

		return array(
			'student'   => absint( LP_Request::get_param( 'student', 0, 'int', 'get' ) ),
			'course'    => absint( LP_Request::get_param( 'course', 0, 'int', 'get' ) ),
			'note_type' => in_array( $type, array( NoteModel::TYPE_TEXT, NoteModel::TYPE_HIGHLIGHT ), true ) ? $type : '',
			's'         => trim( LP_Request::get_param( 's', '', 'text', 'get' ) ),
			'paged'     => max( 1, absint( LP_Request::get_param( 'paged', 1, 'int', 'get' ) ) ),
		);
	}

	/**
	 * Page URL with the current filters.
	 *
	 * @param array $args   Current args.
	 * @param array $change Args to override (null removes).
	 *
	 * @return string
	 */
	public function get_page_url( array $args = array(), array $change = array() ): string {
		$query = array_merge(
			array(
				'student'   => $args['student'] ?? 0,
				'course'    => $args['course'] ?? 0,
				'note_type' => $args['note_type'] ?? '',
				's'         => $args['s'] ?? '',
			),
			$change
		);
		$query = array_filter( $query, static fn( $value ) => null !== $value && '' !== $value && 0 !== $value );

		return add_query_arg( array_map( 'rawurlencode', $query ), admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
	}

	/**
	 * Base filter restricted to what the current user can view.
	 *
	 * @return NoteFilter|false False when the user can view no course.
	 */
	protected function get_scope_filter() {
		$filter     = new NoteFilter();
		$course_ids = NoteService::instance()->get_viewable_course_ids( get_current_user_id() );

		if ( is_array( $course_ids ) ) {
			if ( empty( $course_ids ) ) {
				return false;
			}

			$filter->course_ids = $course_ids;
		}

		return $filter;
	}

	/**
	 * Apply request filters to a scope filter.
	 *
	 * @param NoteFilter $filter Scope filter.
	 * @param array      $args   Request args.
	 *
	 * @return NoteFilter
	 */
	protected function apply_request_filters( NoteFilter $filter, array $args ): NoteFilter {
		$filter->join_details = true;

		if ( $args['course'] ) {
			// Instructors can only narrow down to their own courses.
			if ( ! empty( $filter->course_ids ) && ! in_array( $args['course'], $filter->course_ids, true ) ) {
				$filter->course_ids = array( 0 );
			} else {
				$filter->course_id = $args['course'];
			}
		}

		if ( $args['student'] ) {
			$filter->user_id = $args['student'];
		}

		if ( $args['note_type'] ) {
			$filter->note_type = $args['note_type'];
		}

		if ( '' !== $args['s'] ) {
			$filter->key_word = $args['s'];
		}

		return $filter;
	}

	/**
	 * Whole page content.
	 *
	 * @param array $args Request args.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function html_page( array $args ): string {
		$scope = $this->get_scope_filter();
		if ( ! $scope ) {
			return $this->html_description() . Template::print_message(
				__( 'You do not have any course to review notes for.', 'learnpress' ),
				'info',
				false
			);
		}

		$db = NoteDB::getInstance();

		// List.
		$filter        = $this->apply_request_filters( clone $scope, $args );
		$filter->limit = self::PER_PAGE;
		$filter->page  = $args['paged'];
		// Newest first; note_id breaks ties (same second) so pages never overlap.
		$filter->order_by    = 'n.created_at DESC, n.note_id';
		$filter->order       = NoteFilter::ORDER_DESC;
		$filter->field_count = NoteFilter::COL_NOTE_ID;
		$total_rows          = 0;
		$rows                = $db->get_notes( $filter, $total_rows );
		$rows                = is_array( $rows ) ? $rows : array();

		// Stats follow the current filters.
		$stats = $db->get_stats( $this->apply_request_filters( clone $scope, $args ) );

		// Filter options: everything in scope, ignoring the current filters.
		$users   = $db->get_note_users( clone $scope );
		$courses = $db->get_note_courses( clone $scope );

		$section = apply_filters(
			'learn-press/admin/student-notes/page/section',
			array(
				'wrap'        => '<div class="lp-student-notes">',
				'description' => $this->html_description(),
				'stats'       => $this->html_stats( $stats ),
				'filters'     => $this->html_filters( $args, $users, $courses ),
				'table'       => $this->html_table( $rows, $args, $total_rows ),
				'wrap_end'    => '</div>',
			),
			$args,
			$rows
		);

		return Template::combine_components( $section );
	}

	/**
	 * @return string
	 */
	public function html_description(): string {
		return sprintf(
			'<p class="lp-student-notes__description">%s</p>',
			esc_html__( 'Review notes saved by students across LearnPress courses and lessons.', 'learnpress' )
		);
	}

	/**
	 * Stat cards.
	 *
	 * @param array $stats NoteDB::get_stats().
	 *
	 * @return string
	 */
	public function html_stats( array $stats ): string {
		$cards = array(
			'total_notes'    => __( 'Total notes', 'learnpress' ),
			'total_students' => __( 'Students with notes', 'learnpress' ),
			'total_courses'  => __( 'Courses with notes', 'learnpress' ),
		);

		$html = '';
		foreach ( $cards as $key => $label ) {
			$html .= sprintf(
				'<div class="lp-student-notes__stat"><span class="lp-student-notes__stat-label">%s</span><span class="lp-student-notes__stat-value">%s</span></div>',
				esc_html( $label ),
				esc_html( number_format_i18n( (int) ( $stats[ $key ] ?? 0 ) ) )
			);
		}

		return sprintf( '<div class="lp-student-notes__stats">%s</div>', $html );
	}

	/**
	 * Filter form (GET).
	 *
	 * @param array    $args    Request args.
	 * @param object[] $users   Students having notes.
	 * @param object[] $courses Courses having notes.
	 *
	 * @return string
	 */
	public function html_filters( array $args, array $users, array $courses ): string {
		$user_options = array( '' => __( 'All students', 'learnpress' ) );
		foreach ( $users as $user ) {
			$name = $user->display_name ? $user->display_name : '#' . $user->ID;
			// Translators: 1: student name, 2: email.
			$user_options[ (string) $user->ID ] = sprintf( __( '%1$s (%2$s)', 'learnpress' ), $name, $user->user_email );
		}

		$course_options = array( '' => __( 'All courses', 'learnpress' ) );
		foreach ( $courses as $course ) {
			$course_options[ (string) $course->ID ] = $course->post_title ? $course->post_title : '#' . $course->ID;
		}

		$type_options = array(
			''                         => __( 'All types', 'learnpress' ),
			NoteModel::TYPE_HIGHLIGHT => __( 'Highlight', 'learnpress' ),
			NoteModel::TYPE_TEXT      => __( 'Text', 'learnpress' ),
		);

		$field = static function ( string $label, string $input, string $id, string $hint = '' ): string {
			return sprintf(
				'<div class="filter-field"><label for="%1$s">%2$s</label>%3$s%4$s</div>',
				esc_attr( $id ),
				esc_html( $label ),
				$input,
				$hint ? sprintf( '<p class="description">%s</p>', esc_html( $hint ) ) : ''
			);
		};

		$fields = sprintf( '<input type="hidden" name="page" value="%s">', esc_attr( self::PAGE_SLUG ) )
			. $field(
				__( 'Student', 'learnpress' ),
				str_replace(
					'<select ',
					'<select id="lp-student-notes-student" ',
					AdminTemplate::html_tom_select(
						array(
							'name'          => 'student',
							'options'       => $user_options,
							'default_value' => $args['student'] ? (string) $args['student'] : '',
						)
					)
				),
				'lp-student-notes-student',
				__( 'Search by student name or email.', 'learnpress' )
			)
			. $field(
				__( 'Course', 'learnpress' ),
				str_replace(
					'<select ',
					'<select id="lp-student-notes-course" ',
					AdminTemplate::html_tom_select(
						array(
							'name'          => 'course',
							'options'       => $course_options,
							'default_value' => $args['course'] ? (string) $args['course'] : '',
						)
					)
				),
				'lp-student-notes-course',
				__( 'Search by course title.', 'learnpress' )
			)
			. $field(
				__( 'Type', 'learnpress' ),
				str_replace(
					'<select ',
					'<select id="lp-student-notes-type" ',
					AdminTemplate::html_tom_select(
						array(
							'name'          => 'note_type',
							'options'       => $type_options,
							'default_value' => $args['note_type'],
						)
					)
				),
				'lp-student-notes-type'
			)
			. $field(
				__( 'Search', 'learnpress' ),
				sprintf(
					'<input id="lp-student-notes-search" type="search" name="s" value="%1$s" placeholder="%2$s">',
					esc_attr( $args['s'] ),
					esc_attr__( 'Student, course, lesson, note', 'learnpress' )
				),
				'lp-student-notes-search'
			);

		$actions = sprintf(
			'<a class="button lp-student-notes__reset" href="%1$s">%2$s</a><button type="submit" class="button button-primary">%3$s</button>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Reset', 'learnpress' ),
			esc_html__( 'Apply Filter', 'learnpress' )
		);

		return sprintf(
			'<div class="lp-student-notes__filters"><h2>%s</h2>%s</div>',
			esc_html__( 'Filters', 'learnpress' ),
			AdminTemplate::html_form_filter(
				array(
					'form_classes' => 'lp-student-notes__form',
					'fields'       => $fields,
					'btn_actions'  => $actions,
				)
			)
		);
	}

	/**
	 * Notes table with page result and pagination.
	 *
	 * @param object[] $rows       Rows from NoteDB::get_notes() with join_details.
	 * @param array    $args       Request args.
	 * @param int      $total_rows Total rows.
	 *
	 * @return string
	 */
	public function html_table( array $rows, array $args, int $total_rows ): string {
		if ( empty( $rows ) ) {
			return sprintf(
				'<div class="lp-student-notes__table">%s</div>',
				Template::print_message( __( 'No notes found.', 'learnpress' ), 'info', false )
			);
		}

		$columns = array(
			'student' => __( 'Student', 'learnpress' ),
			'course'  => __( 'Course', 'learnpress' ),
			'lesson'  => __( 'Lesson', 'learnpress' ),
			'type'    => __( 'Type', 'learnpress' ),
			'content' => __( 'Content', 'learnpress' ),
			'created' => __( 'Created', 'learnpress' ),
			'actions' => __( 'Actions', 'learnpress' ),
		);

		$header = array();
		foreach ( $columns as $key => $title ) {
			$header[ $key ] = array(
				'class' => 'lp-col-' . $key,
				'title' => esc_html( $title ),
			);
		}

		$rows_html = '';
		foreach ( $rows as $row ) {
			$rows_html .= $this->html_row( $row );
		}

		$total_pages = (int) ceil( $total_rows / self::PER_PAGE );
		$footer      = sprintf(
			'<div class="lp-student-notes__footer"><span>%1$s</span>%2$s</div>',
			TableListTemplate::instance()->html_page_result(
				array(
					'paged'      => $args['paged'],
					'per_page'   => self::PER_PAGE,
					'total_rows' => $total_rows,
					'item_name'  => _n( 'note', 'notes', $total_rows, 'learnpress' ),
				)
			),
			Template::instance()->html_pagination(
				array(
					'total_pages' => $total_pages,
					'paged'       => $args['paged'],
					'base'        => add_query_arg( 'paged', '%#%', $this->get_page_url( $args ) ),
					'format'      => '',
				)
			)
		);

		$table_args = apply_filters(
			'learn-press/admin/student-notes/table/args',
			array(
				'class_table' => 'lp-student-notes-table',
				'header'      => $header,
				'body'        => array( 'rows_html' => $rows_html ),
			),
			$rows,
			$args
		);

		return sprintf(
			'<div class="lp-student-notes__table">%s%s</div>',
			TableListTemplate::instance()->html_table( $table_args ),
			$footer
		);
	}

	/**
	 * One table row.
	 *
	 * @param object $row Row from NoteDB::get_notes() with join_details.
	 *
	 * @return string
	 */
	public function html_row( $row ): string {
		$note         = new NoteModel( $row );
		$is_highlight = NoteModel::TYPE_HIGHLIGHT === $note->note_type;
		$course       = CourseModel::find( $note->course_id, true );
		$item_link    = $course ? $course->get_item_link( $note->item_id, $note->item_type ) : '';
		$open_link    = $item_link
			? add_query_arg( CourseNoteTemplate::PARAM_NOTE_USER, $note->user_id, $item_link ) . '#lp-note-' . $note->get_note_id()
			: '';
		$timestamp    = strtotime( $note->created_at . ' UTC' );

		$student = sprintf(
			'<strong>%1$s</strong><span class="lp-student-notes__email">%2$s</span>',
			esc_html( $row->display_name ? $row->display_name : '#' . $note->user_id ),
			esc_html( $row->user_email ?? '' )
		);

		$lesson = $row->item_title ? $row->item_title : '#' . $note->item_id;
		$lesson = $item_link
			? sprintf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $item_link ), esc_html( $lesson ) )
			: esc_html( $lesson );

		$type = sprintf(
			'<span class="lp-student-notes__badge lp-student-notes__badge--%1$s">%2$s</span>',
			esc_attr( $note->note_type ),
			esc_html( $is_highlight ? __( 'Highlight', 'learnpress' ) : __( 'Text', 'learnpress' ) )
		);

		$created = $timestamp
			? sprintf(
				'<span>%1$s</span><span class="lp-student-notes__time">%2$s</span>',
				esc_html( wp_date( get_option( 'date_format' ), $timestamp ) ),
				esc_html( wp_date( get_option( 'time_format' ), $timestamp ) )
			)
			: '';

		$action = $open_link
			? sprintf(
				'<a class="button button-small" href="%1$s" target="_blank" rel="noopener">%2$s</a>',
				esc_url( $open_link ),
				esc_html__( 'Open Lesson', 'learnpress' )
			)
			: '';

		$section = apply_filters(
			'learn-press/admin/student-notes/row/section',
			array(
				'tr'      => sprintf( '<tr data-note-id="%d">', $note->get_note_id() ),
				'student' => sprintf( '<td class="lp-col-student">%s</td>', $student ),
				'course'  => sprintf( '<td class="lp-col-course">%s</td>', esc_html( $row->course_title ? $row->course_title : '#' . $note->course_id ) ),
				'lesson'  => sprintf( '<td class="lp-col-lesson">%s</td>', $lesson ),
				'type'    => sprintf( '<td class="lp-col-type">%s</td>', $type ),
				'content' => sprintf( '<td class="lp-col-content">%s</td>', $this->html_content( $note ) ),
				'created' => sprintf( '<td class="lp-col-created">%s</td>', $created ),
				'actions' => sprintf( '<td class="lp-col-actions">%s</td>', $action ),
				'tr_end'  => '</tr>',
			),
			$note,
			$row
		);

		return Template::combine_components( $section );
	}

	/**
	 * Content cell: excerpt, expandable to the highlighted text + full note.
	 *
	 * @param NoteModel $note Note.
	 *
	 * @return string
	 */
	public function html_content( NoteModel $note ): string {
		$is_highlight = NoteModel::TYPE_HIGHLIGHT === $note->note_type;
		$text         = '' !== $note->content ? $note->content : $note->highlight_text;
		// Plain text: cut by characters (wp_html_excerpt() would strip text like "1<2").
		$excerpt = preg_replace( '/\s+/u', ' ', $text );
		if ( mb_strlen( $excerpt ) > self::EXCERPT ) {
			$excerpt = rtrim( mb_substr( $excerpt, 0, self::EXCERPT ) ) . '…';
		}
		$is_long = $excerpt !== $text || $is_highlight;

		if ( ! $is_long ) {
			return sprintf( '<span class="lp-student-notes__excerpt">%s</span>', esc_html( $text ) );
		}

		$full = '';
		if ( $is_highlight ) {
			$full .= sprintf( '<blockquote>%s</blockquote>', esc_html( $note->highlight_text ) );
		}

		if ( '' !== $note->content ) {
			$full .= sprintf( '<p>%s</p>', nl2br( esc_html( $note->content ) ) );
		}

		return sprintf(
			'<details class="lp-student-notes__content"><summary><span class="lp-student-notes__excerpt">%1$s</span><span class="lp-icon lp-icon-eye" title="%2$s" aria-hidden="true"></span><span class="screen-reader-text">%2$s</span></summary><div class="lp-student-notes__full">%3$s</div></details>',
			esc_html( $excerpt ),
			esc_attr__( 'View full note', 'learnpress' ),
			$full
		);
	}
}
