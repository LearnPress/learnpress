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
use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserModel;

use LP_Course_Filter;
use Throwable;
use WP_Query;

class BuilderTabQuizTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/quizzes/layout', [ $this, 'html_tab_quizzes' ] );
	}

	public function html_tab_quizzes() {
		$list_quiz   = $this->tab_list_quizzes();
		$hmtl_search = $this->html_search();
		$btn         = CourseBuilderTemplate::instance()->html_btn_add_new();

		$tab = [
			'wrapper'            => '<div class="cb-tab-quiz">',
			'wrapper_action'     => '<div class="cb-tab-quiz__action">',
			'search_quiz'        => $hmtl_search,
			'btn'                => $btn,
			'wrapper_action_end' => '</div>',
			'quizzes'            => $list_quiz,
			'wrapper_end'        => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_search() {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'quizzes' );

		$search = [
			'wrapper'     => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_quiz' => '<button class="cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
			'input'       => sprintf( '<input class="cb-input-search-quiz" type="search" placeholder="%s" name="c_search" value="%s">', __( 'Search', 'learnpress' ), $args['c_search'] ?? '' ),
			'wrapper_end' => '</form>',
		];

		return Template::combine_components( $search );
	}

	public function tab_list_quizzes(): string {
		$content = '';

		try {
			// Query quizzes of user
			$param           = lp_archive_skeleton_get_args();
			$param['id_url'] = 'tab-list-quizzes';

			$query_args = array(
				'post_type'      => LP_QUIZ_CPT,
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

			$query         = new WP_Query();
			$result        = $query->query( $query_args );
			$total_quizzes = $query->found_posts ?? 0;

			if ( $total_quizzes < 1 ) {
				unset( $query_args['paged'] );
				$count_query = new WP_Query();
				$count_query->query( $query_args );
				$total_quizzes = $count_query->found_posts;
			}

			$quizzes = array();

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$quiz_model = QuizPostModel::find( get_the_ID(), true );
					$quizzes[]  = $quiz_model;
				}
			}

			if ( ! empty( $quizzes ) ) {
				$html_quizzes = $this->list_quizzes( $quizzes );
			} else {
				$html_quizzes = Template::print_message(
					sprintf( __( 'No quizzes found', 'learnpress' ) ),
					'info',
					false
				);
			}

			$sections = apply_filters(
				'learn-press/course-builder/quizzes/sections',
				[
					'wrapper'     => '<div class="courses-builder__quiz-tab learn-press-quizzes">',
					'quizzes'     => $html_quizzes,
					'pagination'  => $this->quizzes_pagination( $query_args['paged'] ?? 1, $query_args['posts_per_page'], $total_quizzes ),
					'wrapper_end' => '</div>',
				],
				$quizzes,
				$userModel
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Display list quizzes.
	 *
	 * @param $quizzes
	 *
	 * @return string
	 */
	public function list_quizzes( $quizzes ): string {
		$content = '';

		try {
			$html_list_quiz = '';
			foreach ( $quizzes as $quiz_model ) {
				$html_list_quiz .= self::render_quiz( $quiz_model );
			}

			$header = '<div class="cb-list-table-header">';
			$header .= sprintf( '<span>%s</span>', __( 'Quiz Title', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Assigned', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Duration', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Create Date', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Status', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Actions', 'learnpress' ) );
			$header .= '</div>';

			$sections = [
				'header'      => $header,
				'wrapper'     => '<ul class="cb-list-quiz">',
				'list_quiz'   => $html_list_quiz,
				'wrapper_end' => '</ul>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Render quiz in course builder
	 *
	 * @param $quiz
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function render_quiz( QuizPostModel $quiz_model, array $settings = [] ): string {
		$quiz = array(
			'id'            => $quiz_model->get_id(),
			'title'         => $quiz_model->post_title,
			'status'        => $quiz_model->post_status,
			'courses'       => BuilderEditQuizTemplate::instance()->get_assigned( $quiz_model->get_id() ),
			'author'        => get_user_by( 'ID', $quiz_model->post_author )->display_name,
			'duration'      => learn_press_get_post_translated_duration( $quiz_model->get_id(), esc_html__( 'Lifetime', 'learnpress' ) ),
			'date_modified' => lp_jwt_prepare_date_response( $quiz_model->post_date_gmt ),
		);

		try {
			$edit_link = BuilderTabQuizTemplate::instance()->get_link_edit( $quiz['id'] );

			$html_courses = '';
			$assigned     = '--';
			if ( ! empty( $quiz['courses'] ) ) {
				$courses = is_array( $quiz['courses'] ) && isset( $quiz['courses']['id'] )
					? array( $quiz['courses'] )
					: $quiz['courses'];

				$course_htmls = array();
				foreach ( $courses as $course ) {
					$course_id    = $course['id'] ?? 0;
					$course_title = $course['title'] ?? '';

					if ( $course_id && $course_title ) {
						$course_link    = BuilderTabCourseTemplate::instance()->get_link_edit( $course_id );
						$course_htmls[] = sprintf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url( $course_link ),
							esc_html( $course_title )
						);
					}
				}

				if ( ! empty( $course_htmls ) ) {
					$assigned = implode( ', ', $course_htmls );
				}
			}

			$html_courses = sprintf(
				'<div class="lesson-assigned-courses"><span class="label">%s:</span> %s</div>',
				__( 'Assigned', 'learnpress' ),
				$assigned
			);

			$status       = $quiz_model->post_status ?? '';
			$html_content = apply_filters(
				'learn-press/course-builder/list-quizzes/item/section/bottom',
				[
					'wrapper'           => '<div class="quiz-content">',
					'wrapper_left'      => '<div class="quiz-content__left">',
					'title'             => sprintf(
						'<h3 class="wap-quiz-title"><a href="%s">%s</a></h3>',
						esc_url( $edit_link ),
						esc_html( $quiz['title'] )
					),
					'quiz_status'       => ! empty( $status ) ? sprintf( '<span class="quiz-status %1$s">%1$s</span>', $status ) : '',
					'wrapper_left_end'  => '</div>',
					'wrapper_right'     => '<div class="quiz-content__right">',
					'courses'           => $html_courses,
					'duration'          => sprintf( '<span class="quiz__duration">%s</span>', $quiz['duration'] ),
					'date'              => sprintf( '<span class="quiz__date">%s</span>', date_i18n( 'm/d/Y', strtotime( $quiz['date_modified'] ?? '' ) ) ),
					'wrapper_right_end' => '</div>',
					'wrapper_end'       => '</div>',
				],
				$quiz,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-quizzes/item/action',
				[
					'wrapper'                     => '<div class="quiz-action">',
					'edit'                        => sprintf(
						'<div class="quiz-action-editor"><a class="btn-edit-quiz quiz-edit-permalink" href="%s">%s %s</a></div>',
						esc_url( $edit_link ),
						'<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
						__( 'Edit', 'learnpress' )
					),
					'action_expanded_button'      => '<div class="quiz-action-expanded"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg></div>',
					'action_expanded_wrapper'     => '<div style="display:none;" class="quiz-action-expanded__items">',
					'action_expanded_duplicate'   => sprintf( '<span class="quiz-action-expanded__duplicate">%s</span>', __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_publish'     => sprintf( '<span class="quiz-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf( '<span class="quiz-action-expanded__trash">%s</span>', __( 'Trash', 'learnpress' ) ),
					'action_expanded_delete'      => sprintf( '<span class="quiz-action-expanded__delete">%s</span>', __( 'Delete', 'learnpress' ) ),
					'action_expanded_wrapper_end' => '</div>',
					'wrapper_end'                 => '</div>',
				],
				$quiz,
				$settings
			);

			$section = apply_filters(
				'learn-press/course-builder/list-quizzes/item-li',
				[
					'wrapper_li'      => '<li class="quiz">',
					'wrapper_div'     => sprintf( '<div class="quiz-item" data-quiz-id="%s">', $quiz['id'] ),
					'quiz_info'       => Template::combine_components( $html_content ),
					'quiz_action'     => Template::combine_components( $html_action ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$quiz,
				$settings
			);

			$html_item = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}

	/**
	 * Pagination quizzes.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_quizzes
	 *
	 * @return string
	 */
	public function quizzes_pagination( int $page, int $limit, int $total_quizzes ): string {
		$content = '';

		try {
			$total_pages = \LP_Database::get_total_pages( $limit, $total_quizzes );
			$link_tab    = CourseBuilder::get_tab_link( 'quizzes' );
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

	public function get_link_edit( $quiz_id = 0 ) {
		if ( ! $quiz_id ) {
			return '';
		}

		$section  = CourseBuilder::get_current_section( '', 'quizzes' );
		$link_tab = CourseBuilder::get_tab_link( 'quizzes' );
		$link     = $link_tab . $quiz_id . '/' . $section;

		return $link;
	}
}
