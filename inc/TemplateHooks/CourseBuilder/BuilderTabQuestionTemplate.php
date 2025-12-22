<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\UserModel;
use LP_Question;
use LP_Question_CURD;
use Throwable;
use WP_Query;

class BuilderTabQuestionTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/questions/layout', [ $this, 'html_tab_questions' ] );
	}

	public function html_tab_questions() {
		$list_question = $this->tab_list_questions();
		$hmtl_search   = $this->html_search();
		$btn           = CourseBuilderTemplate::instance()->html_btn_add_new();

		$tab = [
			'wrapper'            => '<div class="cb-tab-question">',
			'wrapper_action'     => '<div class="cb-tab-question__action">',
			'search_question'    => $hmtl_search,
			'btn'                => $btn,
			'wrapper_action_end' => '</div>',
			'questions'          => $list_question,
			'wrapper_end'        => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_search() {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'questions' );

		$search = [
			'wrapper'         => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_question' => '<button class="cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
			'input'           => sprintf( '<input class="cb-input-search-question" type="search" placeholder="%s" name="c_search" value="%s">', __( 'Search', 'learnpress' ), $args['c_search'] ?? '' ),
			'wrapper_end'     => '</form>',
		];

		return Template::combine_components( $search );
	}

	public function tab_list_questions(): string {
		$content = '';

		try {
			// Query questions of user
			$param           = lp_archive_skeleton_get_args();
			$param['id_url'] = 'tab-list-questions';

			$query_args = array(
				'post_type'      => LP_QUESTION_CPT,
				'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'trash' ),
				'posts_per_page' => 12,
				'paged'          => $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1,
				's'              => ! empty( $param['c_search'] ) ? sanitize_text_field( $param['c_search'] ) : '',

			);

			$user_id   = get_current_user_id();
			$userModel = UserModel::find( $user_id, true );
			if ( ! $userModel instanceof UserModel ) {
				return '';
			}

			if ( $userModel->is_instructor() ) {
				$query_args['author'] = $user_id;
			}

			$query           = new WP_Query();
			$result          = $query->query( $query_args );
			$total_questions = $query->found_posts ?? 0;

			if ( $total_questions < 1 ) {
				unset( $query_args['paged'] );
				$count_query = new WP_Query();
				$count_query->query( $query_args );
				$total_questions = $count_query->found_posts;
			}

			$questions = array();

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$question_model = QuestionPostModel::find( get_the_ID(), true );
					$questions[]    = $question_model;
				}
			}

			if ( ! empty( $questions ) ) {
				$html_questions = $this->list_questions( $questions );
			} else {
				$html_questions = Template::print_message(
					sprintf( __( 'No questions found', 'learnpress' ) ),
					'info',
					false
				);
			}

			$sections = apply_filters(
				'learn-press/course-builder/questions/sections',
				[
					'wrapper'     => '<div class="courses-builder__question-tab learn-press-questions">',
					'questions'   => $html_questions,
					'pagination'  => $this->questions_pagination( $query_args['paged'] ?? 1, $query_args['posts_per_page'], $total_questions ),
					'wrapper_end' => '</div>',
				],
				$questions,
				$userModel
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Display list questions.
	 *
	 * @param $questions
	 *
	 * @return string
	 */
	public function list_questions( $questions ): string {
		$content = '';

		try {
			$html_list_question = '';
			foreach ( $questions as $question_model ) {
				$html_list_question .= self::render_question( $question_model );
			}

			$sections = [
				'wrapper'       => '<ul class="cb-list-question">',
				'list_question' => $html_list_question,
				'wrapper_end'   => '</ul>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Render question in course builder
	 *
	 * @param $question
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function render_question( QuestionPostModel $question_model, array $settings = [] ): string {
		$types         = LP_Question::get_types();
		$type          = get_post_meta( $question_model->get_id(), '_lp_type', true );
		$question_type = $types[ $type ] ?? '';

		$question = array(
			'id'            => $question_model->get_id(),
			'title'         => $question_model->post_title,
			'status'        => $question_model->post_status,
			'quizzes'       => BuilderEditQuestionTemplate::instance()->get_assigned_question( $question_model->get_id() ),
			'author'        => get_user_by( 'ID', $question_model->post_author )->display_name,
			'type'          => $question_type,
			'date_modified' => lp_jwt_prepare_date_response( $question_model->post_date_gmt ),
		);

		try {
			$edit_link = BuilderTabQuestionTemplate::instance()->get_link_edit( $question['id'] );

			$html_quizzes = '';
			$assigned     = '--';
			if ( ! empty( $question['quizzes'] ) ) {
				$quizzes = is_array( $question['quizzes'] ) && isset( $question['quizzes']['id'] )
				? array( $question['quizzes'] )
				: $question['quizzes'];

				$quiz_htmls = array();
				foreach ( $quizzes as $quiz ) {
					$quiz_id    = $quiz['id'] ?? 0;
					$quiz_title = $quiz['title'] ?? '';

					if ( $quiz_id && $quiz_title ) {
						$quiz_link    = BuilderTabQuizTemplate::instance()->get_link_edit( $quiz_id );
						$quiz_htmls[] = sprintf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url( $quiz_link ),
							esc_html( $quiz_title )
						);
					}
				}

				if ( ! empty( $quiz_htmls ) ) {
					$assigned = implode( ', ', $quiz_htmls );
				}
			}

			$html_quizzes = sprintf(
				'<div class="question-assigned-quizzes"><span class="label">%s:</span> %s</div>',
				__( 'Assigned', 'learnpress' ),
				$assigned
			);

			$status       = $question_model->post_status ?? '';
			$html_content = apply_filters(
				'learn-press/course-builder/list-questions/item/section/bottom',
				[
					'wrapper'           => '<div class="question-content">',
					'wrapper_left'      => '<div class="question-content__left">',
					'title'             => sprintf(
						'<h3 class="wap-question-title"><button data-popup-question="%s">%s</button></h3>',
						$question_model->get_id(),
						$question['title']
					),
					'question_status'   => ! empty( $status ) ? sprintf( '<span class="question-status %1$s">%1$s</span>', $status ) : '',
					'wrapper_left_end'  => '</div>',
					'wrapper_right'     => '<div class="question-content__right">',
					'quizzes'           => $html_quizzes,
					'type'              => sprintf( '<span class="question__preview">%s</span>', $question['type'] ),
					'date'              => sprintf( '<span class="question__date">%s</span>', date_i18n( 'm/d/Y', strtotime( $question['date_modified'] ) ) ),
					'wrapper_right_end' => '</div>',
					'wrapper_end'       => '</div>',
				],
				$question,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-questions/item/action',
				[
					'wrapper'                     => '<div class="question-action">',
					'edit'                        => sprintf(
						'<div class="question-action-editor"><button class="btn-edit-question question-edit-permalink" data-popup-question="%s">%s %s</button></div>',
						$question['id'],
						'<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
						__( 'Edit', 'learnpress' )
					),
					'action_expanded_button'      => '<div class="question-action-expanded"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg></div>',
					'action_expanded_wrapper'     => '<div style="display:none;" class="question-action-expanded__items">',
					'action_expanded_duplicate'   => sprintf( '<span class="question-action-expanded__duplicate">%s</span>', __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_publish'     => sprintf( '<span class="question-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf( '<span class="question-action-expanded__trash">%s</span>', __( 'Trash', 'learnpress' ) ),
					'action_expanded_delete'      => sprintf( '<span class="question-action-expanded__delete">%s</span>', __( 'Delete', 'learnpress' ) ),
					'action_expanded_wrapper_end' => '</div>',
					'wrapper_end'                 => '</div>',
				],
				$question,
				$settings
			);

			$section = apply_filters(
				'learn-press/course-builder/list-questions/item-li',
				[
					'wrapper_li'      => '<li class="question">',
					'wrapper_div'     => sprintf( '<div class="question-item" data-question-id="%s">', $question['id'] ),
					'question_info'   => Template::combine_components( $html_content ),
					'question_action' => Template::combine_components( $html_action ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$question,
				$settings
			);

			$html_item = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

			return $html_item;
	}

	/**
	 * Pagination questions.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_questions
	 *
	 * @return string
	 */
	public function questions_pagination( int $page, int $limit, int $total_questions ): string {
		$content = '';

		try {
			$total_pages = \LP_Database::get_total_pages( $limit, $total_questions );
			$link_tab    = CourseBuilder::get_tab_link( 'questions' );
			$base_url    = trailingslashit( $link_tab ) . 'page/%#%';

			$data_pagination = array(
				'total'    => $total_pages,
				'current'  => max( 1, $page ),
				'base'     => $base_url,
				'format'   => '',
				'per_page' => $limit,
			);

			ob_start();
			Template::instance()->get_frontend_template( 'shared/pagination.php', $data_pagination );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	public function get_link_edit( $question_id = 0 ) {
		if ( ! $question_id ) {
			return '';
		}

		$section  = CourseBuilder::get_current_section( '', 'questions' );
		$link_tab = CourseBuilder::get_tab_link( 'questions' );
		$link     = $link_tab . $question_id . '/' . $section;

		return $link;
	}
}
