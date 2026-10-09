<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Quiz;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\PostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LearnPress\TemplateHooks\Table\TableListTemplate;
use LP_WP_Filesystem;
use Throwable;
use WP_Query;

class BuilderListQuizzesTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/list-quizzes/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		$list_quiz = $this->tab_list_quizzes();

		$tab = [
			'wrapper'     => '<div class="cb-tab-quiz">',
			'header'      => $this->html_header(),
			'filter_bar'  => $this->html_filter_bar(),
			'quizzes'     => $list_quiz,
			'wrapper_end' => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_header(): string {
		$section = [
			'wrapper'     => '<div class="cb-tab-header">',
			'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', __( 'Quizzes', 'learnpress' ) ),
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
			'wrapper_action'     => '<div class="cb-tab-quiz__action">',
			'search_quiz'        => $this->html_search(),
			'wrapper_action_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_search() {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'quizzes' );

		$search = [
			'wrapper'     => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_quiz' => '<button class="lp-button cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
			'input'       => sprintf( '<input class="cb-input-search-quiz" type="search" placeholder="%s" name="c_search" value="%s">', __( 'Search', 'learnpress' ), esc_attr( $args['c_search'] ?? '' ) ),
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

			if ( ! current_user_can( ADMIN_ROLE ) ) {
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
			wp_reset_postdata();

			$html_quizzes = $this->list_quizzes( $quizzes );

			$total_pages     = \LP_Database::get_total_pages( $query_args['posts_per_page'], $total_quizzes );
			$link_tab        = CourseBuilder::get_tab_link( 'quizzes' );
			$data_pagination = [
				'paged'       => max( 1, $query_args['paged'] ?? 1 ),
				'total_pages' => $total_pages,
				'base'        => trailingslashit( $link_tab ) . 'page/%#%',
				'format'      => '',
			];

			$pagination = Template::instance()->html_pagination( $data_pagination );

			$sections = apply_filters(
				'learn-press/course-builder/quizzes/sections',
				[
					'wrapper'     => '<div class="courses-builder__quiz-tab learn-press-quizzes">',
					'quizzes'     => $html_quizzes,
					'wrapper_end' => '</div>',
					'pagination'  => $pagination,
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

			if ( empty( $html_list_quiz ) ) {
				$html_list_quiz = sprintf(
					'<tr class="cb-list-empty-row"><td colspan="7">%s</td></tr>',
					Template::print_message( __( 'No quizzes found', 'learnpress' ), 'info', false )
				);
			}

			$table_args = [
				'class_table' => 'cb-list-table cb-list-quiz',
				'header'      => [
					'title'           => [
						'class' => 'cb-col-title',
						'title' => __( 'Quiz Title', 'learnpress' ),
					],
					'courses'         => [
						'class' => 'cb-col-assigned',
						'title' => __( 'Courses', 'learnpress' ),
					],
					'total_questions' => [
						'class' => 'cb-col-questions',
						'title' => __( 'Questions', 'learnpress' ),
					],
					'duration'        => [
						'class' => 'cb-col-duration',
						'title' => __( 'Duration', 'learnpress' ),
					],
					'date'            => [
						'class' => 'cb-col-date',
						'title' => __( 'Create Date', 'learnpress' ),
					],
					'status'          => [
						'class' => 'cb-col-status',
						'title' => __( 'Status', 'learnpress' ),
					],
					'actions'         => [
						'class' => 'cb-col-actions',
						'title' => __( 'Actions', 'learnpress' ),
					],
				],
				'body'        => [
					'rows_html' => $html_list_quiz,
				],
			];

			$content = TableListTemplate::instance()->html_table( $table_args );
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
		$edit_icon         = LP_WP_Filesystem::get_icon_svg( 'ico-cb-edit.svg' );
		$more_actions_icon = LP_WP_Filesystem::get_icon_svg( 'ico-cb-more.svg' );

		$author      = get_user_by( 'ID', $quiz_model->post_author );
		$author_name = $author && isset( $author->display_name ) ? $author->display_name : '--';

		$quiz = array(
			'id'            => $quiz_model->get_id(),
			'title'         => $quiz_model->post_title,
			'status'        => $quiz_model->post_status,
			'courses'       => BuilderEditQuizTemplate::instance()->get_assigned( $quiz_model->get_id() ),
			'author'        => $author_name,
			'duration'      => learn_press_get_post_translated_duration( $quiz_model->get_id(), esc_html__( 'Lifetime', 'learnpress' ) ),
			'date_modified' => lp_jwt_prepare_date_response( $quiz_model->post_date_gmt ),
		);

		try {
			$edit_link = BuilderQuizTemplate::instance()->get_link_edit( $quiz['id'] );

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
						$course_link    = BuilderCourseTemplate::instance()->get_link_edit( $course_id );
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
					'title'           => sprintf(
						'<h3 class="wap-quiz-title"><a href="%s">%s</a></h3>',
						esc_url( $edit_link ),
						esc_html( $quiz['title'] )
					),
					'courses'         => $html_courses,
					'total_questions' => sprintf( '<span class="quiz__total-questions">%d</span>', $quiz_model->count_questions() ),
					'duration'        => sprintf( '<span class="quiz__duration">%s</span>', $quiz['duration'] ),
					'date'            => sprintf( '<span class="quiz__date">%s</span>', ! empty( $quiz['date_modified'] ) ? date_i18n( 'm/d/Y', strtotime( $quiz['date_modified'] ) ) : '--' ),
					'quiz_status'     => ! empty( $status ) ? sprintf( '<span class="quiz-status %1$s">%1$s</span>', $status ) : '<span></span>',
				],
				$quiz,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-quizzes/item/action',
				[
					'wrapper'                     => '<div class="quiz-action">',
					'edit'                        => $status !== PostModel::STATUS_TRASH ? sprintf(
						'<div class="quiz-action-editor"><a class="lp-button btn-edit-quiz quiz-edit-permalink" href="%s">%s %s</a></div>',
						esc_url( $edit_link ),
						$edit_icon,
						__( 'Edit', 'learnpress' )
					) : '',
					'action_expanded_button'      => sprintf(
						'<button type="button" class="lp-button quiz-action-expanded">%s</button>',
						$more_actions_icon
					),
					'action_expanded_wrapper'     => '<div style="display:none;" class="quiz-action-expanded__items">',
					'action_expanded_duplicate'   => sprintf( '<span class="lp-button quiz-action-expanded__duplicate" data-title="%s" data-content="%s">%s</span>', __( 'Are you sure?', 'learnpress' ), __( 'Are you sure you want to duplicate this quiz?', 'learnpress' ), __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_publish'     => sprintf( '<span class="lp-button quiz-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf(
						'<span class="lp-button quiz-action-expanded__trash"%s>%s</span>',
						$status === 'trash' ? ' style="display:none"' : '',
						__( 'Trash', 'learnpress' )
					),
					'action_expanded_restore'     => sprintf(
						'<span class="lp-button quiz-action-expanded__restore"%s>%s</span>',
						$status !== 'trash' ? ' style="display:none"' : '',
						__( 'Restore', 'learnpress' )
					),
					'action_expanded_delete'      => sprintf( '<span class="lp-button quiz-action-expanded__delete">%s</span>', __( 'Delete', 'learnpress' ) ),
					'action_expanded_wrapper_end' => '</div>',
					'wrapper_end'                 => '</div>',
				],
				$quiz,
				$settings
			);

			$cell_classes = [
				'courses'         => 'assigned',
				'total_questions' => 'questions',
				'quiz_status'     => 'status',
			];
			$html_cells   = [];
			foreach ( $html_content as $key => $html ) {
				$cell_class         = $cell_classes[ $key ] ?? $key;
				$html_cells[ $key ] = sprintf(
					'<td class="cb-col-%s">%s</td>',
					esc_attr( sanitize_html_class( $cell_class ) ),
					$html
				);
			}

			$section = apply_filters(
				'learn-press/course-builder/list-quizzes/item-li',
				[
					'wrapper_li'      => sprintf( '<tr class="quiz quiz-item" data-quiz-id="%s" data-status="%s">', $quiz['id'], $status ),
					'wrapper_div'     => '',
					'quiz_info'       => Template::combine_components( $html_cells ),
					'quiz_action'     => sprintf( '<td class="cb-col-actions">%s</td>', Template::combine_components( $html_action ) ),
					'wrapper_div_end' => '',
					'wrapper_li_end'  => '</tr>',
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
}
