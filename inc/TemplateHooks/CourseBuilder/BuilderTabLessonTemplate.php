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
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\UserModel;

use Throwable;
use WP_Query;

class BuilderTabLessonTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/lessons/layout', [ $this, 'html_tab_lessons' ] );
	}

	public function html_tab_lessons() {
		$list_lesson = $this->tab_list_lessons();
		$hmtl_search = $this->html_search();
		$btn         = CourseBuilderTemplate::instance()->html_btn_add_new();

		$tab = [
			'wrapper'            => '<div class="cb-tab-lesson">',
			'wrapper_action'     => '<div class="cb-tab-lesson__action">',
			'search_lesson'      => $hmtl_search,
			'btn'                => $btn,
			'wrapper_action_end' => '</div>',
			'lessons'            => $list_lesson,
			'wrapper_end'        => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_search() {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'lessons' );

		$search = [
			'wrapper'       => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_lesson' => '<button class="cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
			'input'         => sprintf( '<input class="cb-input-search-lesson" type="search" placeholder="%s" name="c_search" value="%s">', __( 'Search', 'learnpress' ), $args['c_search'] ?? '' ),
			'wrapper_end'   => '</form>',
		];

		return Template::combine_components( $search );
	}

	public function tab_list_lessons(): string {
		$content = '';

		try {
			// Query lessons of user
			$param           = lp_archive_skeleton_get_args();
			$param['id_url'] = 'tab-list-lessons';

			$query_args = array(
				'post_type'      => LP_LESSON_CPT,
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
			$total_lessons = $query->found_posts ?? 0;

			if ( $total_lessons < 1 ) {
				unset( $query_args['paged'] );
				$count_query = new WP_Query();
				$count_query->query( $query_args );
				$total_lessons = $count_query->found_posts;
			}

			$lessons = array();

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$lesson_model = LessonPostModel::find( get_the_ID(), true );
					$lessons[]    = $lesson_model;
				}
			}

			if ( ! empty( $lessons ) ) {
				$html_lessons = $this->list_lessons( $lessons );
			} else {
				$html_lessons = Template::print_message(
					sprintf( __( 'No lessons found', 'learnpress' ) ),
					'info',
					false
				);
			}

			$sections = apply_filters(
				'learn-press/course-builder/lessons/sections',
				[
					'wrapper'     => '<div class="courses-builder__lesson-tab learn-press-lessons">',
					'lessons'     => $html_lessons,
					'pagination'  => $this->lessons_pagination( $query_args['paged'] ?? 1, $query_args['posts_per_page'], $total_lessons ),
					'wrapper_end' => '</div>',
				],
				$lessons,
				$userModel
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Display list lessons.
	 *
	 * @param $lessons
	 *
	 * @return string
	 */
	public function list_lessons( $lessons ): string {
		$content = '';

		try {
			$html_list_lesson = '';
			foreach ( $lessons as $lesson_model ) {
				$html_list_lesson .= self::render_lesson( $lesson_model );
			}

			$sections = [
				'wrapper'     => '<ul class="cb-list-lesson">',
				'list_lesson' => $html_list_lesson,
				'wrapper_end' => '</ul>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Render lesson in course builder
	 *
	 * @param $lesson
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function render_lesson( LessonPostModel $lesson_model, array $settings = [] ): string {
		$lesson = array(
			'id'            => $lesson_model->get_id(),
			'title'         => $lesson_model->post_title,
			'status'        => $lesson_model->post_status,
			'courses'       => BuilderEditLessonTemplate::instance()->get_assigned( $lesson_model->get_id() ),
			'author'        => get_user_by( 'ID', $lesson_model->post_author )->display_name,
			'preview'       => get_post_meta( $lesson_model->get_id(), '_lp_preview', true ),
			'date_modified' => lp_jwt_prepare_date_response( $lesson_model->post_date_gmt ),
		);

		try {
			$edit_link = BuilderTabLessonTemplate::instance()->get_link_edit( $lesson['id'] );

			$html_courses = '';
			$assigned     = '--';
			if ( ! empty( $lesson['courses'] ) ) {
				$courses = is_array( $lesson['courses'] ) && isset( $lesson['courses']['id'] )
					? array( $lesson['courses'] )
					: $lesson['courses'];

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

			$status      = $lesson_model->post_status ?? '';
			$svg_preview = $lesson['preview'] ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
					<path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
					<path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
				</svg>' : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
					<path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
				</svg>';

			$html_content = apply_filters(
				'learn-press/course-builder/list-lessons/item/section/bottom',
				[
					'wrapper'           => '<div class="lesson-content">',
					'wrapper_left'      => '<div class="lesson-content__left">',
					'title'             => sprintf(
						'<h3 class="wap-lesson-title"><a href="%s">%s</a></h3>',
						$edit_link,
						$lesson['title']
					),
					'lesson_status'     => ! empty( $status ) ? sprintf( '<span class="lesson-status %1$s">%1$s</span>', $status ) : '',
					'wrapper_left_end'  => '</div>',
					'wrapper_right'     => '<div class="lesson-content__right">',
					'courses'           => $html_courses,
					'preview'           => sprintf( '<span class="lesson__preview">%s</span>', $svg_preview ),
					'date'              => sprintf( '<span class="lesson__date">%s</span>', date_i18n( 'm/d/Y', strtotime( $lesson['date_modified'] ) ) ),
					'wrapper_right_end' => '</div>',
					'wrapper_end'       => '</div>',
				],
				$lesson,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-lessons/item/action',
				[
					'wrapper'                     => '<div class="lesson-action">',
					'edit'                        => sprintf(
						'<div class="lesson-action-editor"><a class="btn-edit-lesson lesson-edit-permalink" href="%s">%s %s</a></div>',
						$edit_link,
						'<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
						__( 'Edit', 'learnpress' )
					),
					'action_expanded_button'      => '<div class="lesson-action-expanded"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg></div>',
					'action_expanded_wrapper'     => '<div style="display:none;" class="lesson-action-expanded__items">',
					'action_expanded_duplicate'   => sprintf( '<span class="lesson-action-expanded__duplicate">%s</span>', __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_publish'     => sprintf( '<span class="lesson-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf( '<span class="lesson-action-expanded__trash">%s</span>', __( 'Trash', 'learnpress' ) ),
					'action_expanded_delete'      => sprintf( '<span class="lesson-action-expanded__delete">%s</span>', __( 'Delete', 'learnpress' ) ),
					'action_expanded_wrapper_end' => '</div>',
					'wrapper_end'                 => '</div>',
				],
				$lesson,
				$settings
			);

			$section = apply_filters(
				'learn-press/course-builder/list-lessons/item-li',
				[
					'wrapper_li'      => '<li class="lesson">',
					'wrapper_div'     => sprintf( '<div class="lesson-item" data-lesson-id="%s">', $lesson['id'] ),
					'lesson_info'     => Template::combine_components( $html_content ),
					'lesson_action'   => Template::combine_components( $html_action ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$lesson,
				$settings
			);

			$html_item = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}

	/**
	 * Pagination lessons.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_lessons
	 *
	 * @return string
	 */
	public function lessons_pagination( int $page, int $limit, int $total_lessons ): string {
		$content = '';

		try {
			$total_pages = \LP_Database::get_total_pages( $limit, $total_lessons );
			$link_tab    = CourseBuilder::get_tab_link( 'lessons' );
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

	public function get_link_edit( $lesson_id = 0 ) {
		if ( ! $lesson_id ) {
			return '';
		}

		$section  = CourseBuilder::get_current_section( '', 'lessons' );
		$link_tab = CourseBuilder::get_tab_link( 'lessons' );
		$link     = $link_tab . $lesson_id . '/' . $section;

		return $link;
	}
}
