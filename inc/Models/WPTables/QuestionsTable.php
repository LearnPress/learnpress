<?php

namespace LearnPress\Models\WPTables;

use LearnPress\Databases\QuizQuestionsDB;
use LearnPress\Filters\QuizQuestionsFilter;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
use LP_Abstract_Post_Type;
use Throwable;
use WP_Post;
use WP_Posts_List_Table;

defined( 'ABSPATH' ) || exit;

/**
 * LearnPress Questions Table class.
 *
 * @since 4.4.8
 * @version 1.0.0
 */
class QuestionsTable extends WP_Posts_List_Table {
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
				'instructor' => esc_html__( 'Author', 'learnpress' ),
				LP_QUIZ_CPT  => esc_html__( 'Quiz', 'learnpress' ),
				'type'       => esc_html__( 'Type', 'learnpress' ),
			);

			$columns = array_merge(
				array_slice( $columns, 0, $pos + 1 ),
				$insert,
				array_slice( $columns, $pos + 1 )
			);
		}

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
		$sortable_columns                = parent::get_sortable_columns();
		$sortable_columns['author']      = array( 'author', 'asc' );
		$sortable_columns[ LP_QUIZ_CPT ] = array( 'quiz-name', 'asc' );

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
	 * Column quiz.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_lp_quiz( $post ) {
		try {
			$filter                  = new QuizQuestionsFilter();
			$filter->question_id     = $post->ID;
			$filter->query_count     = false;
			$filter->run_query_count = false;
			$quiz_questions          = QuizQuestionsDB::getInstance()->get_quiz_questions( $filter );
			$quiz_items              = array();

			foreach ( $quiz_questions as $quiz_question ) {
				$quizPostModel = ( new QuizQuestionModel( $quiz_question ) )->get_quiz_post_model();
				if ( ! $quizPostModel ) {
					continue;
				}

				$quiz_items[] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( add_query_arg( array( 'filter_quiz' => $quizPostModel->ID ) ) ),
					esc_html( $quizPostModel->get_the_title() )
				);
			}

			if ( $quiz_items ) {
				echo implode( ', ', $quiz_items );
			} else {
				esc_html_e( 'Not assigned yet', 'learnpress' );
			}
		} catch ( Throwable $e ) {
			esc_html_e( 'Not assigned yet', 'learnpress' );
		}
	}

	/**
	 * Column type.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_type( $post ) {
		try {
			if ( empty( $post ) ) {
				return;
			}

			$questionPostModel = QuestionPostModel::find( $post->ID ?? 0, true );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				return;
			}

			$question_type_label = $questionPostModel->get_type_label();
			if ( empty( $question_type_label ) ) {
				$question_type_label = esc_html__( 'Not set', 'learnpress' );
			}

			echo esc_html( $question_type_label );

			//echo esc_html( learn_press_question_name_from_slug( get_post_meta( $post->ID, '_lp_type', true ) ) );
		} catch ( Throwable $e ) {
			Template::print_message(
				$e->getMessage(),
				Response::STATUS_ERROR
			);
		}
	}
}
