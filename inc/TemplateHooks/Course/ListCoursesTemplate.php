<?php
/**
 * Template hooks List Courses.
 *
 * @since 4.2.3.2
 * @version 1.0.3
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Course;
use LP_Course_Filter;
use LP_Database;
use LP_Settings;
use LP_Settings_Courses;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use stdClass;
use Throwable;
use WP_Term;

class ListCoursesTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/list-courses/layout', [ $this, 'layout_courses' ] );
		add_action( 'learn-press/rest-api/courses/suggest/layout', [ $this, 'sections_course_suggest' ] );
		add_action( 'learn-press/archive-course/sidebar', [ $this, 'sidebar' ] );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	public function allow_callback( $callbacks ) {
		/**
		 * @uses self::render_courses()
		 */
		$callbacks[] = get_class( $this ) . ':render_courses';

		return $callbacks;
	}

	/**
	 * Layout default list courses.
	 *
	 * @return void
	 * @since 4.2.5.8
	 * @version 1.0.1
	 */
	public function layout_courses() {
		$html_wrapper = [
			'<div class="lp-list-courses-default">' => '</div>',
		];

		$callback = [
			'class'  => get_class( $this ),
			'method' => 'render_courses',
		];

		$args                          = lp_archive_skeleton_get_args();
		$args['id_url']                = 'list-courses-default';
		$args['courses_load_ajax']     = LP_Settings_Courses::is_ajax_load_courses() ? 1 : 0;
		$args['courses_first_no_ajax'] = LP_Settings_Courses::is_no_load_ajax_first_courses() ? 1 : 0;

		// Load list courses via AJAX.
		if ( LP_Settings_Courses::is_ajax_load_courses() && ! LP_Settings_Courses::is_no_load_ajax_first_courses() ) {
			$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
		} else { // Load courses first not AJAX, or for filter.
			$content_obj                     = static::render_courses( $args );
			$args['html_no_load_ajax_first'] = $content_obj->content;
			$content                         = TemplateAJAX::load_content_via_ajax( $args, $callback );
		}

		echo Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Render template list courses with settings param.
	 *
	 * @param array $settings
	 *
	 * @return stdClass { content: string_html }
	 * @since 4.2.5.7
	 * @version 1.0.5
	 */
	public static function render_courses( array $settings = [] ): stdClass {
		$filter = new LP_Course_Filter();
		Courses::handle_params_for_query_courses( $filter, $settings );

		$total_rows                   = 0;
		$courses                      = Courses::get_courses( $filter, $total_rows );
		$total_pages                  = LP_Database::get_total_pages( $filter->limit, $total_rows );
		$settings['total_pages']      = $total_pages;
		$settings['total_rows']       = $total_rows;
		$settings['courses_per_page'] = $filter->limit;
		$skin                         = $settings['skin'] ?? ( wp_is_mobile() ? 'grid' : learn_press_get_courses_layout() );
		$paged                        = $settings['paged'] ?? 1;
		$settings['paged']            = $paged;
		$listCoursesTemplate          = self::instance();

		// HTML section courses.
		$html_courses = '';
		if ( empty( $courses ) ) {
			$html_courses = Template::print_message( __( 'No courses found', 'learnpress' ), 'info', false );
		} else {
			foreach ( $courses as $courseObj ) {
				$course        = CourseModel::find( $courseObj->ID, true );
				$html_courses .= static::render_course( $course, $settings );
			}
		}

		$section_courses = apply_filters(
			'learn-press/layout/list-courses/section/courses',
			[
				'wrap'     => sprintf(
					'<ul class="learn-press-courses lp-list-courses-no-css %1$s" data-layout="%1$s">',
					$skin
				),
				'courses'  => $html_courses,
				'wrap_end' => '</ul>',
			],
			$courses,
			$settings
		);

		$section_top = apply_filters(
			'learn-press/layout/list-courses/section/top',
			[
				'wrapper'                   => '<div class="lp-courses-bar">',
				'search'                    => $listCoursesTemplate->html_search_form( $settings ),
				'order_by'                  => $listCoursesTemplate->html_order_by( $settings['order_by'] ?? 'post_date' ),
				'switch_layout'             => $listCoursesTemplate->switch_layout(),
				'btn_filter_courses_mobile' => FilterCourseTemplate::instance()->html_btn_filter_mobile( $settings ),
				'wrapper_end'               => '</div>',
			],
			$courses,
			$settings
		);

		// For old themes use old hook.
		$section_top = self::fix_theme_cb_hook_courses( $section_top, $courses, $settings );
		//$section= self::fix_theme_el_hook_old_render_courses( $section, $courses, $settings );
		// End.

		// Pagination html
		$data_pagination_type = LP_Settings::get_option( 'course_pagination_type', 'number' );
		if ( empty( $settings['courses_load_ajax'] ) ) {
			$data_pagination_type = 'number';
		}
		$data_pagination = [
			'total_pages' => $total_pages,
			'type'        => $data_pagination_type,
			'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
			'paged'       => $settings['paged'] ?? 1,
		];
		$html_pagination = static::instance()->html_pagination( $data_pagination );

		$section = apply_filters(
			'learn-press/layout/list-courses/section',
			[
				'top'        => Template::combine_components( $section_top ),
				'courses'    => Template::combine_components( $section_courses ),
				'pagination' => $html_pagination,
			],
			$courses,
			$settings
		);

		$content              = new stdClass();
		$content->content     = Template::combine_components( $section );
		$content->total_pages = $total_pages;
		$content->paged       = $paged;

		return $content;
	}

	/**
	 * Render single item course
	 *
	 * @param CourseModel|LP_Course $course
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.2.5.8
	 * @version 1.0.4
	 */
	public static function render_course( $course, array $settings = [] ): string {
		if ( ! $course instanceof CourseModel ) {
			$course = CourseModel::find( $course->get_id(), true );
		}
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			// New layout course item.

			// HTML top section, show image.
			$section_top = apply_filters(
				'learn-press/layout/list-courses/item/section-top',
				[
					'wrapper'     => '<div class="course-thumbnail">',
					'img'         => sprintf(
						'<a href="%s">%s</a>',
						$course->get_permalink(),
						$singleCourseTemplate->html_image( $course )
					),
					'wrapper_end' => '</div>',
				],
				$course,
				$settings
			);

			// HTML meta section.
			$meta_data = apply_filters(
				'learn-press/layout/list-courses/item/meta-data',
				[
					'duration' => $singleCourseTemplate->html_duration( $course ),
					'level'    => $singleCourseTemplate->html_level( $course ),
					'lesson'   => $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT ),
					'quiz'     => $singleCourseTemplate->html_count_item( $course, LP_QUIZ_CPT ),
					'student'  => $singleCourseTemplate->html_count_student( $course ),
				],
				$course,
				$settings
			);

			if ( $course->is_offline() ) {
				$singleCourseOfflineTemplate = SingleCourseOfflineTemplate::instance();
				unset( $meta_data['quiz'] );
				unset( $meta_data['student'] );
				if ( ! empty( $meta_data['lesson'] ) ) {
					$meta_data['lesson'] = $singleCourseOfflineTemplate->html_lesson_info( $course, true );
				}

				// Add address for offline course.
				$html_address = $singleCourseOfflineTemplate->html_address( $course );
				if ( ! empty( $html_address ) ) {
					$meta_data['address'] = $singleCourseOfflineTemplate->html_address( $course );
				}
			}

			$html_meta_data = '';
			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $k => $v ) {
					$html_meta_data .= sprintf( '<div class="meta-item meta-item-%s">%s</div>', $k, $v );
				}

				$html_meta_data = sprintf( '<div class="course-wrap-meta">%s</div>', $html_meta_data );
			}

			// HTML bottom section end.
			$section_bottom_end = apply_filters(
				'learn-press/layout/list-courses/item/section/bottom/end',
				[
					'short_des'     => $singleCourseTemplate->html_short_description( $course ),
					'wrapper'       => '<div class="course-info">',
					//'clearfix'          => '<div class="clearfix"></div>',
					//'course_footer'     => '<div class="course-footer">',
					'price'         => $singleCourseTemplate->html_price( $course ),
					'btn_read_more' => sprintf(
						'<div class="course-readmore"><a href="%s">%s</a></div>',
						$course->get_permalink(),
						__( 'Read more', 'learnpress' )
					),
					//'course_footer_end' => '</div>',
					'wrapper_end'   => '</div>',
				],
				$course,
				$settings
			);

			// HTML bottom section.
			$html_categories = $singleCourseTemplate->html_categories( $course );
			if ( ! empty( $html_categories ) ) {
				$html_categories = sprintf(
					'<div>%s %s</div>',
					sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) ),
					$html_categories
				);
			}
			$section_bottom = apply_filters(
				'learn-press/layout/list-courses/item/section/bottom',
				[
					'wrapper'                     => '<div class="course-content">',
					'title'                       => sprintf(
						'<h3 class="wap-course-title"><a class="course-permalink" href="%s">%s</a></h3>',
						$course->get_permalink(),
						$singleCourseTemplate->html_title( $course )
					),
					'featured'                    => $singleCourseTemplate->html_featured( $course ),
					'wrapper_instructor_cate'     => '<div class="course-instructor-category">',
					'instructor'                  => sprintf(
						'<div>%s %s</div>',
						sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
						$singleCourseTemplate->html_instructor( $course )
					),
					'category'                    => $html_categories,
					'wrapper_instructor_cate_end' => '</div>',
					'meta'                        => $html_meta_data,
					'info'                        => Template::combine_components( $section_bottom_end ),
					'wrapper_end'                 => '</div>',
				],
				$course,
				$settings
			);

			$section = apply_filters(
				'learn-press/layout/list-courses/item-li',
				[
					'wrapper_li'      => '<li class="course">',
					'wrapper_div'     => sprintf( '<div class="course-item" data-id="%s">', esc_attr( $course->get_id() ) ),
					'top'             => Template::combine_components( $section_top ),
					'bottom'          => Template::combine_components( $section_bottom ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$course,
				$settings
			);

			// For old themes use old hook.
			$section = self::fix_theme_course_old( $section, $course, $settings );
			// End.

			$html_item = Template::combine_components( $section );
			// End new layout course item.
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}

	/**
	 * Button Load more
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination_load_more(): string {
		$html_wrapper = [
			'<button class="courses-btn-load-more learn-press-pagination lp-button courses-btn-load-more-no-css">' => '</button>',
		];
		$content      = sprintf(
			'%s<span class="lp-loading-circle lp-loading-no-css hide"></span>',
			__( 'Load more', 'learnpress' )
		);

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Button infinite
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination_infinite(): string {
		$html_wrapper = [
			'<div class="courses-load-infinite-no-css courses-load-infinite learn-press-pagination">' => '</div>',
		];
		$content      = '<span class="lp-loading-circle lp-loading-no-css hide"></span>';

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Pagination number
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination_number( array $data = [] ): string {
		if ( empty( $data['total_pages'] ) || $data['total_pages'] <= 1 ) {
			return '';
		}

		$html_wrapper = $data['wrapper'] ?? [
			'<nav class="learn-press-pagination navigation pagination">' => '</nav>',
		];

		$pagination = paginate_links(
			apply_filters(
				'learn_press_pagination_args',
				array(
					'base'      => $data['base'] ?? '',
					'format'    => '',
					'add_args'  => '',
					'current'   => max( 1, $data['paged'] ?? 1 ),
					'total'     => $data[ 'total_pages' ?? 1 ],
					'prev_text' => '<i class="lp-icon-arrow-left"></i>',
					'next_text' => '<i class="lp-icon-arrow-right"></i>',
					'type'      => 'list',
					'end_size'  => 3,
					'mid_size'  => 3,
				)
			)
		);

		return Template::instance()->nest_elements( $html_wrapper, $pagination );
	}

	/**
	 * Pagination
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination( array $data = [] ): string {
		if ( empty( $data['total_pages'] ) || $data['total_pages'] <= 1 ) {
			return '';
		}

		$pagination_type = $data['type'] ?? 'number';
		switch ( $pagination_type ) {
			case 'load-more':
				if ( $data['paged'] >= $data['total_pages'] ) {
					return '';
				}

				return $this->html_pagination_load_more();
			case 'infinite':
				if ( $data['paged'] >= $data['total_pages'] ) {
					return '';
				}

				return $this->html_pagination_infinite();
			default:
				return $this->html_pagination_number( $data );
		}
	}

	/**
	 * Pagination
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.1
	 */
	public function html_courses_page_result( array $data = [] ): string {
		$html = '';

		try {
			$total_rows      = $data['total_rows'] ?? 0;
			$paged           = $data['paged'] ?? 1;
			$course_per_page = $data['courses_per_page'] ?? 8;
			$pagination_type = $data['pagination_type'] ?? 'number';

			$from = 1 + ( $paged - 1 ) * $course_per_page;
			$to   = ( $paged * $course_per_page > $total_rows ) ? $total_rows : $paged * $course_per_page;

			$content = '';
			if ( 0 === $total_rows ) {

			} elseif ( 1 === $total_rows ) {
				$content = esc_html__( 'Showing only one result', 'learnpress' );
			} else {
				$from_to = $from . '-' . $to;
				// For pagination type is load-more or infinite.
				if ( $pagination_type !== 'number' ) {
					$from_to = '1 - ' . $to;
				}
				$content = sprintf( esc_html__( 'Showing %1$s of %2$s results', 'learnpress' ), $from_to, $total_rows );
			}

			if ( ! empty( $content ) ) {
				$html = '<span class="courses-page-result">' . $content . '</span>';
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Layouts type
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_layout_type( array $data = [] ): string {
		$html_wrapper = [
			'<div class="courses-layouts-display">' => '</div>',
		];

		$ico_grid_default = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-grid.svg' );
		$ico_list_default = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-list.svg' );
		$layouts          = [
			'list' => $data['courses_ico_list'] ?? $ico_list_default,
			'grid' => $data['courses_ico_grid'] ?? $ico_grid_default,
		];

		$content = '<ul class="courses-layouts-display-list">';
		foreach ( $layouts as $k => $v ) {
			$active   = ( $data['courses_layout_default'] ?? '' ) === $k ? 'active' : '';
			$content .= '<li class="courses-layout ' . $active . '" data-layout="' . $k . '">' . $v . '</li>';
		}
		$content .= '</ul>';

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Order by
	 *
	 * @param string $default_value
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.1
	 */
	public function html_order_by( string $default_value = 'post_date' ): string {
		$html_wrapper = [
			'<div class="courses-order-by-wrapper">' => '</div>',
		];

		$values = apply_filters(
			'learn-press/courses/order-by/values',
			[
				'post_date'       => esc_html__( 'Newly published', 'learnpress' ),
				'post_title'      => esc_html__( 'Title a-z', 'learnpress' ),
				'post_title_desc' => esc_html__( 'Title z-a', 'learnpress' ),
				'price'           => esc_html__( 'Price high to low', 'learnpress' ),
				'price_low'       => esc_html__( 'Price low to high', 'learnpress' ),
				'popular'         => esc_html__( 'Popular', 'learnpress' ),
			]
		);

		$content = '<select name="order_by" class="courses-order-by">';
		foreach ( $values as $k => $v ) {
			$content .= '<option value="' . $k . '" ' . selected( $default_value, $k, false ) . '>' . $v . '</option>';
		}
		$content .= '</select>';

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	public function html_search_form( array $data = [] ) {
		$s     = $data['c_search'] ?? '';
		$class = $data['class'] ?? 'search-courses';
		ob_start();
		?>
		<form class="<?php echo esc_attr( $class ); ?>" method="get"
			action="<?php echo esc_url_raw( learn_press_get_page_link( 'courses' ) ); ?>">
			<input type="search" placeholder="<?php esc_attr_e( 'Search courses...', 'learnpress' ); ?>"
					name="c_search"
					value="<?php echo esc_attr( $s ); ?>">
			<button type="submit" name="lp-btn-search-courses"><i class="lp-icon-search"></i></button>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Html switch layout.
	 *
	 * @return string
	 */
	public function switch_layout(): string {
		$layouts = learn_press_courses_layouts();
		$active  = learn_press_get_courses_layout();

		$html_layouts = '';
		foreach ( $layouts as $layout => $value ) {
			$html_layouts .= sprintf(
				'<input type="radio" name="lp-switch-layout-btn" value="%s" id="lp-switch-layout-btn-%s" %s>
				<label class="switch-btn %s" title="%s" for="lp-switch-layout-btn-%s"></label>',
				esc_attr( $layout ),
				esc_attr( $layout ),
				checked( $layout, $active, false ),
				esc_attr( $layout ),
				sprintf( esc_attr__( 'Switch to %s', 'learnpress' ), $value ),
				esc_attr( $layout )
			);
		}

		$section = apply_filters(
			'learn-press/layout/list-courses/section/switch-layout',
			[
				'wrapper'     => '<div class="switch-layout">',
				'layouts'     => $html_layouts,
				'wrapper_end' => '</div>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * Layout course search suggest result.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function sections_course_suggest( array $data = [] ) {
		$content              = '';
		$singleCourseTemplate = SingleCourseTemplate::instance();

		ob_start();
		try {
			$courses       = $data['courses'] ?? [];
			$key_search    = $data['keyword'] ?? '';
			$total_courses = $data['total_course'] ?? 0;

			// HTML section list courses.
			$html_list_course = '';
			foreach ( $courses as $courseObj ) {
				if ( ! is_object( $courseObj ) ) {
					continue;
				}
				$course_id = $courseObj->ID;
				$course    = learn_press_get_course( $course_id );
				if ( ! $course ) {
					continue;
				}

				$section_item      = apply_filters(
					'learn-press/course-suggest/item/sections',
					[
						'wrapper'      => '<li class="item-course-suggest">',
						'course_image' => $singleCourseTemplate->html_image( $course ),
						'course_title' => sprintf(
							'<a href="%s">%s</a>',
							$course->get_permalink(),
							$singleCourseTemplate->html_title( $course )
						),
						'wrapper_end'  => '</li>',
					],
					$course,
					$key_search,
					$data
				);
				$html_list_course .= Template::combine_components( $section_item );
			}

			$count_courses = sprintf(
				'%s %s',
				$total_courses,
				_n( 'Course Found', 'Courses Found', $total_courses, 'learnpress' )
			);
			$view_all      = sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( 'c_search', $key_search, learn_press_get_page_link( 'courses' ) ),
				__( 'View All', 'learnpress' )
			);

			// Section.
			$section = [
				'wrapper'          => '<ul class="lp-courses-suggest-list">',
				'courses'          => $html_list_course,
				'wrapper_end'      => '</ul>',
				'wrapper_info'     => '<div class="lp-courses-suggest-info">',
				'count'            => $count_courses,
				'view_all'         => $view_all,
				'wrapper_info_end' => '</div>',
			];
			$content = Template::combine_components( $section );
			echo $content;
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Sidebar
	 *
	 * @return void
	 * @version 1.0.0
	 * @since 4.2.3.2
	 */
	public function sidebar() {
		try {
			if ( is_active_sidebar( 'archive-courses-sidebar' ) ) {
				$html_wrapper = [
					'<div class="lp-archive-courses-sidebar">' => '</div>',
				];

				ob_start();
				dynamic_sidebar( 'archive-courses-sidebar' );
				echo Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Show total course free by Category.
	 * @return string
	 * @version 1.0.0
	 *
	 * @since 4.2.5.4
	 */
	public function html_count_course_free(): string {
		$html_wrapper = [
			'<div class="courses-count-free">' => '</div>',
		];

		$category_current      = 0;
		$category_current_slug = get_query_var( 'term' );
		if ( ! empty( $category_current_slug ) ) {
			$category_current_obj = get_term_by( 'slug', $category_current_slug, LP_COURSE_CATEGORY_TAX );
			if ( $category_current_obj instanceof WP_Term ) {
				$category_current = $category_current_obj->term_id;
			}
		}

		$filter = new LP_Course_Filter();
		if ( ! empty( $category_current ) ) {
			$filter->term_ids = [ $category_current ];
		}

		$count   = Courses::count_course_free( $filter );
		$content = sprintf(
			'<span class="courses-count-free-number">%1$s</span> %2$s',
			$count,
			_n( 'Free Course', 'Free Courses', $count, 'learnpress' )
		);

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Show total students on Course Category.
	 *
	 * @return string
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function html_count_students(): string {
		$html_wrapper = [
			'<div class="courses-count-students">' => '</div>',
		];

		$category_current      = 0;
		$category_current_slug = get_query_var( 'term' );
		if ( ! empty( $category_current_slug ) ) {
			$category_current_obj = get_term_by( 'slug', $category_current_slug, LP_COURSE_CATEGORY_TAX );
			if ( $category_current_obj instanceof WP_Term ) {
				$category_current = $category_current_obj->term_id;
			}
		}

		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$filter           = new LP_User_Items_Filter();

		// If page is course category, get total students of this category.
		if ( ! empty( $category_current ) ) {
			$filter->join[]  = "INNER JOIN {$lp_user_items_db->tb_posts} AS p ON ui.item_id = p.ID";
			$filter->join[]  = "INNER JOIN {$lp_user_items_db->tb_term_relationships} AS r_term ON ui.item_id = r_term.object_id";
			$filter->where[] = $lp_user_items_db->wpdb->prepare( 'AND r_term.term_taxonomy_id = %d', $category_current );
		}

		$count   = UserCourseModel::count_students( $filter );
		$content = sprintf(
			'<span class="courses-count-students-number">%1$s</span> %2$s',
			$count,
			_n( 'Student', 'Students', $count, 'learnpress' )
		);

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/************************** Hook old *****************************/

	/**
	 * Fix theme course-builder use old hook.
	 *
	 * @param array $section
	 *
	 * @return array
	 */
	public static function fix_theme_cb_hook_courses( $section, $courses, $settings ) {
		/*$theme_name = wp_get_theme()->get( 'Name' );
		if ( 'Course Builder' !== $theme_name ) {
			return $section;
		}*/

		$section_top = apply_filters(
			'learn-press/list-courses/layout/section/top',
			[],
			$courses,
			$settings
		);

		$section_new = [];
		if ( ! empty( $section_top ) ) {
			foreach ( $section_top as $k => $v ) {
				$section_new[ $k ] = $v['text_html'] ?? '';
			}

			return $section_new;
		}

		return $section;
	}

	/**
	 * Fix theme course-builder use old hook.
	 *
	 * @param array $section
	 *
	 * @return array
	 */
	public static function fix_theme_course_old( $section, $course, $settings ) {
		/*$theme_name = wp_get_theme()->get( 'Name' );
		if ( 'Course Builder' !== $theme_name ) {
			return $section;
		}*/

		$course = learn_press_get_course( $course->get_id() );

		$wrapper = apply_filters(
			'learn-press/list-courses/layout/item/wrapper',
			[],
			$course,
			$settings
		);
		if ( ! empty( $wrapper ) ) {
			$i = 0;
			foreach ( $wrapper as $k => $v ) {
				if ( $i === 0 ) {
					$section['wrapper_li']     = $k;
					$section['wrapper_li_end'] = $v;
				} elseif ( $i === 1 ) {
					$section['wrapper_div']     = $k;
					$section['wrapper_div_end'] = $v;
					break;
				}

				++$i;
			}
		}

		$section_item = apply_filters(
			'learn-press/list-courses/layout/item/section',
			[],
			$course,
			$settings
		);
		if ( ! empty( $section_item ) ) {
			$section['top'] = $section_item['thim_top']['text_html'] ?? '';
		}

		$section_bottom     = apply_filters(
			'learn-press/list-courses/layout/item/section/bottom',
			[],
			$course,
			$settings
		);
		$section_bottom_new = [];
		if ( ! empty( $section_bottom ) ) {
			foreach ( $section_bottom as $k => $v ) {
				$section_bottom_new[ $k ] = $v['text_html'] ?? '';
			}

			$section['bottom'] = Template::combine_components( $section_bottom_new );
		}

		return $section;
	}

	/**
	 * Render string to data content
	 *
	 * @param array $data
	 * @param string $data_content
	 *
	 * @return string
	 */
	public function render_data( array $data, string $data_content ): string {
		return str_replace(
			[
				'{{courses_order_by}}',
				'{{courses_layout_type}}',
				'{{courses_pagination}}',
				'{{courses_page_result}}',
			],
			[
				$this->html_order_by( $data['order_by'] ?? '' ),
				$this->html_layout_type( $data ),
				$this->html_pagination( $data['pagination'] ?? [] ),
				$this->html_courses_page_result( $data ),
			],
			$data_content
		);
	}
}
