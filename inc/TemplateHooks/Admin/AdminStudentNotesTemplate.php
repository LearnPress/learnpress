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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * ThickBox (WP default admin modal) for the note details.
	 *
	 * @param string $hook_suffix Admin page hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'learnpress_page_' . self::PAGE_SLUG === $hook_suffix ) {
			add_thickbox();
		}
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
			'<p class="description">%s</p>',
			esc_html__( 'Review notes saved by students across LearnPress courses and lessons.', 'learnpress' )
		);
	}

	/**
	 * Stat boxes (WP .postbox).
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
				'<div class="postbox"><div class="inside"><p class="lp-student-notes__stat-label">%1$s</p><p class="lp-student-notes__stat-value">%2$s</p></div></div>',
				esc_html( $label ),
				esc_html( number_format_i18n( (int) ( $stats[ $key ] ?? 0 ) ) )
			);
		}

		return sprintf( '<div class="lp-student-notes__stats">%s</div>', $html );
	}

	/**
	 * Filters box (WP .postbox) with a GET form.
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

		$select = static function ( string $id, string $name, array $options, string $value ): string {
			$html = '';
			foreach ( $options as $key => $label ) {
				$html .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $key ),
					selected( $value, (string) $key, false ),
					esc_html( $label )
				);
			}

			return sprintf( '<select id="%1$s" name="%2$s">%3$s</select>', esc_attr( $id ), esc_attr( $name ), $html );
		};

		$field = static function ( string $id, string $label, string $input ): string {
			return sprintf(
				'<div class="lp-student-notes__field"><label for="%1$s">%2$s</label>%3$s</div>',
				esc_attr( $id ),
				esc_html( $label ),
				$input
			);
		};

		$fields = $field(
			'lp-student-notes-student',
			__( 'Student', 'learnpress' ),
			$select( 'lp-student-notes-student', 'student', $user_options, $args['student'] ? (string) $args['student'] : '' )
		)
			. $field(
				'lp-student-notes-course',
				__( 'Course', 'learnpress' ),
				$select( 'lp-student-notes-course', 'course', $course_options, $args['course'] ? (string) $args['course'] : '' )
			)
			. $field(
				'lp-student-notes-type',
				__( 'Type', 'learnpress' ),
				$select( 'lp-student-notes-type', 'note_type', $type_options, $args['note_type'] )
			)
			. $field(
				'lp-student-notes-search',
				__( 'Search', 'learnpress' ),
				sprintf(
					'<input id="lp-student-notes-search" type="search" name="s" value="%1$s" placeholder="%2$s">',
					esc_attr( $args['s'] ),
					esc_attr__( 'Student, course, lesson, note', 'learnpress' )
				)
			);

		return sprintf(
			'<div class="postbox lp-student-notes__filters">
				<div class="postbox-header"><h2 class="hndle">%1$s</h2></div>
				<div class="inside">
					<form method="get" action="%2$s">
						<input type="hidden" name="page" value="%3$s">
						<div class="lp-student-notes__fields">%4$s</div>
						<p class="lp-student-notes__actions">
							<button type="submit" class="button button-primary">%5$s</button>
							<a class="button" href="%6$s">%7$s</a>
						</p>
					</form>
				</div>
			</div>',
			esc_html__( 'Filters', 'learnpress' ),
			esc_url( admin_url( 'admin.php' ) ),
			esc_attr( self::PAGE_SLUG ),
			$fields,
			esc_html__( 'Apply Filter', 'learnpress' ),
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Reset', 'learnpress' )
		);
	}

	/**
	 * Notes table (WP list table markup).
	 *
	 * @param object[] $rows       Rows from NoteDB::get_notes() with join_details.
	 * @param array    $args       Request args.
	 * @param int      $total_rows Total rows.
	 *
	 * @return string
	 */
	public function html_table( array $rows, array $args, int $total_rows ): string {
		$columns = apply_filters(
			'learn-press/admin/student-notes/table/columns',
			array(
				'student' => __( 'Student', 'learnpress' ),
				'course'  => __( 'Course', 'learnpress' ),
				'lesson'  => __( 'Lesson', 'learnpress' ),
				'type'    => __( 'Type', 'learnpress' ),
				'content' => __( 'Content', 'learnpress' ),
				'created' => __( 'Created', 'learnpress' ),
				'actions' => __( 'Actions', 'learnpress' ),
			)
		);

		$head = '';
		foreach ( $columns as $key => $title ) {
			$head .= sprintf(
				'<th scope="col" class="manage-column column-%1$s%2$s">%3$s</th>',
				esc_attr( $key ),
				'student' === $key ? ' column-primary' : '',
				esc_html( $title )
			);
		}

		$body = '';
		foreach ( $rows as $row ) {
			$body .= $this->html_row( $row );
		}

		if ( '' === $body ) {
			$body = sprintf(
				'<tr class="no-items"><td class="colspanchange" colspan="%1$d">%2$s</td></tr>',
				count( $columns ),
				esc_html__( 'No notes found.', 'learnpress' )
			);
		}

		return sprintf(
			'<div class="lp-student-notes__table-wrap"><table class="wp-list-table widefat striped table-view-list lp-student-notes__table"><thead><tr>%1$s</tr></thead><tbody>%2$s</tbody><tfoot><tr>%1$s</tr></tfoot></table></div>%3$s',
			$head,
			$body,
			$this->html_tablenav( $args, $total_rows )
		);
	}

	/**
	 * WP list table pagination ("tablenav-pages").
	 *
	 * @param array $args       Request args.
	 * @param int   $total_rows Total rows.
	 *
	 * @return string
	 */
	public function html_tablenav( array $args, int $total_rows ): string {
		$total_pages = max( 1, (int) ceil( $total_rows / self::PER_PAGE ) );
		$current     = min( $args['paged'], $total_pages );
		$url         = function ( int $page ) use ( $args ): string {
			return esc_url( add_query_arg( 'paged', $page, $this->get_page_url( $args ) ) );
		};
		$link        = static function ( bool $enabled, string $href, string $css_class, string $label, string $symbol ): string {
			if ( ! $enabled ) {
				return sprintf( '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">%s</span>', $symbol );
			}

			return sprintf(
				'<a class="%1$s button" href="%2$s"><span class="screen-reader-text">%3$s</span><span aria-hidden="true">%4$s</span></a>',
				esc_attr( $css_class ),
				$href,
				esc_html( $label ),
				$symbol
			);
		};

		$pagination = sprintf(
			'<span class="pagination-links">%1$s %2$s <span class="paging-input"><span class="tablenav-paging-text">%3$s</span></span> %4$s %5$s</span>',
			$link( $current > 1, $url( 1 ), 'first-page', __( 'First page', 'learnpress' ), '&laquo;' ),
			$link( $current > 1, $url( $current - 1 ), 'prev-page', __( 'Previous page', 'learnpress' ), '&lsaquo;' ),
			sprintf(
				/* translators: 1: current page, 2: total pages */
				esc_html__( '%1$s of %2$s', 'learnpress' ),
				number_format_i18n( $current ),
				sprintf( '<span class="total-pages">%s</span>', number_format_i18n( $total_pages ) )
			),
			$link( $current < $total_pages, $url( $current + 1 ), 'next-page', __( 'Next page', 'learnpress' ), '&rsaquo;' ),
			$link( $current < $total_pages, $url( $total_pages ), 'last-page', __( 'Last page', 'learnpress' ), '&raquo;' )
		);

		return sprintf(
			'<div class="tablenav bottom"><div class="tablenav-pages%1$s"><span class="displaying-num">%2$s</span>%3$s</div><br class="clear"></div>',
			$total_pages <= 1 ? ' one-page' : '',
			esc_html(
				sprintf(
					/* translators: %s: number of notes */
					_n( '%s note', '%s notes', $total_rows, 'learnpress' ),
					number_format_i18n( $total_rows )
				)
			),
			$pagination
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

		// Same as the WP Users list: avatar + name, email below.
		$student = sprintf(
			'<div class="lp-student-notes__student">%1$s<div><strong>%2$s</strong><br><span class="description">%3$s</span></div></div>',
			get_avatar( $note->user_id, 32 ),
			esc_html( $row->display_name ? $row->display_name : '#' . $note->user_id ),
			esc_html( $row->user_email ?? '' )
		);

		$course_title = $row->course_title ? $row->course_title : '#' . $note->course_id;
		$course_html  = $course
			? sprintf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $course->get_permalink() ), esc_html( $course_title ) )
			: esc_html( $course_title );

		$lesson_title = $row->item_title ? $row->item_title : '#' . $note->item_id;
		$lesson_html  = $item_link
			? sprintf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $item_link ), esc_html( $lesson_title ) )
			: esc_html( $lesson_title );

		$created = $timestamp
			? sprintf(
				'%1$s<br>%2$s',
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

		// data-colname + .toggle-row: WP list table responsive view (handled by wp-admin common.js).
		$td = static function ( string $key, string $label, string $html ): string {
			return sprintf( '<td class="column-%1$s" data-colname="%2$s">%3$s</td>', esc_attr( $key ), esc_attr( $label ), $html );
		};

		$section = apply_filters(
			'learn-press/admin/student-notes/row/section',
			array(
				'tr'      => sprintf( '<tr data-note-id="%d">', $note->get_note_id() ),
				'student' => sprintf(
					'<td class="column-student column-primary">%1$s<button type="button" class="toggle-row"><span class="screen-reader-text">%2$s</span></button></td>',
					$student,
					esc_html__( 'Show more details', 'learnpress' )
				),
				'course'  => $td( 'course', __( 'Course', 'learnpress' ), $course_html ),
				'lesson'  => $td( 'lesson', __( 'Lesson', 'learnpress' ), $lesson_html ),
				'type'    => $td(
					'type',
					__( 'Type', 'learnpress' ),
					esc_html( $is_highlight ? __( 'Highlight', 'learnpress' ) : __( 'Text', 'learnpress' ) )
				),
				'content' => $td(
					'content',
					__( 'Content', 'learnpress' ),
					$this->html_content(
						$note,
						array(
							__( 'Student', 'learnpress' ) => $student,
							__( 'Course', 'learnpress' )  => $course_html,
							__( 'Lesson', 'learnpress' )  => $lesson_html,
							__( 'Created', 'learnpress' ) => $created,
						),
						$open_link
					)
				),
				'created' => $td( 'created', __( 'Created', 'learnpress' ), $created ),
				'actions' => $td( 'actions', __( 'Actions', 'learnpress' ), $action ),
				'tr_end'  => '</tr>',
			),
			$note,
			$row
		);

		return Template::combine_components( $section );
	}

	/**
	 * Content cell: short quote + note, and a "View note" link opening the details in ThickBox.
	 *
	 * @param NoteModel $note      Note.
	 * @param array     $meta      Label => HTML (already escaped) shown in the modal.
	 * @param string    $open_link Lesson URL focused on the note.
	 *
	 * @return string
	 */
	public function html_content( NoteModel $note, array $meta = array(), string $open_link = '' ): string {
		$quote     = NoteModel::TYPE_HIGHLIGHT === $note->note_type ? $note->highlight_text : '';
		$detail_id = 'lp-note-detail-' . $note->get_note_id();

		$rows = '';
		foreach ( $meta as $label => $html ) {
			$rows .= sprintf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), $html );
		}

		$detail = sprintf(
			'<div id="%1$s" style="display:none;">
				<div class="lp-student-notes__detail">
					<table class="form-table" role="presentation"><tbody>%2$s</tbody></table>
					%3$s
					<h3>%4$s</h3>
					<p>%5$s</p>
					%6$s
				</div>
			</div>',
			esc_attr( $detail_id ),
			$rows,
			'' !== $quote
				? sprintf( '<h3>%1$s</h3><blockquote>%2$s</blockquote>', esc_html__( 'Highlighted text', 'learnpress' ), nl2br( esc_html( $quote ) ) )
				: '',
			esc_html__( 'Note', 'learnpress' ),
			'' !== $note->content ? nl2br( esc_html( $note->content ) ) : sprintf( '<em>%s</em>', esc_html__( 'No note content.', 'learnpress' ) ),
			$open_link
				? sprintf(
					'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener">%2$s</a></p>',
					esc_url( $open_link ),
					esc_html__( 'Open Lesson', 'learnpress' )
				)
				: ''
		);

		return sprintf(
			'%1$s<a href="%2$s" class="thickbox lp-student-notes__view" title="%3$s">%4$s</a>%5$s',
			$this->html_note_text( $this->excerpt( $quote, 60 ), $this->excerpt( $note->content, self::EXCERPT ) ),
			esc_url( '#TB_inline?width=640&height=480&inlineId=' . $detail_id ),
			esc_attr__( 'Student note', 'learnpress' ),
			esc_html__( 'View note', 'learnpress' ),
			$detail
		);
	}

	/**
	 * Quote + note text.
	 *
	 * @param string $quote Highlighted text.
	 * @param string $text  Note content.
	 *
	 * @return string
	 */
	protected function html_note_text( string $quote, string $text ): string {
		$html = '';
		if ( '' !== $quote ) {
			$html .= sprintf( '<span class="lp-student-notes__quote">&ldquo;%s&rdquo;</span>', esc_html( $quote ) );
		}

		if ( '' !== $text ) {
			$html .= sprintf( '<span class="lp-student-notes__text">%s</span>', nl2br( esc_html( $text ) ) );
		}

		return $html;
	}

	/**
	 * Cut plain text by characters (wp_html_excerpt() would strip text like "1<2").
	 *
	 * @param string $text   Text.
	 * @param int    $length Max length.
	 *
	 * @return string
	 */
	protected function excerpt( string $text, int $length ): string {
		if ( mb_strlen( $text ) <= $length ) {
			return $text;
		}

		return rtrim( mb_substr( preg_replace( '/\s+/u', ' ', $text ), 0, $length ) ) . '…';
	}
}
