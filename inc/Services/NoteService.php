<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

/**
 * Business rules and permissions for student notes.
 *
 * - Create: logged-in student enrolled in (or finished) the course, item assigned to the course.
 * - Edit/delete: the note owner, with the same enrollment check.
 * - View: the owner, admins (manage_options) and the course author/co-instructors.
 *
 * @since 4.4.9.2
 */
class NoteService {
	use Singleton;

	public function init() {
	}

	/**
	 * Check a user can create notes on an item of a course.
	 *
	 * @param int    $user_id   User ID.
	 * @param int    $course_id Course ID.
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Item post type.
	 *
	 * @return bool
	 */
	public function can_create( int $user_id, int $course_id, int $item_id, string $item_type = LP_LESSON_CPT ): bool {
		try {
			$this->check_can_create( $user_id, $course_id, $item_id, $item_type );
			$can = true;
		} catch ( Exception $e ) {
			$can = false;
		}

		return (bool) apply_filters( 'learn-press/note/can-create', $can, $user_id, $course_id, $item_id, $item_type );
	}

	/**
	 * Check a user can edit/delete a note.
	 *
	 * @param int       $user_id User ID.
	 * @param NoteModel $note    Note.
	 *
	 * @return bool
	 */
	public function can_manage( int $user_id, NoteModel $note ): bool {
		$can = $note->is_owner( $user_id )
			&& $this->can_create( $user_id, $note->course_id, $note->item_id, $note->item_type );

		return (bool) apply_filters( 'learn-press/note/can-manage', $can, $user_id, $note );
	}

	/**
	 * Check a user can view the notes of a student in a course.
	 *
	 * @param int $viewer_id Viewer user ID.
	 * @param int $owner_id  Note owner user ID.
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	public function can_view( int $viewer_id, int $owner_id, int $course_id ): bool {
		$can = false;

		if ( $viewer_id > 0 ) {
			if ( $viewer_id === $owner_id || $this->is_admin( $viewer_id ) ) {
				$can = true;
			} else {
				$can = $this->is_course_instructor( $viewer_id, $course_id );
			}
		}

		return (bool) apply_filters( 'learn-press/note/can-view', $can, $viewer_id, $owner_id, $course_id );
	}

	/**
	 * Check a user is the author or a co-instructor of a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	public function is_course_instructor( int $user_id, int $course_id ): bool {
		$course = $this->find_course( $course_id );
		$user   = $this->find_user( $user_id );
		if ( ! $course || ! $user ) {
			return false;
		}

		return $course->check_user_is_author( $user );
	}

	/**
	 * Get notes of a student on an item, checking the viewer permission.
	 *
	 * @param int $viewer_id Viewer user ID.
	 * @param int $owner_id  Note owner user ID.
	 * @param int $course_id Course ID.
	 * @param int $item_id   Item ID.
	 *
	 * @return NoteModel[]
	 * @throws Exception
	 */
	public function get_item_notes( int $viewer_id, int $owner_id, int $course_id, int $item_id ): array {
		if ( ! $this->can_view( $viewer_id, $owner_id, $course_id ) ) {
			throw new Exception( __( 'You do not have permission to view these notes.', 'learnpress' ) );
		}

		return NoteModel::get_user_item_notes( $owner_id, $course_id, $item_id );
	}

