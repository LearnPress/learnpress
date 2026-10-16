<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Services\NoteService;
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
				'title'   => __( 'Notes', 'learnpress' ),
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
		return array(
			'student'   => absint( LP_Request::get_param( 'student', 0, 'int', 'get' ) ),
			'course'    => absint( LP_Request::get_param( 'course', 0, 'int', 'get' ) ),
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
				's'         => $args['s'] ?? '',
			),
			$change
		);
		$query = array_filter( $query, static fn( $value ) => null !== $value && '' !== $value && 0 !== $value );

		return add_query_arg( array_map( 'rawurlencode', $query ), admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
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
		$data = NoteService::instance()->get_admin_list( get_current_user_id(), $args, self::PER_PAGE );
		if ( ! $data ) {
			return $this->html_description() . Template::print_message(
				__( 'You do not have any course to review notes for.', 'learnpress' ),
				'info',
				false
			);
		}

		$rows = $data['rows'];

		$section = apply_filters(
			'learn-press/admin/student-notes/page/section',
			array(
				'wrap'        => '<div class="lp-student-notes">',
				'description' => $this->html_description(),
				'stats'       => $this->html_stats( $data['stats'] ),
				'filters'     => $this->html_filters( $args, $data['users'], $data['courses'] ),
				'table'       => $this->html_table( $rows, $args, $data['total_rows'] ),
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
				AdminTemplate::html_tom_select(
					array(
						'id'            => 'lp-student-notes-student',
						'name'          => 'student',
						'options'       => $user_options,
						'default_value' => $args['student'] ? (string) $args['student'] : '',
					)
				),
				'lp-student-notes-student',
				__( 'Search by student name or email.', 'learnpress' )
			)
			. $field(
				__( 'Course', 'learnpress' ),
				AdminTemplate::html_tom_select(
					array(
						'id'            => 'lp-student-notes-course',
						'name'          => 'course',
						'options'       => $course_options,
						'default_value' => $args['course'] ? (string) $args['course'] : '',
					)
				),
				'lp-student-notes-course',
				__( 'Search by course title.', 'learnpress' )
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
			'<a class="lp-button lp-be-btn lp-be-btn--outline lp-student-notes__reset" href="%1$s">%2$s</a><button type="submit" class="lp-button lp-be-btn lp-be-btn--primary">%3$s</button>',
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
				'<div class="lp-student-notes__table lp-student-notes__table--empty">%s</div>',
				Template::print_message( __( 'No notes found.', 'learnpress' ), 'info', false )
			);
		}

		$columns = array(
			'student' => __( 'Student', 'learnpress' ),
			'course'  => __( 'Course', 'learnpress' ),
			'lesson'  => __( 'Lesson', 'learnpress' ),
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
		$course       = CourseModel::find( $note->course_id, true );
		$item_link    = $course ? $course->get_item_link( $note->item_id, $note->item_type ) : '';
		$open_link    = $item_link;
		$timestamp    = $note->get_created_timestamp();

		$student = sprintf(
			'<strong>%1$s</strong><span class="lp-student-notes__email">%2$s</span>',
			esc_html( $row->display_name ? $row->display_name : '#' . $note->user_id ),
			esc_html( $row->user_email ?? '' )
		);

		$course_title = $row->course_title ? $row->course_title : '#' . $note->course_id;

		$lesson = $row->item_title ? $row->item_title : '#' . $note->item_id;
		$lesson = $item_link
			? sprintf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $item_link ), esc_html( $lesson ) )
			: esc_html( $lesson );

		$created = $timestamp
			? sprintf(
				'<span>%1$s</span><span class="lp-student-notes__time">%2$s</span>',
				esc_html( wp_date( get_option( 'date_format' ), $timestamp ) ),
				esc_html( wp_date( get_option( 'time_format' ), $timestamp ) )
			)
			: '';

		$action = $this->html_actions( $note, $open_link );

		$section = apply_filters(
			'learn-press/admin/student-notes/row/section',
			array(
				'tr'      => sprintf( '<tr data-note-id="%d">', $note->get_note_id() ),
				'student' => sprintf( '<td class="lp-col-student">%s</td>', $student ),
				'course'  => sprintf( '<td class="lp-col-course">%s</td>', esc_html( $course_title ) ),
				'lesson'  => sprintf( '<td class="lp-col-lesson">%s</td>', $lesson ),
				'content' => sprintf(
					'<td class="lp-col-content">%s</td>',
					$this->html_content(
						$note,
						array(
							__( 'Student', 'learnpress' ) => $student,
							__( 'Course', 'learnpress' )  => esc_html( $course_title ),
							__( 'Lesson', 'learnpress' )  => $lesson,
							__( 'Created', 'learnpress' ) => $created,
						),
						$open_link
					)
				),
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
	 * Mutating actions use the same course permissions as the server's note handlers.
	 */
	public function html_actions( NoteModel $note, string $open_link = '' ): string {
		$html    = $open_link
			? '<a class="lp-button lp-be-btn lp-be-btn--sm lp-be-btn--outline" href="' . esc_url( $open_link ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open Lesson', 'learnpress' ) . '</a>'
			: '';
		$service = NoteService::instance();
		$viewer  = get_current_user_id();
		if ( $service->can_manage_course( $viewer, $note->course_id ) && $service->can_manage( $viewer, $note ) ) {
			$template_id = 'lp-note-edit-' . $note->get_note_id();
			$html       .= sprintf(
				' <button type="button" class="lp-button lp-be-btn lp-be-btn--sm lp-be-btn--primary" data-note-template="%3$s" data-title="%4$s">%4$s</button> <button type="button" class="lp-button lp-be-btn lp-be-btn--sm lp-be-btn--danger lp-student-notes__delete" data-note-id="%1$d" data-confirm="%2$s">%5$s</button><template id="%3$s"><form class="lp-student-notes__edit-form" data-note-id="%1$d"><p><label for="%3$s-content">%7$s</label></p><textarea id="%3$s-content" name="content" class="swal2-textarea" rows="8" maxlength="%8$d" %9$s>%6$s</textarea><p class="submit"><button type="submit" class="lp-button lp-be-btn lp-be-btn--primary">%10$s</button> <button type="button" class="lp-button lp-be-btn lp-be-btn--outline lp-student-notes__cancel">%11$s</button></p></form></template>',
				$note->get_note_id(),
				esc_attr__( 'Delete this note?', 'learnpress' ),
				esc_attr( $template_id ),
				esc_html__( 'Edit', 'learnpress' ),
				esc_html__( 'Delete', 'learnpress' ),
				esc_textarea( $note->content ),
				esc_html__( 'Note content', 'learnpress' ),
				NoteModel::CONTENT_MAX_LENGTH,
				NoteModel::TYPE_TEXT === $note->note_type ? 'required' : '',
				esc_html__( 'Save', 'learnpress' ),
				esc_html__( 'Cancel', 'learnpress' )
			);
		}
		return '<div class="lp-student-notes__actions">' . $html . '</div>';
	}

	/**
	 * Content cell: short quote + note, and details opened by the shared LearnPress dialog.
	 *
	 * @param NoteModel $note      Note.
	 * @param array     $meta      Label => HTML (already escaped) shown in the modal.
	 * @param string    $open_link Lesson URL.
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
			'<template id="%1$s">
				<div class="lp-student-notes__detail">
					<table class="form-table" role="presentation"><tbody>%2$s</tbody></table>
					%3$s
					<h3>%4$s</h3>
					<p>%5$s</p>
					%6$s
				</div>
			</template>',
			esc_attr( $detail_id ),
			$rows,
			'' !== $quote
				? sprintf( '<h3>%1$s</h3><blockquote>%2$s</blockquote>', esc_html__( 'Highlighted text', 'learnpress' ), nl2br( esc_html( $quote ) ) )
				: '',
			esc_html__( 'Note', 'learnpress' ),
			'' !== $note->content ? nl2br( esc_html( $note->content ) ) : sprintf( '<em>%s</em>', esc_html__( 'No note content.', 'learnpress' ) ),
			$open_link
				? sprintf(
					'<p><a class="lp-button lp-be-btn lp-be-btn--primary" href="%1$s" target="_blank" rel="noopener">%2$s</a></p>',
					esc_url( $open_link ),
					esc_html__( 'Open Lesson', 'learnpress' )
				)
				: ''
		);

		return sprintf(
			'%1$s<button type="button" class="lp-button lp-be-btn lp-be-btn--sm lp-be-btn--secondary" data-note-template="%2$s" data-title="%3$s">%4$s</button>%5$s',
			$this->html_note_text( $this->excerpt( $quote, 60 ), $this->excerpt( $note->content, self::EXCERPT ) ),
			esc_attr( $detail_id ),
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
