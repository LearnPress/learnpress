<?php
/**
 * Template hooks List Courses.
 *
 * @since 4.2.3.2
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Courses;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Course;
use LP_Course_Filter;
use LP_Database;
use LP_Request;
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
		$callbacks[] = get_class( $this ) . ':render_courses';

		return $callbacks;
	}

	public function layout_courses() {
		$html_wrapper = [
			'<div id="lp-list-courses-default" class="learn-press-courses-wrapper">' => '</div>',
		];

		$callback = [
			'class'  => get_class( $this ),
			'method' => 'render_courses',
		];

		if ( LP_Settings_Courses::is_ajax_load_courses() && ! LP_Settings_Courses::is_no_load_ajax_first_courses() ) {
			$content = TemplateAJAX::load_content_via_ajax( lp_archive_skeleton_get_args(), $callback );
		} else {
			$target_id   = uniqid( 'lp-target-' );
			$url_params  = lp_archive_skeleton_get_args();
			$settings    = [
				'args'     => $url_params,
				'callback' => $callback,
				'id'       => $target_id,
			];
			$content_obj = self::render_courses( $url_params );
			$content     = sprintf(
				'<div class="lp-target" data-send="%s" data-id="%s">%s</div>',
				esc_attr( htmlentities2( json_encode( $settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) ),
				$target_id,
				$content_obj->content
			);
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
	 * @version 1.0.1
	 */
	public static function render_courses( array $settings = [] ): stdClass {
		$filter = new LP_Course_Filter();
		Courses::handle_params_for_query_courses( $filter, $settings );
		// Check is in category page.
		if ( ! empty( $settings['page_term_id_current'] ) &&
			empty( $settings['term_id'] ) ) {
			$filter->term_ids[] = $settings['page_term_id_current'];
		} // Check is in tag page.
		elseif ( ! empty( $settings['page_tag_id_current'] ) &&
				empty( $settings['tag_id'] ) ) {
			$filter->tag_ids[] = $settings['page_tag_id_current'];
		}
		$total_rows          = 0;
		$courses             = Courses::get_courses( $filter, $total_rows );
		$total_pages         = LP_Database::get_total_pages( $filter->limit, $total_rows );
		$skin                = learn_press_get_courses_layout();
		$paged               = $settings['paged'] ?? 1;
		$listCoursesTemplate = self::instance();

		// Handle layout
		ob_start();

		$section_top = [
			'wrapper'         => [ 'text_html' => '<div class="lp-courses-bar">' ],
			'el_result_count' => [ 'text_html' => $listCoursesTemplate->html_search_form( $settings ) ],
			'el_sorting'      => [ 'text_html' => $listCoursesTemplate->switch_layout() ],
			'close_wrapper'   => [ 'text_html' => '</div>' ],
		];
		Template::instance()->print_sections( $section_top );
		echo '<ul class="learn-press-courses ' . $skin . '" data-layout="' . $skin . '">';
		if ( empty( $courses ) ) {
			echo sprintf( '<p class="learn-press-message success">%s!</p>', __( 'No courses found', 'learnpress' ) );
		} else {
			foreach ( $courses as $courseObj ) {
				$course = learn_press_get_course( $courseObj->ID );
				echo static::render_course( $course, $settings );
			}
		}
		echo '</ul>';

		$data_pagination = [
			'total_pages' => $total_pages,
			'type'        => LP_Settings::get_option( 'course_pagination_type', 'number' ),
			'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
			'paged'       => $settings['paged'] ?? 1,
		];
		echo static::instance()->html_pagination( $data_pagination );

		$content              = new stdClass();
		$content->content     = ob_get_clean();
		$content->total_pages = $total_pages;
		$content->paged       = $paged;

		return $content;
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
			'<button class="courses-btn-load-more learn-press-pagination lp-button">' => '</button>',
		];
		$content      = sprintf(
			'%s<span class="lp-loading-circle hide"></span>',
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
			'<div class="courses-load-infinite learn-press-pagination">' => '</div>',
		];
		$content      = '<span class="lp-loading-circle hide"></span>';

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

		$html_wrapper = [
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
					'prev_text' => '<i class="fas fa-angle-left"></i>',
					'next_text' => '<i class="fas fa-angle-right"></i>',
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
	 * @version 1.0.0
	 */
	public function html_courses_page_result( array $data = [] ): string {
		$html = '';

		try {
			$total_rows      = $data['total_rows'] ?? 0;
			$paged           = $data['paged'] ?? 1;
			$course_per_page = $data['courses_per_page'] ?? 8;

			$from = 1 + ( $paged - 1 ) * $course_per_page;
			$to   = ( $paged * $course_per_page > $total_rows ) ? $total_rows : $paged * $course_per_page;

			$content = '';
			if ( 0 === $total_rows ) {

			} elseif ( 1 === $total_rows ) {
				$content = esc_html__( 'Showing only one result', 'learnpress' );
			} else {
				if ( $from === $to ) {
					$content = sprintf( esc_html__( 'Showing last course of %s results', 'learnpress' ), $total_rows );
				} else {
					$from_to = $from . '-' . $to;
					$content = sprintf( esc_html__( 'Showing %1$s of %2$s results', 'learnpress' ), $from_to, $total_rows );
				}
			}

			$html = '<span class="courses-page-result">' . $content . '</span>';
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
	 * @param string $default
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.1
	 */
	public function html_order_by( string $default = 'post_date' ): string {
		$html_wrapper = [
			'<div class="courses-order-by-wrapper">' => '</div>',
		];

		$values = [
			'post_date'       => esc_html__( 'Newly published', 'learnpress' ),
			'post_title'      => esc_html__( 'Title a-z', 'learnpress' ),
			'post_title_desc' => esc_html__( 'Title z-a', 'learnpress' ),
			'price'           => esc_html__( 'Price high to low', 'learnpress' ),
			'price_low'       => esc_html__( 'Price low to high', 'learnpress' ),
			'popular'         => esc_html__( 'Popular', 'learnpress' ),
		];

		$content = '<select name="order_by" class="courses-order-by">';
		foreach ( $values as $k => $v ) {
			$content .= '<option value="' . $k . '" ' . selected( $default, $k, false ) . '>' . $v . '</option>';
		}
		$content .= '</select>';

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	public function html_search_form( array $data = [] ) {
		$s = $data['c_search'] ?? '';
		ob_start();
		?>
		<form class="search-courses" method="get"
				action="<?php echo esc_url_raw( learn_press_get_page_link( 'courses' ) ); ?>">
			<label>
				<input type="text" placeholder="<?php esc_attr_e( 'Search courses...', 'learnpress' ); ?>"
						name="c_search"
						value="<?php echo esc_attr( $s ); ?>">
			</label>
			<button type="submit" name="lp-btn-search-courses"><i class="fas fa-search"></i></button>
		</form>
		<?php
		return ob_get_clean();
	}

	public function switch_layout() {
		$layouts = learn_press_courses_layouts();
		$active  = learn_press_get_courses_layout();
		ob_start();
		?>
		<div class="switch-layout">
			<?php foreach ( $layouts as $layout => $value ) : ?>
				<input type="radio" name="lp-switch-layout-btn"
						value="<?php echo esc_attr( $layout ); ?>"
						id="lp-switch-layout-btn-<?php echo esc_attr( $layout ); ?>" <?php checked( $layout, $active ); ?>>
				<label class="switch-btn <?php echo esc_attr( $layout ); ?>"
						title="<?php echo sprintf( esc_attr__( 'Switch to %s', 'learnpress' ), $layout ); ?>"
						for="lp-switch-layout-btn-<?php echo esc_attr( $layout ); ?>"></label>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
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

			// Section list courses.
			$html_item_wrapper = [
				'<ul class="lp-courses-suggest-list">' => '</ul>',
			];
			$list_course       = '';
			foreach ( $courses as $courseObj ) {
				if ( ! is_object( $courseObj ) ) {
					continue;
				}
				$course_id = $courseObj->ID;
				$course    = learn_press_get_course( $course_id );
				if ( ! $course ) {
					continue;
				}

				$item_wrapper  = [
					'<li class="item-course-suggest">' => '</li>',
				];
				$course_title  = sprintf(
					'<a href="%s">%s</a>',
					$course->get_permalink(),
					$singleCourseTemplate->html_title( $course )
				);
				$item_sections = apply_filters(
					'learn-press/course-suggest/item/sections',
					[
						'course_image' => [ 'text_html' => $singleCourseTemplate->html_image( $course ) ],
						'course_title' => [ 'text_html' => $course_title ],
					]
				);
				ob_start();
				Template::instance()->print_sections( $item_sections );
				$item_content = ob_get_clean();
				$list_course .= Template::instance()->nest_elements( $item_wrapper, $item_content );
			}
			$list_course = Template::instance()->nest_elements( $html_item_wrapper, $list_course );
			// End section list courses.

			// Section info search.
			$html_info_wrapper = [
				'<div class="lp-courses-suggest-info">' => '</div>',
			];
			$count_courses     = sprintf(
				'%s %s',
				$total_courses,
				_n( 'Course Found', 'Courses Found', $total_courses, 'learnpress' )
			);
			$view_all          = sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( 'c_search', $key_search, learn_press_get_page_link( 'courses' ) ),
				__( 'View All', 'learnpress' )
			);
			$info_sections     = apply_filters(
				'learn-press/course-suggest/info/sections',
				[
					'count'    => [ 'text_html' => $count_courses ],
					'view_all' => [ 'text_html' => $view_all ],
				]
			);

			ob_start();
			Template::instance()->print_sections( $info_sections );
			$info_content = ob_get_clean();
			$info_content = Template::instance()->nest_elements( $html_info_wrapper, $info_content );
			// End section info search.

			$content = $list_course . $info_content;
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

	/**
	 * Render single item course
	 *
	 * @param LP_Course $course
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render_course( LP_Course $course, array $settings = [] ): string {
		$html_item            = '';
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$html_wrapper = [
				'<li class="course">'       => '</li>',
				'<div class="course-item">' => '</div>',
			];

			$top_wrapper = [
				'<div class="course-wrap-thumbnail">' => '</div>',
				'<div class="course-thumbnail">'      => '</div>',
			];
			$img         = sprintf( '<a href="%s">%s</a>', $course->get_permalink(), $singleCourseTemplate->html_image( $course ) );
			$html_top    = Template::instance()->nest_elements( $top_wrapper, $img );

			$bottom_wrapper  = [
				'<div class="course-content">' => '</div>',
			];
			$bottom_content  = '';
			$bottom_content .= str_replace( ',', '', $singleCourseTemplate->html_categories( $course ) );
			$bottom_content .= $singleCourseTemplate->html_instructor( $course );
			$bottom_content .= sprintf( '<a class="course-permalink" href="%s">%s</a>', $course->get_permalink(), $singleCourseTemplate->html_title( $course ) );
			$bottom_content .= sprintf(
				'<div class="course-wrap-meta">%s%s%s%s%s</div>',
				sprintf( '<div class="meta-item meta-item-duration">%s</div>', $singleCourseTemplate->html_duration( $course ) ),
				sprintf( '<div class="meta-item meta-item-level">%s</div>', $singleCourseTemplate->html_level( $course ) ),
				sprintf( '<div class="meta-item meta-item-lesson">%s</div>', $singleCourseTemplate->html_count_item( $course, 'lesson' ) ),
				sprintf( '<div class="meta-item meta-item-quiz">%s</div>', $singleCourseTemplate->html_count_item( $course, 'quiz' ) ),
				sprintf( '<div class="meta-item meta-item-student">%s</div>', $singleCourseTemplate->html_count_student( $course ) )
			);
			$bottom_content .= '<div class="separator"></div>';
			$course_info     = sprintf(
				'<div class="course-info">%s%s%s%s</div>',
				$singleCourseTemplate->html_short_description( $course ),
				'<div class="clearfix"></div>',
				sprintf( '<div class="course-footer">%s</div>', $singleCourseTemplate->html_price( $course ) ),
				sprintf( '<div class="course-readmore"><a href="%s">%s</a></div>', $course->get_permalink(), __( 'Read more', 'learnpress' ) )
			);

			$bottom_content .= $course_info;

			$html_bottom = Template::instance()->nest_elements( $bottom_wrapper, $bottom_content );

			$section = [
				'top'    => [ 'text_html' => $html_top ],
				'bottom' => [ 'text_html' => $html_bottom ],
			];

			ob_start();
			Template::instance()->print_sections( $section );
			$html_item = ob_get_clean();
			$html_item = Template::instance()->nest_elements( $html_wrapper, $html_item );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}
}
