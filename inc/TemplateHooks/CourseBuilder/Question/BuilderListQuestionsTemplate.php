<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Question;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\PostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Quiz\BuilderQuizTemplate;
use LP_Question;
use LP_Question_CURD;
use Throwable;
use WP_Query;

class BuilderListQuestionsTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/list-questions/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		$list_question = $this->tab_list_questions();

		$tab = [
			'wrapper'     => '<div class="cb-tab-question">',
			'header'      => $this->html_header(),
			'filter_bar'  => $this->html_filter_bar(),
			'questions'   => $list_question,
			'wrapper_end' => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_header(): string {
		$section = [
			'wrapper'     => '<div class="cb-tab-header">',
			'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', __( 'Questions', 'learnpress' ) ),
			'actions'     => sprintf(
				'<div class="cb-tab-header-actions" style="display:flex;align-items:center;gap:8px;">%s</div>',
				CourseBuilderTemplate::instance()->html_btn_add_new()
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_filter_bar(): string {
		$section = [
			'wrapper_action'     => '<div class="cb-tab-question__action">',
			'search_question'    => $this->html_search(),
			'wrapper_action_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_search() {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'questions' );

		$search = [
			'wrapper'         => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_question' => '<button class="lp-button cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
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

			if ( ! current_user_can( ADMIN_ROLE ) ) {
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
			wp_reset_postdata();

			if ( ! empty( $questions ) ) {
				$html_questions = $this->list_questions( $questions );
			} else {
				$html_questions = Template::print_message(
					sprintf( __( 'No questions found', 'learnpress' ) ),
					'info',
					false
				);
			}

			$total_pages     = \LP_Database::get_total_pages( $query_args['posts_per_page'], $total_questions );
			$link_tab        = CourseBuilder::get_tab_link( 'questions' );
			$data_pagination = [
				'paged'       => max( 1, $query_args['paged'] ?? 1 ),
				'total_pages' => $total_pages,
				'base'        => trailingslashit( $link_tab ) . 'page/%#%',
				'format'      => '',
			];

			$pagination = Template::instance()->html_pagination( $data_pagination );

			$sections = apply_filters(
				'learn-press/course-builder/questions/sections',
				[
					'wrapper'     => '<div class="courses-builder__question-tab learn-press-questions">',
					'questions'   => $html_questions,
					'wrapper_end' => '</div>',
					'pagination'  => $pagination,
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

			$header  = '<div class="cb-list-table-header">';
			$header .= sprintf( '<span>%s</span>', __( 'Question Title', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Quiz', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Create Date', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Status', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Preview', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Actions', 'learnpress' ) );
			$header .= '</div>';

			$sections = [
				'header'        => $header,
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
		static $edit_icon         = null;
		static $more_actions_icon = null;

		if ( null === $edit_icon ) {
			$edit_icon = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-edit.svg' );
		}

		if ( null === $more_actions_icon ) {
			$more_actions_icon = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-more.svg' );
		}

		$types         = LP_Question::get_types();
		$type          = get_post_meta( $question_model->get_id(), '_lp_type', true );
		$question_type = $types[ $type ] ?? '';
		$author        = get_user_by( 'ID', $question_model->post_author );
		$author_name   = $author && isset( $author->display_name ) ? $author->display_name : '--';

		$question = array(
			'id'            => $question_model->get_id(),
			'title'         => $question_model->post_title,
			'status'        => $question_model->post_status,
			'quizzes'       => BuilderEditQuestionTemplate::instance()->get_assigned_question( $question_model->get_id() ),
			'author'        => $author_name,
			'type'          => $question_type,
			'date_modified' => lp_jwt_prepare_date_response( $question_model->post_date_gmt ),
		);

		try {
			$edit_link = BuilderQuestionTemplate::instance()->get_link_edit( $question['id'] );

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
						$quiz_link    = BuilderQuizTemplate::instance()->get_link_edit( $quiz_id );
						$quiz_htmls[] = sprintf(
							'<a href="%s">%s</a>',
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
					'title'           => sprintf(
						'<h3 class="wap-question-title"><a href="%s">%s</a></h3>',
						esc_url( $edit_link ),
						esc_html( $question['title'] )
					),
					'quizzes'         => $html_quizzes,
					'date'            => sprintf( '<span class="question__date">%s</span>', ! empty( $question['date_modified'] ) ? date_i18n( 'm/d/Y', strtotime( $question['date_modified'] ) ) : '--' ),
					'question_status' => ! empty( $status ) ? sprintf( '<span class="question-status %1$s">%1$s</span>', $status ) : '<span></span>',
					'type'            => sprintf( '<span class="question__preview">%s</span>', $question['type'] ),
				],
				$question,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-questions/item/action',
				[
					'wrapper'                     => '<div class="question-action">',
					'edit'                        => $status !== PostModel::STATUS_TRASH ? sprintf(
						'<div class="question-action-editor"><a class="lp-button btn-edit-question question-edit-permalink" href="%s">%s %s</a></div>',
						esc_url( $edit_link ),
						$edit_icon,
						__( 'Edit', 'learnpress' )
					) : '',
					'action_expanded_button'      => sprintf(
						'<button type="button" class="lp-button question-action-expanded">%s</button>',
						$more_actions_icon
					),
					'action_expanded_wrapper'     => '<div style="display:none;" class="question-action-expanded__items">',
					'action_expanded_duplicate'   => sprintf( '<span class="lp-button question-action-expanded__duplicate">%s</span>', __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_publish'     => sprintf( '<span class="lp-button question-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf(
						'<span class="lp-button question-action-expanded__trash"%s>%s</span>',
						$status === 'trash' ? ' style="display:none"' : '',
						__( 'Trash', 'learnpress' )
					),
					'action_expanded_restore'     => sprintf(
						'<span class="lp-button question-action-expanded__restore"%s>%s</span>',
						$status !== 'trash' ? ' style="display:none"' : '',
						__( 'Restore', 'learnpress' )
					),
					'action_expanded_delete'      => sprintf( '<span class="lp-button question-action-expanded__delete">%s</span>', __( 'Delete', 'learnpress' ) ),
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
					'wrapper_div'     => sprintf( '<div class="question-item" data-question-id="%s" data-status="%s">', $question['id'], $status ),
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
}
