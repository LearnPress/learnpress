<?php

namespace LearnPress\Models\WPTables;

use LearnPress\Helpers\Response;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\QuizPostModel;
use LP_Abstract_Post_Type;
use LP_Datetime;
use Throwable;
use WP_Post;
use WP_Posts_List_Table;

defined( 'ABSPATH' ) || exit;

/**
 * LearnPress Quizzes Table class.
 *
 * @since 4.4.8
 * @version 1.0.0
 */
class QuizzesTable extends WP_Posts_List_Table {
	/**
	 * Get the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = parent::get_columns();

		$pos = array_search( 'title', array_keys( $columns ), true );
		if ( false !== $pos ) {
			$insert = array(
				LP_COURSE_CPT     => esc_html__( 'Course', 'learnpress' ),
				'instructor'      => esc_html__( 'Author', 'learnpress' ),
				'num_of_question' => esc_html__( 'Questions', 'learnpress' ),
				'duration'        => esc_html__( 'Duration', 'learnpress' ),
			);

			$columns = array_merge(
				array_slice( $columns, 0, $pos + 1 ),
				$insert,
				array_slice( $columns, $pos + 1 )
			);
		}

		unset( $columns['taxonomy-lesson-tag'] );

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
		$sortable_columns                    = parent::get_sortable_columns();
		$sortable_columns['instructor']      = array( 'author', 'asc' );
		$sortable_columns[ LP_COURSE_CPT ]   = array( 'course-name', 'asc' );
		$sortable_columns['num_of_question'] = array( 'question-count', 'asc' );

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
			$this->render_courses_of_item( $post->ID );
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column number of questions.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_num_of_question( $post ) {
		try {
			$quizPostModel = QuizPostModel::find( $post->ID, true );
			if ( ! $quizPostModel ) {
				return;
			}

			$count = $quizPostModel->count_questions();

			printf(
				'<span class="lp-label-counter %s" title="%s">%s</span>',
				esc_attr( ! $count ? 'disabled' : '' ),
				$count ?
					sprintf( /* translators: %d: question count */ _n( '%d question', '%d questions', $count, 'learnpress' ), $count ) :
					__( 'This quiz has no questions', 'learnpress' ),
				esc_html( $count )
			);
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
			$quizPostModel = QuizPostModel::find( $post->ID, true );
			if ( ! $quizPostModel ) {
				return;
			}

			$duration_str  = $quizPostModel->get_duration();
			$duration_arr  = explode( ' ', $duration_str );
			$duration      = $duration_arr[0];
			$duration_type = $duration_arr[1];

			if ( $duration > 0 ) {
				$duration_str = LP_Datetime::get_string_plural_duration( $duration, $duration_type );
			} else {
				$duration_str = __( 'Unlimited', 'learnpress' );
			}

			echo esc_html( $duration_str );
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column preview.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_preview( $post ) {
		try {
			printf(
				'<input type="checkbox" class="learn-press-checkbox learn-press-toggle-item-preview" %s value="%s" data-nonce="%s" />',
				checked( get_post_meta( $post->ID, '_lp_preview', true ), 'yes', false ),
				esc_attr( $post->ID ),
				esc_attr( wp_create_nonce( 'learn-press-toggle-item-preview' ) )
			);
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Get the course id currently filtering by.
	 *
	 * @return int|false
	 */
	protected function get_filter_course_id() {
		$course_id = ! empty( $_REQUEST['course'] ) ? absint( $_REQUEST['course'] ) : false;

		if ( ! $course_id && learn_press_is_support_course_item_type( LP_QUIZ_CPT ) ) {
			$course_id = false;
		}

		return $course_id;
	}

	/**
	 * Render the courses an item is assigned to.
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	protected function render_courses_of_item( int $post_id ) {
		try {
			$courses = CourseSectionItemModel::get_courses_from_item_id( $post_id, LP_QUIZ_CPT );

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
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}
}
