<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\NoteDB;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\CoursePostFilter;
use LearnPress\Filters\NoteFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Note\NoteModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LP_Debug;
use LP_Settings;

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
		// Notes have no meaning without their student, course or lesson.
		add_action( 'deleted_user', array( $this, 'on_deleted_user' ) );
		add_action( 'deleted_post', array( $this, 'on_deleted_post' ), 10, 2 );
	}

	/**
	 * Delete the notes of a deleted user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function on_deleted_user( $user_id ) {
		$this->delete_notes_by( NoteFilter::COL_USER_ID, absint( $user_id ) );
	}

	/**
	 * Delete the notes of a permanently deleted course or lesson (not when trashed).
	 *
	 * @param int           $post_id Post ID.
	 * @param \WP_Post|null $post    Post object.
	 *
	 * @return void
	 */
	public function on_deleted_post( $post_id, $post = null ) {
		$post_type = $post->post_type ?? get_post_type( $post_id );

		if ( LP_COURSE_CPT === $post_type ) {
			$this->delete_notes_by( NoteFilter::COL_COURSE_ID, absint( $post_id ) );
		} elseif ( in_array( $post_type, NoteModel::get_supported_item_types(), true ) ) {
			$this->delete_notes_by( NoteFilter::COL_ITEM_ID, absint( $post_id ) );
		}
	}

	/**
	 * @param string $column note_id | user_id | course_id | item_id.
	 * @param int    $id     ID.
	 *
	 * @return int Deleted rows.
	 */
	protected function delete_notes_by( string $column, int $id ): int {
		if ( $id <= 0 ) {
			return 0;
		}

		try {
			return NoteDB::getInstance()->delete_notes_by( $column, array( $id ) );
		} catch ( Exception $e ) {
			LP_Debug::error_log( $e );

			return 0;
		}
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

			return true;
		} catch ( Exception $e ) {
			return false;
		}
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

			$this->check_can_create( $user_id, $note->course_id, $note->item_id, $note->item_type );
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
	 * Courses whose notes a user can review in the backend.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int[]|null Null = all courses (admin); array (maybe empty) = only these courses.
	 * @throws Exception
	 */
	public function get_viewable_course_ids( int $user_id ) {
		if ( $user_id <= 0 ) {
			return array();
		}

		if ( $this->is_admin( $user_id ) ) {
			return null;
		}

		$course_ids = $this->find_authored_course_ids( $user_id );

		/**
		 * Add courses the user co-instructs (e.g. Co-Instructor add-on).
		 */
		$course_ids = apply_filters( 'learn-press/note/viewable-course-ids', $course_ids, $user_id );

		return array_values( array_unique( array_filter( array_map( 'absint', (array) $course_ids ) ) ) );
	}

	/**
	 * Notes for the admin Student Notes page, restricted to the courses the viewer can review.
	 *
	 * @param int   $viewer_id Viewer user ID.
	 * @param array $args      [ student, course, note_type, s, paged ].
	 * @param int   $per_page  Rows per page.
	 *
	 * @return array|false False when the viewer can review no course, else
	 *                     [ rows, total_rows, stats, users, courses ] (users/courses: filter options in scope).
	 * @throws Exception
	 */
	public function get_admin_list( int $viewer_id, array $args, int $per_page ) {
		$scope = $this->get_scope_filter( $viewer_id );
		if ( ! $scope ) {
			return false;
		}

		$db = NoteDB::getInstance();

		$filter        = $this->apply_list_args( clone $scope, $args );
		$filter->limit = $per_page;
		$filter->page  = max( 1, (int) ( $args['paged'] ?? 1 ) );
		$db->order_newest_first( $filter );
		$total_rows = 0;
		$rows       = $db->get_notes( $filter, $total_rows );

		return array(
			'rows'       => is_array( $rows ) ? $rows : array(),
			'total_rows' => $total_rows,
			'stats'      => $db->get_stats( $this->apply_list_args( clone $scope, $args ) ),
			'users'      => $db->get_note_users( clone $scope ),
			'courses'    => $db->get_note_courses( clone $scope ),
		);
	}

	/**
	 * Base filter restricted to the courses a viewer can review.
	 *
	 * @param int $viewer_id Viewer user ID.
	 *
	 * @return NoteFilter|false False when the viewer can review no course.
	 */
	protected function get_scope_filter( int $viewer_id ) {
		$filter     = new NoteFilter();
		$course_ids = $this->get_viewable_course_ids( $viewer_id );

		if ( is_array( $course_ids ) ) {
			if ( empty( $course_ids ) ) {
				return false;
			}

			$filter->course_ids = $course_ids;
		}

		return $filter;
	}

	/**
	 * Apply admin list args to a scope filter.
	 *
	 * @param NoteFilter $filter Scope filter.
	 * @param array      $args   [ student, course, note_type, s ].
	 *
	 * @return NoteFilter
	 */
	protected function apply_list_args( NoteFilter $filter, array $args ): NoteFilter {
		$filter->join_details = true;
		$course_id            = absint( $args['course'] ?? 0 );

		if ( $course_id ) {
			// Instructors can only narrow down to their own courses.
			if ( ! empty( $filter->course_ids ) && ! in_array( $course_id, $filter->course_ids, true ) ) {
				$filter->course_ids = array( 0 );
			} else {
				$filter->course_id = $course_id;
			}
		}

		if ( ! empty( $args['student'] ) ) {
			$filter->user_id = absint( $args['student'] );
		}

		if ( ! empty( $args['note_type'] ) ) {
			$filter->note_type = (string) $args['note_type'];
		}

		if ( '' !== ( $args['s'] ?? '' ) ) {
			$filter->key_word = (string) $args['s'];
		}

		return $filter;
	}

	/**
	 * Note data sent to the frontend (AJAX responses and the initial page data).
	 * Values are raw plain text: JS must render them with textContent.
	 *
	 * @param NoteModel $note Note.
	 *
	 * @return array
	 */
	public function to_response( NoteModel $note ): array {
		$data = $note->to_array();
		unset( $data['user_id'] );

		$timestamp                  = $note->get_created_timestamp();
		$data['created_at_display'] = $timestamp
			? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp )
			: '';

		return $data;
	}

	/**
	 * Check student notes are enabled in settings.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return 'yes' === LP_Settings::get_option( 'enable_student_notes', 'yes' );
	}

	/**
	 * Throw the reason a user cannot create notes on an item.
	 * The filter `learn-press/note/can-create` has the final say (e.g. allow preview items).
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
		$error = $this->get_create_error( $user_id, $course_id, $item_id, $item_type );
		$can   = apply_filters( 'learn-press/note/can-create', '' === $error, $user_id, $course_id, $item_id, $item_type );

		if ( ! $can ) {
			throw new Exception( '' !== $error ? $error : __( 'You do not have permission to add notes to this item.', 'learnpress' ) );
		}
	}

	/**
	 * Why a user cannot create notes on an item.
	 *
	 * @param int    $user_id   User ID.
	 * @param int    $course_id Course ID.
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Item post type.
	 *
	 * @return string Empty when allowed.
	 */
	protected function get_create_error( int $user_id, int $course_id, int $item_id, string $item_type ): string {
		if ( $user_id <= 0 ) {
			return __( 'Please log in to add notes.', 'learnpress' );
		}

		if ( ! in_array( $item_type, NoteModel::get_supported_item_types(), true ) ) {
			return __( 'Notes are not supported for this item.', 'learnpress' );
		}

		$course = $this->find_course( $course_id );
		if ( ! $course ) {
			return __( 'Course is invalid!', 'learnpress' );
		}

		if ( ! $course->get_item_model( $item_id, $item_type ) ) {
			return __( 'This item does not belong to the course.', 'learnpress' );
		}

		$user_course = $this->find_user_course( $user_id, $course_id );
		if ( ! $user_course || ! $user_course->has_enrolled_or_finished() ) {
			return __( 'You must enroll in this course to add notes.', 'learnpress' );
		}

		return '';
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
	 * IDs of the courses a user is the author of (any status).
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int[]
	 * @throws Exception
	 */
	protected function find_authored_course_ids( int $user_id ): array {
		$filter                  = new CoursePostFilter();
		$filter->post_author     = $user_id;
		$filter->only_fields     = array( 'p.ID' );
		$filter->limit           = -1;
		$filter->run_query_count = false;
		$rows                    = PostDB::getInstance()->get_posts( $filter );

		return is_array( $rows ) ? array_map( 'absint', PostDB::get_values_by_key( $rows ) ) : array();
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
