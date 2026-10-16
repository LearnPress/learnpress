<?php
/**
 * Private student notes tab in the LearnPress profile.
 *
 * @since 4.4.9.2
 */

namespace LearnPress\TemplateHooks\Profile;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Services\NoteService;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Page_Controller;
use LP_Profile;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit;

class ProfileNotesTemplate {
	use Singleton;

	public function init(): void {
		add_filter( 'learn-press/get-profile-tabs', array( $this, 'display_profile_tab' ), 10, 3 );
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_notes';
		return $callbacks;
	}

	/**
	 * Notes are private even when the rest of a student's profile is public.
	 */
	public function display_profile_tab( array $tabs, $user_of_profile, $user_current ): array {
		if ( ! self::can_view_profile_notes( $user_of_profile, $user_current ) ) {
			unset( $tabs['notes'] );
		}
		return $tabs;
	}

	protected static function can_view_profile_notes( $owner, $viewer ): bool {
		return NoteService::is_enabled()
			&& is_object( $owner ) && method_exists( $owner, 'get_id' )
			&& is_object( $viewer ) && method_exists( $viewer, 'get_id' )
			&& (int) $viewer->get_id() > 0
			&& (int) $owner->get_id() === (int) $viewer->get_id();
	}

	public function enqueue_assets(): void {
		if ( ! LP_Page_Controller::is_page_profile() ) {
			return;
		}
		$profile = LP_Profile::instance();
		if ( 'notes' === $profile->get_current_tab()
			&& self::can_view_profile_notes( $profile->get_user(), $profile->get_user_current() ) ) {
			wp_enqueue_style( 'lp-course-notes' );
		}
	}

	public static function tab_content(): string {
		$profile = LP_Profile::instance();
		if ( ! self::can_view_profile_notes( $profile->get_user(), $profile->get_user_current() ) ) {
			return '';
		}

		return '<div class="lp-notes__all lp-profile-notes">'
			. TemplateAJAX::load_content_via_ajax(
				array(
					'id_url'                => 'profile_notes',
					'paged'                 => 1,
					'enableUpdateParamsUrl' => false,
					'enableScrollToView'    => false,
				),
				array(
					'class' => self::class,
					'method' => 'render_notes',
				)
			) . '</div>';
	}

	/**
	 * Derive ownership from the session, never from AJAX arguments.
	 */
	public static function render_notes( array $args ): stdClass {
		$response = new stdClass();
		try {
			if ( ! is_user_logged_in() || ! NoteService::is_enabled() ) {
				throw new Exception( __( 'Notes are unavailable.', 'learnpress' ) );
			}
			$data              = NoteService::instance()->get_user_notes( get_current_user_id(), max( 1, (int) ( $args['paged'] ?? 1 ) ) );
			$response->content = $data['notes']
				? self::html_notes( $data['notes'] ) . Template::instance()->html_pagination(
					array(
						'total_pages' => (int) ceil( $data['total_rows'] / $data['per_page'] ),
						'paged'       => $data['page'],
					)
				)
				: Template::print_message( __( 'You have not added any notes yet.', 'learnpress' ), 'info', false );
		} catch ( Throwable $e ) {
			$response->content = Template::print_message( $e->getMessage(), 'error', false );
		}
		return $response;
	}

	public static function html_notes( array $notes ): string {
		$html = '<div class="lp-notes__list">';
		foreach ( $notes as $note ) {
			$highlight    = NoteModel::TYPE_HIGHLIGHT === $note['note_type'];
			$course_title = '' !== $note['course_title'] ? $note['course_title'] : '#' . $note['course_id'];
			$item_title   = '' !== $note['item_title'] ? $note['item_title'] : '#' . $note['item_id'];
			$course_html  = esc_html( $course_title );
			$item_html    = esc_html( $item_title );
			if ( ! empty( $note['course_url'] ) ) {
				$course_html = '<a href="' . esc_url( $note['course_url'] ) . '">' . $course_html . '</a>';
			}
			if ( ! empty( $note['item_url'] ) ) {
				$item_html = '<a href="' . esc_url( $note['item_url'] ) . '">' . $item_html . '</a>';
			}
			$html .= Template::combine_components(
				array(
					'wrapper' => '<article class="lp-notes__card">',
					'context' => '<div class="lp-profile-notes__context">' . $course_html . '<span aria-hidden="true"> / </span>' . $item_html . '</div>',
					'meta'    => '<div class="lp-notes__card-meta"><span class="lp-notes__card-date">' . esc_html( $note['created_at_display'] ) . '</span></div>',
					'quote'   => $highlight ? '<blockquote class="lp-notes__card-quote">' . esc_html( $note['highlight_text'] ) . '</blockquote>' : '',
					'content' => '<p class="lp-notes__card-content">' . esc_html( $note['content'] ) . '</p>',
					'end'     => '</article>',
				)
			);
		}
		return $html . '</div>';
	}
}
