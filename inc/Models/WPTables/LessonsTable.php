<?php

namespace LearnPress\Models\WPTables;

use Exception;
use LearnPress\Helpers\LPDateTime;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\LessonPostModel;
use LP_Abstract_Post_Type;
use Throwable;
use WP_Post;
use WP_Posts_List_Table;

defined( 'ABSPATH' ) || exit;

/**
 * Lessons Table class.
 * Display columns for lessons on WP Backend
 *
 * @since 4.4.8
 * @version 1.0.0
 */
class LessonsTable extends WP_Posts_List_Table {
	/**
	 * Get the table columns.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_columns() {
		$columns = parent::get_columns();

		$pos = array_search( 'title', array_keys( $columns ), true );
		if ( false !== $pos ) {
			$new_columns = array(
				'instructor'  => esc_html__( 'Author', 'learnpress' ),
				LP_COURSE_CPT => $this->get_course_column_title(),
			);

			$new_columns['duration'] = esc_html__( 'Duration', 'learnpress' );
			// Set key lp_preview instead of preview to not apply style float right CSS of system
			$new_columns['lp_preview'] = esc_html__( 'Preview', 'learnpress' );

			$columns = array_merge(
				array_slice( $columns, 0, $pos + 1 ),
				$new_columns,
				array_slice( $columns, $pos + 1 )
			);
		}

		unset( $columns['taxonomy-lesson-tag'] );
		unset( $columns['comments'] );

		if ( ! empty( $columns['author'] ) ) {
			unset( $columns['author'] );
		}

		$user = wp_get_current_user();
		if ( in_array( LP_TEACHER_ROLE, $user->roles, true ) ) {
			unset( $columns['instructor'] );
		}

		return $columns;
	}

	/**
	 * Set columns can be sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns                  = parent::get_sortable_columns();
		$sortable_columns['author']        = array( 'author', 'asc' );
		$sortable_columns[ LP_COURSE_CPT ] = array( 'course-name', 'asc' );

		return $sortable_columns;
	}

	/**
	 * Column instructor.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_instructor( $post ) {
		try {
			LP_Abstract_Post_Type::column_author( $post );
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column course.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_lp_course( $post ) {
		try {
			$courses = CourseSectionItemModel::get_courses_from_item_id( $post->ID, LP_LESSON_CPT );

			if ( $courses ) {
				foreach ( $courses as $course ) {
					$course_id = $course->section_course_id;
					echo '<div><a href="' . esc_url_raw( remove_query_arg( 'orderby', add_query_arg( array( 'course' => $course_id ) ) ) ) . '">' . get_the_title( $course_id ) . '</a>';
					echo '<div class="row-actions">';
					printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course_id ) ), __( 'Edit', 'learnpress' ) );
					echo '&nbsp;|&nbsp;';
					printf( '<a href="%s">%s</a>', get_the_permalink( $course_id ), __( 'View', 'learnpress' ) );

					if ( $this->get_filter_course_id() ) {
						echo '&nbsp;|&nbsp;';
						printf(
							'<a href="%s">%s</a>',
							esc_url_raw( remove_query_arg( array( 'course', 'orderby' ) ) ),
							__( 'Remove Filter', 'learnpress' )
						);
					}
					echo '</div></div>';
				}
			} else {
				_e( 'Not assigned yet', 'learnpress' );
			}
		} catch ( Throwable $e ) {

		}
	}

	/**
	 * Column preview.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_lp_preview( $post ) {
		try {
			$lessonPostModel = LessonPostModel::find( $post->ID, true );
			if ( ! $lessonPostModel ) {
				return;
			}

			$lesson_is_preview = 'yes' === get_post_meta( $post->ID, '_lp_preview', true );

			echo $lesson_is_preview
				? '<span class="lp-icon-eye"></span>'
				: '<span class="lp-icon-eye-slash"></span>';
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column duration.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_duration( $post ) {
		try {
			$lessonPostModel = LessonPostModel::find( $post->ID, true );
			if ( ! $lessonPostModel ) {
				return;
			}

			$duration_raw    = $lessonPostModel->get_duration();
			$duration_number = absint( $duration_raw );
			if ( $duration_number === 0 ) {
				_e( 'Unlimited', 'learnpress' );
			} else {
				$duration_arr    = explode( ' ', $duration_raw );
				$duration_number = floatval( $duration_arr[0] ?? 0 );
				$duration_type   = $duration_arr[1] ?? '';
				if ( empty( $duration_number ) ) {
					$duration_str = __( 'Lifetime', 'learnpress' );
				} else {
					$duration_str = LPDateTime::get_string_plural_duration( $duration_number, $duration_type );
				}

				echo $duration_str;
			}
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Get the course column title.
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function get_course_column_title(): string {
		$title     = esc_html__( 'Course', 'learnpress' );
		$course_id = $this->get_filter_course_id();

		if ( $course_id ) {
			$course = learn_press_get_course( $course_id );
			if ( $course ) {
				$count       = $course->count_items( LP_LESSON_CPT );
				$post_object = get_post_type_object( LP_LESSON_CPT );
				$title       = sprintf(
					/* translators: 1: count 2: item label */
					_n( 'Course (%1$d %2$s)', 'Course (%1$d %2$s)', $count, 'learnpress' ),
					$count,
					$count > 1 ? $post_object->label : $post_object->labels->singular_name
				);
			}
		}

		return $title;
	}

	/**
	 * Get the course id currently filtering by.
	 *
	 * @return int|false
	 */
	protected function get_filter_course_id() {
		$course_id = ! empty( $_REQUEST['course'] ) ? absint( $_REQUEST['course'] ) : false;

		if ( ! $course_id && learn_press_is_support_course_item_type( LP_LESSON_CPT ) ) {
			$course_id = false;
		}

		return $course_id;
	}
}
