<?php
/**
 * Template hook: Student Notes on the learning page (lesson only).
 *
 * - Notes item registered in the shared learning sidebar.
 * - Sidebar content is a template; wp_footer renders the selection button and JS data.
 *
 * The current user manages only their own notes.
 *
 * @since 4.4.9.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Services\NoteService;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Global;
use LP_Page_Controller;
use Throwable;
use stdClass;

defined( 'ABSPATH' ) || exit;

class CourseNoteTemplate {
	use Singleton;

	const MODE_EDIT = 'edit';

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
		add_filter( 'learn-press/learning-bar/items', array( $this, 'register_learning_bar_item' ) );
		add_action( 'wp_footer', array( $this, 'render_runtime' ), 10 );
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'register_ajax_callback' ) );
	}

	public function register_ajax_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_course_notes';
		return $callbacks;
	}

	/**
	 * Enqueue assets.
	 * Data for JS is added in render_runtime(): LP registers its script handles after this hook runs.
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

		$can_create = $service->can_create( $viewer_id, $course_id, $item_id, $item_type );
		if ( ! $can_create ) {
			return false;
		}

		$owner_id = $viewer_id;
		$mode     = self::MODE_EDIT;

		$notes = $service->get_lesson_notes( $viewer_id, $course_id, $item_id, $item_type );

		return array(
			'mode'       => $mode,
			'can_create' => $can_create,
			'owner_id'   => $owner_id,
			'course_id'  => $course_id,
			'item_id'    => $item_id,
			'item_type'  => $item_type,
			'notes'      => $notes,
		);
	}

	/**
	 * Check the viewer can add new notes in this render state.
	 *
	 * @param array $render_state Render state.
	 *
	 * @return bool
	 */
	protected function can_create_notes( array $render_state ): bool {
		return ! empty( $render_state['can_create'] );
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
				'canCreate'       => $this->can_create_notes( $render_state ),
				'courseId'        => $render_state['course_id'],
				'itemId'          => $render_state['item_id'],
				'itemType'        => $render_state['item_type'],
				'notes'           => $render_state['notes'],
				'contentSelector' => apply_filters(
					'learn-press/course-notes/content-selector',
					'.content-item-description.lesson-description'
				),
				'i18n'            => array(
					'deleteConfirm' => __( 'Delete this note?', 'learnpress' ),
					'orphaned'      => __( 'The highlighted text was changed or removed from the lesson.', 'learnpress' ),
					'contentEmpty'  => __( 'Please enter your note.', 'learnpress' ),
					'error'         => __( 'An error occurred. Please try again.', 'learnpress' ),
					'courseNotes'   => __( 'All course notes', 'learnpress' ),
				),
			),
			$render_state
		);
	}

	/**
	 * Add Notes to the shared learning sidebar when the viewer has access.
	 */
	public function register_learning_bar_item( array $items ): array {
		$render_state = $this->get_render_state();
		if ( $render_state ) {
			$items['notes'] = array(
				'label' => __( 'Notes', 'learnpress' ),
				'icon'  => 'lp-icon-edit-square',
				'html'  => $this->html_learning_bar_template( $render_state ),
			);
		}

		return $items;
	}

	/**
	 * Render the persistent selection button and runtime data on wp_footer.
	 */
	public function render_runtime() {
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
	 * Persistent widget for the floating "Add Note" selection button.
	 *
	 * @param array $render_state Render state.
	 *
	 * @return string
	 */
	public function html_widget( array $render_state ): string {
		$can_create = $this->can_create_notes( $render_state );

		$section = apply_filters(
			'learn-press/course-notes/html-widget',
			array(
				'wrapper'       => sprintf(
					'<div id="lp-notes" class="lp-notes lp-notes--%s">',
					esc_attr( $render_state['mode'] )
				),
				'selection_btn' => $can_create ? $this->html_selection_button() : '',
				'all_notes'     => '<template class="lp-notes__all-template"><div class="lp-notes__all">' . TemplateAJAX::load_content_via_ajax(
					array(
						'course_id'             => $render_state['course_id'],
						'paged'                 => 1,
						'enableUpdateParamsUrl' => false,
						'enableScrollToView'    => false,
					),
					array(
						'class' => self::class,
						'method' => 'render_course_notes',
					)
				) . '</div></template>',
				'popup_form'    => '<template class="lp-notes__popup-form-template">' . $this->html_form() . '</template>',
				'wrapper_end'   => '</div>',
			),
			$render_state
		);

		return Template::combine_components( $section );
	}

	/**
	 * Notes template for the shared learning content bar.
	 *
	 * @param array $render_state Render state.
	 *
	 * @return string
	 */
	public function html_learning_bar_template( array $render_state ): string {
		$is_edit    = self::MODE_EDIT === $render_state['mode'];
		$can_create = $this->can_create_notes( $render_state );

		$body = $can_create
			? $this->html_help() . $this->html_add_button() . $this->html_form()
			: $this->html_form();

		$section = apply_filters(
			'learn-press/course-notes/html-panel',
			array(
				'wrapper'     => '<template id="lp-notes-template">',
				'header'      => $this->html_header(),
				'body'        => '<div id="lp-notes-content" class="lp-learning-bar-item-content lp-notes__body">',
				'content'     => $body,
				'list'        => $this->html_list(),
				'cards'       => $this->html_card_template( $is_edit ),
				'body_end'    => '</div>',
				'wrapper_end' => '</template>',
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
				'wrapper'     => '<div class="lp-learning-bar-item-head lp-notes__header">',
				'title'       => sprintf(
					'<span class="lp-notes__heading"><span class="lp-icon lp-icon-edit-square" aria-hidden="true"></span><span class="lp-learning-bar-item-title">%s</span></span>',
					esc_html__( 'Notes', 'learnpress' )
				),
				'view_all'    => sprintf(
					'<a href="#" class="lp-notes__view-all" aria-haspopup="dialog">%s</a>',
					esc_html__( 'View all', 'learnpress' )
				),
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Render a page using the shared TemplateAJAX loader and pagination.
	 * Permissions are checked here because the generic loader also serves guests.
	 */
	public static function render_course_notes( array $args ): stdClass {
		$response = new stdClass();
		try {
			if ( ! is_user_logged_in() || ! NoteService::is_enabled() ) {
				throw new Exception( __( 'Notes are unavailable.', 'learnpress' ) );
			}
			$service   = NoteService::instance();
			$viewer_id = get_current_user_id();
			$course_id = absint( $args['course_id'] ?? 0 );
			// Frontend notes always belong to the signed-in user, regardless of role.
			$owner_id          = $viewer_id;
			$data              = $service->get_course_notes( $viewer_id, $owner_id, $course_id, max( 1, (int) ( $args['paged'] ?? 1 ) ) );
			$cards             = self::html_course_note_groups( $data['notes'] );
			$response->content = $cards
				? '<div class="lp-notes__list">' . $cards . '</div>' . Template::instance()->html_pagination(
					array(
						'total_pages' => (int) ceil( $data['total_rows'] / $data['per_page'] ),
						'paged'       => $data['page'],
					)
				)
				: Template::print_message( __( 'No notes for this course yet.', 'learnpress' ), 'info', false );
		} catch ( Throwable $e ) {
			$response->content = Template::print_message( $e->getMessage(), 'error', false );
		}
		return $response;
	}

	/**
	 * Group cards by lesson, using native disclosure controls.
	 */
	public static function html_course_note_groups( array $notes ): string {
		$lessons = array();
		foreach ( $notes as $note ) {
			$lessons[ $note['item_id'] ][] = $note;
		}
		ksort( $lessons, SORT_NUMERIC );
		$html = '';
		foreach ( $lessons as $lesson_notes ) {
			$lesson = $lesson_notes[0];
			$title  = '' !== $lesson['item_title'] ? $lesson['item_title'] : '#' . $lesson['item_id'];
			$html  .= '<details class="lp-notes__lesson" open><summary class="lp-notes__lesson-title">' . esc_html( $title ) . '<span class="lp-icon-angle-down" aria-hidden="true"></span></summary><div class="lp-notes__lesson-cards">';
			foreach ( $lesson_notes as $note ) {
				$highlight = NoteModel::TYPE_HIGHLIGHT === $note['note_type'];
				$html     .= Template::combine_components(
					array(
						'wrapper' => '<article class="lp-notes__card" data-note-id="' . absint( $note['note_id'] ) . '" data-note-type="' . esc_attr( $note['note_type'] ) . '">',
						'header'  => '<div class="lp-notes__card-meta"><span class="lp-notes__card-date">' . esc_html( $note['created_at_display'] ) . '</span></div>',
						'quote'   => $highlight ? '<blockquote class="lp-notes__card-quote">' . esc_html( $note['highlight_text'] ) . '</blockquote>' : '',
						'content' => '<p class="lp-notes__card-content">' . esc_html( $note['content'] ) . '</p>',
						'actions' => ! empty( $note['can_manage'] ) ? self::html_card_actions() : '',
						'end'     => '</article>',
					)
				);
			}
			$html .= '</div></details>';
		}
		return $html;
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
				'wrapper'     => '<div class="learn-press-message info lp-notes__help">',
				'text'        => sprintf(
					'<div class="lp-notes__help-text"><p>%s</p><p class="lp-notes__help-hint">%s</p></div>',
					esc_html__( 'You can add text notes or highlight content and add notes to specific text.', 'learnpress' ),
					esc_html__( '* To create a highlight note: select text in the lesson content, then click the "Add Note" button that appears.', 'learnpress' )
				),
				'dismiss'     => sprintf(
					'<button type="button" class="lp-notes__help-dismiss lp-icon-close" aria-label="%1$s" title="%1$s"></button>',
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
			'<button type="button" class="lp-button lp-notes__add">%s</button>',
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
				'wrapper'     => '<form class="learn-press-form lp-notes__form" novalidate hidden>',
				'fields'      => '<div class="form-fields"><div class="form-field">',
				'quote'       => '<blockquote class="lp-notes__form-quote" hidden></blockquote>',
				'textarea'    => sprintf(
					'<textarea id="lp-notes-input" class="lp-notes__form-content" rows="4" maxlength="%1$d" placeholder="%2$s" aria-label="%3$s"></textarea>',
					NoteModel::CONTENT_MAX_LENGTH,
					esc_attr__( 'Enter your note here...', 'learnpress' ),
					esc_attr__( 'Note content', 'learnpress' )
				),
				'fields_end'  => '</div></div>',
				'actions'     => sprintf(
					'<div class="lp-notes__form-actions"><button type="button" class="lp-button lp-notes__form-cancel">%1$s</button><button type="submit" class="lp-button lp-notes__form-save">%2$s</button></div>',
					esc_html__( 'Cancel', 'learnpress' ),
					esc_html__( 'Save', 'learnpress' )
				),
				'wrapper_end' => '</form>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Notes list container, filled by JS.
	 *
	 * @return string
	 */
	public function html_list(): string {
		$empty = __( 'No notes yet. Add your first note for this lesson.', 'learnpress' );

		return sprintf(
			'<div id="lp-notes-list" class="lp-notes__list" aria-live="polite"></div><button type="button" class="lp-button lp-notes__show-more" aria-controls="lp-notes-list" hidden>%1$s</button><p class="lp-notes__empty" hidden>%2$s</p>',
			esc_html__( 'Show more', 'learnpress' ),
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
			'<button type="button" class="lp-button lp-notes__selection-btn" hidden>%s</button>',
			esc_html__( 'Add Note', 'learnpress' )
		);
	}

	/**
	 * Shared note actions for the sidebar and course popup.
	 */
	public static function html_card_actions(): string {
		return sprintf(
			'<div class="lp-notes__card-actions"><button type="button" class="lp-button lp-notes__card-delete" aria-label="%1$s" title="%1$s"><span class="lp-icon-trash-o" aria-hidden="true"></span></button><button type="button" class="lp-button lp-notes__card-edit" aria-label="%2$s" title="%2$s"><span class="lp-icon-edit" aria-hidden="true"></span></button></div>',
			esc_attr__( 'Delete', 'learnpress' ),
			esc_attr__( 'Edit', 'learnpress' )
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
		$actions = $is_edit ? self::html_card_actions() : '';

		$section = apply_filters(
			'learn-press/course-notes/html-card-template',
			array(
				'wrapper'     => '<template class="lp-notes__card-template"><article class="lp-notes__card" tabindex="-1">',
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