	/**
	 * Create or update a note of the user.
	 *
	 * On update only the note content can change; the item, type and highlight position are kept.
	 *
	 * @param int   $user_id User ID (current user).
	 * @param array $data    [ note_id, course_id, item_id, item_type, note_type, content, highlight_text, anchor ].
	 *
	 * @return NoteModel
	 * @throws Exception
	 */
	public function save_note( int $user_id, array $data ): NoteModel {
		$note_id = absint( $data['note_id'] ?? 0 );

		if ( $note_id > 0 ) {
			$note = $this->find_note( $note_id );
			if ( ! $note ) {
				throw new Exception( __( 'Note not found.', 'learnpress' ) );
			}

			if ( ! $this->can_manage( $user_id, $note ) ) {
				throw new Exception( __( 'You do not have permission to edit this note.', 'learnpress' ) );
			}

			$note->content = (string) ( $data['content'] ?? '' );
		} else {
			$note         = new NoteModel(
				array(
					'user_id'        => $user_id,
					'course_id'      => absint( $data['course_id'] ?? 0 ),
					'item_id'        => absint( $data['item_id'] ?? 0 ),
					'item_type'      => (string) ( $data['item_type'] ?? LP_LESSON_CPT ),
					'note_type'      => (string) ( $data['note_type'] ?? NoteModel::TYPE_TEXT ),
					'content'        => (string) ( $data['content'] ?? '' ),
					'highlight_text' => (string) ( $data['highlight_text'] ?? '' ),
				)
			);
			$note->anchor = $data['anchor'] ?? array();

			// Throws the specific reason first, then let the filter have the final say.
			$this->check_can_create( $user_id, $note->course_id, $note->item_id, $note->item_type );
			if ( ! apply_filters( 'learn-press/note/can-create', true, $user_id, $note->course_id, $note->item_id, $note->item_type ) ) {
				throw new Exception( __( 'You do not have permission to add notes to this lesson.', 'learnpress' ) );
			}
		}

		return $this->persist( $note );
	}

	/**
	 * Delete a note of the user.
	 *
	 * @param int $user_id User ID (current user).
	 * @param int $note_id Note ID.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function delete_note( int $user_id, int $note_id ): bool {
		$note = $this->find_note( $note_id );
		if ( ! $note ) {
			throw new Exception( __( 'Note not found.', 'learnpress' ) );
		}

		if ( ! $this->can_manage( $user_id, $note ) ) {
			throw new Exception( __( 'You do not have permission to delete this note.', 'learnpress' ) );
		}

		return $this->remove( $note );
	}

	/**
	 * Throw the reason a user cannot create notes on an item.
	 *
	 * @param int    $user_id   User ID.
	 * @param int    $course_id Course ID.
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Item post type.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function check_can_create( int $user_id, int $course_id, int $item_id, string $item_type ) {
		if ( $user_id <= 0 ) {
			throw new Exception( __( 'Please log in to add notes.', 'learnpress' ) );
		}

		if ( ! in_array( $item_type, NoteModel::get_supported_item_types(), true ) ) {
			throw new Exception( __( 'Notes are not supported for this item.', 'learnpress' ) );
		}

		$course = $this->find_course( $course_id );
		if ( ! $course ) {
			throw new Exception( __( 'Course is invalid!', 'learnpress' ) );
		}

		if ( ! $course->get_item_model( $item_id, $item_type ) ) {
			throw new Exception( __( 'Lesson is invalid!', 'learnpress' ) );
		}

		$user_course = $this->find_user_course( $user_id, $course_id );
		if ( ! $user_course || ! $user_course->has_enrolled_or_finished() ) {
			throw new Exception( __( 'You must enroll in this course to add notes.', 'learnpress' ) );
		}
	}

	/**
	 * @param int $course_id Course ID.
	 *
	 * @return CourseModel|false
	 */
	protected function find_course( int $course_id ) {
		return $course_id > 0 ? CourseModel::find( $course_id, true ) : false;
	}

	/**
	 * @param int $user_id User ID.
	 *
	 * @return UserModel|false
	 */
	protected function find_user( int $user_id ) {
		return $user_id > 0 ? UserModel::find( $user_id, true ) : false;
	}

	/**
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 *
	 * @return UserCourseModel|false
	 */
	protected function find_user_course( int $user_id, int $course_id ) {
		return UserCourseModel::find( $user_id, $course_id, true );
	}

	/**
	 * @param int $note_id Note ID.
	 *
	 * @return NoteModel|false
	 * @throws Exception
	 */
	protected function find_note( int $note_id ) {
		return NoteModel::find( $note_id );
	}

	/**
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	protected function is_admin( int $user_id ): bool {
		return user_can( $user_id, 'manage_options' );
	}

	/**
	 * @param NoteModel $note Note.
	 *
	 * @return NoteModel
	 * @throws Exception
	 */
	protected function persist( NoteModel $note ): NoteModel {
		return $note->save();
	}

	/**
	 * @param NoteModel $note Note.
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function remove( NoteModel $note ): bool {
		return $note->delete();
	}
}
