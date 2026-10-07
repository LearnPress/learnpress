<?php
/**
 * Template hook: Student Notes on the learning page (lesson only).
 *
 * - Launcher button in the shared footer launchers (learn-press/course-item-footer-launchers).
 * - Notes sidebar panel rendered on wp_footer; list/cards are rendered by JS from localized data.
 *
 * Modes:
 * - edit:     the enrolled student manages their own notes.
 * - readonly: an admin / course instructor views a student's notes via `?lp_note_user={user_id}`.
 *
 * @since 4.4.9.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\NoteService;
use LP_Global;
use LP_Page_Controller;
use LP_Request;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CourseNoteTemplate {
	use Singleton;

	const MODE_EDIT     = 'edit';
	const MODE_READONLY = 'readonly';

	/**
	 * Query param for admins / instructors to view a student's notes.
	 */
	const PARAM_NOTE_USER = 'lp_note_user';

	/**
	 * Cached render state for the current request.
	 *
	 * @var array|false
	 */
	protected $render_state = false;

	/**
	 * @var bool
	 */
	protected $render_state_resolved = false;

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( CourseItemLaunchersTemplate::HOOK, array( $this, 'render_launcher' ), 10 );
		add_action( 'wp_footer', array( $this, 'render_panel' ), 10 );
	}

	/**
	 * Enqueue assets.
	 * Data for JS is added in render_panel(): LP registers its script handles after this hook runs.
	 */
	public function enqueue_assets() {
		if ( ! $this->get_render_state() ) {
			return;
		}

		wp_enqueue_style( 'lp-course-notes' );
		wp_enqueue_script( 'lp-course-notes' );
	}

	/**
	 * Render state for the current request (cached).
	 *
	 * @return array|false
	 */
	public function get_render_state() {
		if ( ! $this->render_state_resolved ) {
			$this->render_state_resolved = true;

			try {
				$this->render_state = $this->resolve_render_state();
			} catch ( Throwable $e ) {
				$this->render_state = false;
			}
		}

		return $this->render_state;
	}

	/**
	 * Decide whether and how to render notes on the current page.
	 *
	 * @return array|false
	 * @throws Exception
	 */
	protected function resolve_render_state() {
		if ( ! NoteService::is_enabled() || ! is_user_logged_in() ) {
			return false;
		}

		if ( LP_PAGE_SINGLE_COURSE_CURRICULUM !== LP_Page_Controller::page_current() ) {
			return false;
		}

		$item = LP_Global::course_item();
		if ( ! $item || ! in_array( $item->get_item_type(), NoteModel::get_supported_item_types(), true ) ) {
			return false;
		}

		$service   = NoteService::instance();
		$viewer_id = get_current_user_id();
		$course_id = absint( $item->get_course_id() );
		$item_id   = absint( $item->get_id() );
		$item_type = (string) $item->get_item_type();
		$owner_id  = absint( LP_Request::get_param( self::PARAM_NOTE_USER, 0, 'int', 'get' ) );

		if ( $owner_id > 0 && $owner_id !== $viewer_id ) {
			// Admin / course instructor viewing a student's notes.
			if ( ! $service->can_view( $viewer_id, $owner_id, $course_id ) ) {
				return false;
			}

			$mode = self::MODE_READONLY;
		} else {
			if ( ! $service->can_create( $viewer_id, $course_id, $item_id, $item_type ) ) {
				return false;
			}

			$owner_id = $viewer_id;
			$mode     = self::MODE_EDIT;
		}

		$notes = $service->get_item_notes( $viewer_id, $owner_id, $course_id, $item_id );

		return array(
			'mode'      => $mode,
			'owner_id'  => $owner_id,
			'course_id' => $course_id,
			'item_id'   => $item_id,
			'item_type' => $item_type,
			'notes'     => array_map( array( $service, 'to_response' ), $notes ),
		);
	}

	/**
	 * Data for window.lpCourseNotes.
	 *
	 * @param array $render_state Render state.
	 *
	 * @return array
	 */
	protected function get_js_data( array $render_state ): array {
		return apply_filters(
			'learn-press/course-notes/js-data',
			array(
				'canEdit'         => self::MODE_EDIT === $render_state['mode'],
				'courseId'        => $render_state['course_id'],
				'itemId'          => $render_state['item_id'],
				'itemType'        => $render_state['item_type'],
				'notes'           => $render_state['notes'],
				'contentSelector' => apply_filters(
					'learn-press/course-notes/content-selector',
					'.content-item-description.lesson-description'
				),
				'i18n'            => array(
					'textNote'      => __( 'Text Note', 'learnpress' ),
					'highlight'     => __( 'Highlight', 'learnpress' ),
					'deleteConfirm' => __( 'Delete this note?', 'learnpress' ),
					'orphaned'      => __( 'The highlighted text was changed or removed from the lesson.', 'learnpress' ),
					'contentEmpty'  => __( 'Please enter your note.', 'learnpress' ),
					'error'         => __( 'An error occurred. Please try again.', 'learnpress' ),
				),
			),
			$render_state
		);
	}

	/**
	 * Render the launcher button in the shared footer launchers wrapper.
	 */
	public function render_launcher() {
		if ( ! $this->get_render_state() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->html_toggle();
	}

	/**
	 * Render the notes panel on wp_footer.
	 */
	public function render_panel() {
		$render_state = $this->get_render_state();
		if ( ! $render_state ) {
			return;
		}

		// Hex flags keep note/lesson text (e.g. "</script>") from breaking out of the inline script.
		$js_data = wp_json_encode(
			$this->get_js_data( $render_state ),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		wp_add_inline_script( 'lp-course-notes', 'window.lpCourseNotes = ' . $js_data . ';', 'before' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->html_widget( $render_state );
	}

	/**
	 * Launcher button.
	 *
	 * @return string
	 */
	public function html_toggle(): string {
		$section = apply_filters(
			'learn-press/course-notes/html-toggle',
			array(
				'wrapper'     => sprintf(
					'<button type="button" class="lp-notes__toggle" aria-label="%s" aria-expanded="false" aria-controls="lp-notes-panel">',
					esc_attr__( 'Open notes', 'learnpress' )
				),
				'icon'        => '<span class="lp-icon lp-icon-edit-square" aria-hidden="true"></span>',
				'label'       => sprintf( '<span class="lp-notes__toggle-label">%s</span>', esc_html__( 'Notes', 'learnpress' ) ),
				'count'       => '<span class="lp-notes__toggle-count" hidden></span>',
				'wrapper_end' => '</button>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Root widget: panel + floating "Add Note" selection button + card template.
	 *
	 * @param array $render_state Render state.
	 *
	 * @return string
	 */
	public function html_widget( array $render_state ): string {
		$is_edit = self::MODE_EDIT === $render_state['mode'];

		$section = apply_filters(
			'learn-press/course-notes/html-widget',
			array(
				'wrapper'       => sprintf(
					'<div id="lp-notes" class="lp-notes lp-notes--%s">',
					esc_attr( $render_state['mode'] )
				),
				'panel'         => $this->html_panel( $render_state ),
				'selection_btn' => $is_edit ? $this->html_selection_button() : '',
				'card_template' => $this->html_card_template( $is_edit ),
				'wrapper_end'   => '</div>',
			),
			$render_state
		);

		return Template::combine_components( $section );
	}

	/**
	 * Sidebar panel.
	 *
	 * @param array $render_state Render state.
	 *
	 * @return string
	 */
	public function html_panel( array $render_state ): string {
		$is_edit = self::MODE_EDIT === $render_state['mode'];

		$body = $is_edit
			? $this->html_help() . $this->html_add_button() . $this->html_form()
			: $this->html_readonly_notice( $render_state['owner_id'] );

		$section = apply_filters(
			'learn-press/course-notes/html-panel',
			array(
				'wrapper'     => '<aside id="lp-notes-panel" class="lp-notes__panel" role="dialog" aria-labelledby="lp-notes-title" hidden>',
				'header'      => $this->html_header(),
				'body'        => '<div class="lp-notes__body">',
				'content'     => $body,
				'list'        => $this->html_list( $is_edit ),
				'body_end'    => '</div>',
				'wrapper_end' => '</aside>',
			),
			$render_state
		);

		return Template::combine_components( $section );
	}

	/**
	 * Panel header.
	 *
	 * @return string
	 */
	public function html_header(): string {
		$section = apply_filters(
			'learn-press/course-notes/html-header',
			array(
				'wrapper'     => '<div class="lp-notes__header">',
				'title'       => sprintf(
					'<h2 id="lp-notes-title" class="lp-notes__title"><span class="lp-icon lp-icon-file-text-o" aria-hidden="true"></span>%s</h2>',
					esc_html__( 'Notes', 'learnpress' )
				),
				'close'       => sprintf(
					'<button type="button" class="lp-notes__close" aria-label="%1$s" title="%1$s"><span class="lp-icon lp-icon-close" aria-hidden="true"></span></button>',
					esc_attr__( 'Close notes', 'learnpress' )
				),
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Dismissible help box.
	 *
	 * @return string
	 */
	public function html_help(): string {
		$section = apply_filters(
			'learn-press/course-notes/html-help',
			array(
				'wrapper'     => '<div class="lp-notes__help">',
				'icon'        => '<span class="lp-icon lp-icon-info-circle" aria-hidden="true"></span>',
				'text'        => sprintf(
					'<div class="lp-notes__help-text"><p>%s</p><p class="lp-notes__help-hint">%s</p></div>',
					esc_html__( 'You can add text notes or highlight content and add notes to specific text.', 'learnpress' ),
					esc_html__( '* To create a highlight note: select text in the lesson content, then click the "Add Note" button that appears.', 'learnpress' )
				),
				'dismiss'     => sprintf(
					'<button type="button" class="lp-notes__help-dismiss" aria-label="%1$s" title="%1$s"><span class="lp-icon lp-icon-close" aria-hidden="true"></span></button>',
					esc_attr__( 'Hide tip', 'learnpress' )
				),
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Button to add a text note (no highlight).
	 *
	 * @return string
	 */
	public function html_add_button(): string {
		return sprintf(
			'<button type="button" class="lp-notes__add">%s</button>',
			esc_html__( 'Add Note', 'learnpress' )
		);
	}

	/**
	 * Note editor form, used for create and edit.
	 *
	 * @return string
	 */
	public function html_form(): string {
		$section = apply_filters(
			'learn-press/course-notes/html-form',
			array(
				'wrapper'     => '<form class="lp-notes__form" novalidate hidden>',
				'label'       => sprintf(
					'<label class="lp-notes__form-label" for="lp-notes-content">%s</label>',
					esc_html__( 'Note Content/Edit Note', 'learnpress' )
				),
				'quote'       => '<blockquote class="lp-notes__form-quote" hidden></blockquote>',
				'textarea'    => sprintf(
					'<textarea id="lp-notes-content" class="lp-notes__form-content" rows="4" maxlength="%1$d" placeholder="%2$s"></textarea>',
					NoteModel::CONTENT_MAX_LENGTH,
					esc_attr__( 'Enter your note here...', 'learnpress' )
				),
				'actions'     => sprintf(
					'<div class="lp-notes__form-actions"><button type="button" class="lp-notes__form-cancel">%1$s</button><button type="submit" class="lp-notes__form-save">%2$s</button></div>',
					esc_html__( 'Cancel', 'learnpress' ),
					esc_html__( 'Save Note', 'learnpress' )
				),
				'wrapper_end' => '</form>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Notice shown to admin / instructor viewing a student's notes.
	 *
	 * @param int $owner_id Student ID.
	 *
	 * @return string
	 */
	public function html_readonly_notice( int $owner_id ): string {
		$user = UserModel::find( $owner_id, true );
		$name = $user ? $user->get_display_name() : '#' . $owner_id;

		return sprintf(
			'<div class="lp-notes__readonly">%s</div>',
			sprintf(
				/* translators: %s: student name */
				esc_html__( 'You are viewing the notes of %s (read only).', 'learnpress' ),
				'<strong>' . esc_html( $name ) . '</strong>'
			)
		);
	}

	/**
	 * Notes list container, filled by JS.
	 *
	 * @param bool $is_edit Edit mode.
	 *
	 * @return string
	 */
	public function html_list( bool $is_edit ): string {
		$empty = $is_edit
			? __( 'No notes yet. Add your first note for this lesson.', 'learnpress' )
			: __( 'This student has no notes for this lesson.', 'learnpress' );

		return sprintf(
			'<div class="lp-notes__list" aria-live="polite"></div><p class="lp-notes__empty" hidden>%s</p>',
			esc_html( $empty )
		);
	}

	/**
	 * Floating "Add Note" button shown next to a text selection.
	 *
	 * @return string
	 */
	public function html_selection_button(): string {
		return sprintf(
			'<button type="button" class="lp-notes__selection-btn" hidden>%s</button>',
			esc_html__( 'Add Note', 'learnpress' )
		);
	}

	/**
	 * Card template cloned by JS for each note. Text values are set with textContent.
	 *
	 * @param bool $is_edit Edit mode.
	 *
	 * @return string
	 */
	public function html_card_template( bool $is_edit ): string {
		$actions = '';
		if ( $is_edit ) {
			$actions = sprintf(
				'<div class="lp-notes__card-actions"><button type="button" class="lp-notes__card-delete">%1$s</button><button type="button" class="lp-notes__card-edit">%2$s</button></div>',
				esc_html__( 'Delete', 'learnpress' ),
				esc_html__( 'Edit Note', 'learnpress' )
			);
		}

		$section = apply_filters(
			'learn-press/course-notes/html-card-template',
			array(
				'wrapper'     => '<template class="lp-notes__card-template"><article class="lp-notes__card" tabindex="-1">',
				'meta'        => '<div class="lp-notes__card-meta"><span class="lp-notes__card-type"></span><time class="lp-notes__card-date"></time></div>',
				'quote'       => '<blockquote class="lp-notes__card-quote" hidden></blockquote>',
				'orphaned'    => '<p class="lp-notes__card-orphaned" hidden></p>',
				'content'     => '<p class="lp-notes__card-content"></p>',
				'actions'     => $actions,
				'wrapper_end' => '</article></template>',
			),
			$is_edit
		);

		return Template::combine_components( $section );
	}
}
