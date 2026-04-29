<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Lesson;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\BuilderPopupTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use Throwable;
use WP_Query;

class BuilderListLessonsTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/list-lessons/layout', [ $this, 'layout' ] );
	}

	public function layout( array $data = [] ) {
		$list_lesson    = $this->tab_list_lessons();
		$popup_template = sprintf(
			'<script type="text/template" id="lp-tmpl-builder-popup-lesson-list"><div class="lp-builder-popup-overlay"></div><div class="lp-builder-popup lp-builder-popup--loading">%s</div></script>',
			TemplateAJAX::load_content_via_ajax(
				[
					'id_url'                  => 'builder-popup-lesson-list',
					'lesson_id'               => 0,
					'html_no_load_ajax_first' => sprintf(
						'<div class="lp-builder-popup__loader"><div class="lp-loading-circle"></div><span>%s</span></div>',
						esc_html__( 'Loading...', 'learnpress' )
					),
				],
				[
					'class'  => BuilderPopupTemplate::class,
					'method' => 'render_lesson_popup',
				]
			)
		);

		$tab = [
			'wrapper'        => '<div class="cb-tab-lesson">',
			'header'         => $this->html_header(),
			'filter_bar'     => $this->html_filter_bar(),
			'lessons'        => $list_lesson,
			'popup_template' => $popup_template,
			'wrapper_end'    => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_header(): string {
		$section = [
			'wrapper'     => '<div class="cb-tab-header">',
			'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', __( 'Lessons', 'learnpress' ) ),
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
			'wrapper_action'     => '<div class="cb-tab-lesson__action">',
			'search_lesson'      => $this->html_search(),
			'wrapper_action_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_search() {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'lessons' );

		$search = [
			'wrapper'       => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_lesson' => '<button class="lp-button cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
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

			if ( ! current_user_can( ADMIN_ROLE ) ) {
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
			wp_reset_postdata();

			if ( ! empty( $lessons ) ) {
				$html_lessons = $this->list_lessons( $lessons );
			} else {
				$html_lessons = Template::print_message(
					sprintf( __( 'No lessons found', 'learnpress' ) ),
					'info',
					false
				);
			}

			$total_pages     = \LP_Database::get_total_pages( $query_args['posts_per_page'], $total_lessons );
			$link_tab        = CourseBuilder::get_tab_link( 'lessons' );
			$data_pagination = [
				'paged'       => max( 1, $query_args['paged'] ?? 1 ),
				'total_pages' => $total_pages,
				'base'        => trailingslashit( $link_tab ) . 'page/%#%',
				'format'      => '',
			];

			$pagination = Template::instance()->html_pagination( $data_pagination );

			$sections = apply_filters(
				'learn-press/course-builder/lessons/sections',
				[
					'wrapper'     => '<div class="courses-builder__lesson-tab learn-press-lessons">',
					'lessons'     => $html_lessons,
					'wrapper_end' => '</div>',
					'pagination'  => $pagination,
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

			$header  = '<div class="cb-list-table-header">';
			$header .= sprintf( '<span>%s</span>', __( 'Lesson Title', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Courses', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Create Date', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Status', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Preview', 'learnpress' ) );
			$header .= sprintf( '<span>%s</span>', __( 'Actions', 'learnpress' ) );
			$header .= '</div>';

			$sections = [
				'header'      => $header,
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
		$author      = get_user_by( 'ID', $lesson_model->post_author );
		$author_name = $author && isset( $author->display_name ) ? $author->display_name : '--';

		$lesson = array(
			'id'            => $lesson_model->get_id(),
			'title'         => $lesson_model->post_title,
			'status'        => $lesson_model->post_status,
			'courses'       => BuilderEditLessonTemplate::instance()->get_assigned( $lesson_model->get_id() ),
			'author'        => $author_name,
			'preview'       => get_post_meta( $lesson_model->get_id(), '_lp_preview', true ),
			'date_modified' => lp_jwt_prepare_date_response( $lesson_model->post_date_gmt ),
		);

		try {
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

			$status = $lesson_model->post_status ?? '';

			$html_content = apply_filters(
				'learn-press/course-builder/list-lessons/item/section/bottom',
				[
					'title'         => sprintf(
						'<h3 class="wap-lesson-title"><button data-popup-lesson="%1$s" data-popup-type="lesson" data-popup-id="%1$s" data-template="#lp-tmpl-builder-popup-lesson-list">%2$s</button></h3>',
						$lesson_model->get_id(),
						$lesson['title']
					),
					'courses'       => $html_courses,
					'date'          => sprintf( '<span class="lesson__date">%s</span>', ! empty( $lesson['date_modified'] ) ? date_i18n( 'm/d/Y', strtotime( $lesson['date_modified'] ) ) : '--' ),
					'lesson_status' => ! empty( $status ) ? sprintf( '<span class="lesson-status %1$s">%1$s</span>', $status ) : '<span></span>',
					'preview'       => sprintf(
						'<span class="lesson__preview lp-button lp-btn-set-preview-item" data-id="%s" title="%s"><a class="%s"></a></span>',
						$lesson['id'],
						__( 'Toggle preview', 'learnpress' ),
						$lesson['preview'] === 'yes' ? 'lp-icon-eye' : 'lp-icon-eye-slash'
					),
				],
				$lesson,
				$settings
			);

			$edit_icon         = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-edit.svg' );
			$more_actions_icon = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-more.svg' );

			$html_action = apply_filters(
				'learn-press/course-builder/list-lessons/item/action',
				[
					'wrapper'                     => '<div class="lesson-action">',
					'edit'                        => $status !== PostModel::STATUS_TRASH ? sprintf(
						'<div class="lesson-action-editor"><button class="lp-button btn-edit-lesson lesson-edit-permalink" data-popup-lesson="%1$s" data-popup-type="lesson" data-popup-id="%1$s" data-template="#lp-tmpl-builder-popup-lesson-list">%2$s %3$s</button></div>',
						$lesson['id'],
						$edit_icon,
						__( 'Edit', 'learnpress' )
					) : '',
					'action_expanded_button'      => sprintf(
						'<button type="button" class="lp-button lesson-action-expanded">%s</button>',
						$more_actions_icon
					),
					'action_expanded_wrapper'     => '<div style="display:none;" class="lesson-action-expanded__items">',
					'action_expanded_duplicate'   => sprintf( '<span class="lp-button lesson-action-expanded__duplicate" data-title="%s" data-content="%s">%s</span>', __( 'Are you sure?', 'learnpress' ), __( 'Are you sure you want to duplicate this lesson?', 'learnpress' ), __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_publish'     => sprintf( '<span class="lp-button lesson-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf(
						'<span class="lp-button lesson-action-expanded__trash"%s>%s</span>',
						$status === 'trash' ? ' style="display:none"' : '',
						__( 'Trash', 'learnpress' )
					),
					'action_expanded_restore'     => sprintf(
						'<span class="lp-button lesson-action-expanded__restore"%s>%s</span>',
						$status !== 'trash' ? ' style="display:none"' : '',
						__( 'Restore', 'learnpress' )
					),
					'action_expanded_delete'      => sprintf( '<span class="lp-button lesson-action-expanded__delete" data-title="%s" data-content="%s">%s</span>', __( 'Are you sure?', 'learnpress' ), __( 'Are you sure you want to delete this lesson? This action cannot be undone.', 'learnpress' ), __( 'Delete', 'learnpress' ) ),
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
					'wrapper_div'     => sprintf( '<div class="lesson-item" data-lesson-id="%s" data-status="%s">', $lesson['id'], $status ),
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
}
