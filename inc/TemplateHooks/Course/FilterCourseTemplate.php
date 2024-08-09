<?php
/**
 * Template hooks Archive Package.
 *
 * @since 4.2.3.2
 * @version 1.0.4
 */

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Courses;
use LearnPress\Models\ListCourseCategories;
use LP_Course;
use LP_Course_Filter;
use LP_Request;
use Throwable;

class FilterCourseTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/filter-courses/layout', [ $this, 'sections' ] );
		//add_action( 'wp_head', [ $this, 'add_internal_scripts_to_head' ] );
	}

	/**
	 * Sections of template filter courses.
	 *
	 * @param array $data
	 *
	 * @return void
	 * @uses self::html_category()
	 *
	 * @since 4.2.3.2
	 * @version 1.0.1
	 */
	public function sections( array $data = [] ) {
		wp_enqueue_script( 'lp-course-filter' );

		try {
			if ( ! isset( $data['fields'] ) ) {
				$data['fields'] = [
					'search',
					'price',
					'category',
					'tag',
					'author',
					'level',
					'btn_submit',
					'btn_reset',
				];

				$data = apply_filters( 'learn-press/filter-courses/data', $data );
			} elseif ( is_string( $data['fields'] ) ) {
				$data['fields'] = explode( ',', $data['fields'] );
			}

			if ( ! is_array( $data['fields'] ) ) {
				throw new Exception( 'Fields must be array' );
			}

			if ( isset( $data['fields']['btn_submit'] ) ) {
				$data['fields'][] = 'btn_submit';
			}

			$html_wrapper = apply_filters(
				'learn-press/filter-courses/sections/wrapper',
				[
					'<form class="lp-form-course-filter">' => '</form>',
				],
				$data
			);
			$sections     = [];
			foreach ( $data['fields'] as $field ) {
				if ( is_callable( [ $this, 'html_' . $field ] ) ) {
					$sections[ $field ] = [ 'text_html' => $this->{'html_' . $field}( $data ) ];
				} else { // For custom field.
					do_action_ref_array(
						'learn-press/filter-courses/sections/field/html',
						[
							&$sections,
							$field,
							$data,
						]
					);
				}
			}

			ob_start();
			Template::instance()->print_sections( $sections );
			echo Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Get html item.
	 *
	 * @param string $title
	 * @param string $content
	 *
	 * @return string
	 */
	public function html_item( string $title = '', string $content = '' ): string {
		try {
			ob_start();
			$html_wrapper = apply_filters(
				'learn-press/filter-courses/item/wrapper',
				[
					'<div class="lp-form-course-filter__item">' => '</div>',

				]
			);
			$title_html   = sprintf(
				'<div class="lp-form-course-filter__title">%s</div>',
				$title
			);
			$content_html = sprintf(
				'<div class="lp-form-course-filter__content">%s</div>',
				$content
			);
			$sections     = apply_filters(
				'learn-press/filter-courses/item/sections',
				[
					'title'   => [ 'text_html' => $title_html ],
					'content' => [ 'text_html' => $content_html ],
				],
				$title,
				$content
			);
			Template::instance()->print_sections( $sections );
			$content = Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html search.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_search( array $data = [] ): string {
		$content = '';

		try {
			$html_wrapper = apply_filters(
				'learn-press/filter-courses/sections/search/wrapper',
				[
					'<div class="lp-course-filter-search-field">' => '</div>',
				],
				$data
			);

			$this->check_param_url_has_lang( $data );
			$value   = LP_Request::get_param( 'c_search' );
			$value   = isset( $data['params_url'] ) ? ( $data['params_url']['c_search'] ?? $value ) : $value;
			$content = sprintf(
				'<input type="text" name="c_search" placeholder="%s" value="%s" class="%s" data-search-suggest="%d">',
				__( 'Search Course', 'learnpress' ),
				$value,
				'lp-course-filter-search',
				$data['search_suggestion'] ?? 1
			);
			$content .= '<span class="lp-loading-circle lp-loading-no-css hide"></span>';
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
			$content .= '<div class="lp-course-filter-search-result"></div>';
			$content = $this->html_item( esc_html__( 'Search', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html price.
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.3
	 */
	public function html_price( array $data = [] ): string {
		$content = '';

		try {
			$this->check_param_url_has_lang( $data );
			$params_url      = $data['params_url'] ?? [];
			$data_selected   = $params_url['sort_by'] ?? '';
			$data_selected   = explode( ',', $data_selected );
			$hide_count_zero = $data['hide_count_zero'] ?? 1;

			// Get number courses free
			$filter_courses_free = new LP_Course_Filter();
			$this->handle_filter_params_before_query( $filter_courses_free, $params_url );
			// Not count include sort by price.
			$filter_courses_free->sort_by = [];
			$count_courses_free           = Courses::count_course_free( $filter_courses_free );

			// Get number courses has price
			$filter_courses_price = new LP_Course_Filter();
			$this->handle_filter_params_before_query( $filter_courses_price, $params_url );
			$filter_courses_price->query_count = true;
			$filter_courses_price->sort_by     = [ 'on_paid' ];
			$count_courses_paid                = 0;
			Courses::get_courses( $filter_courses_price, $count_courses_paid );

			$fields = apply_filters(
				'learn-press/filter-courses/price/fields',
				[
					'free' => [
						'label' => __( 'Free', 'learnpress' ),
						'count' => $count_courses_free,
					],
					'paid' => [
						'label' => __( 'Paid', 'learnpress' ),
						'count' => $count_courses_paid,
					],
				]
			);

			foreach ( $fields as $key => $field ) {
				$html_wrapper = [
					'<div class="lp-course-filter__field">' => '</div>',
				];

				$value    = "on_{$key}";
				$disabled = $field['count'] > 0 ? '' : 'disabled';
				if ( ! empty( $disabled ) && $hide_count_zero ) {
					continue;
				}
				$checked = in_array( $value, $data_selected ) && empty( $disabled ) ? 'checked' : '';
				$input   = sprintf(
					'<input name="sort_by" type="checkbox" value="%1$s" %2$s %3$s>',
					esc_attr( $value ),
					esc_attr( $checked ),
					esc_attr( $disabled )
				);
				$label   = sprintf( '<label for="">%s</label>', wp_kses_post( $field['label'] ) );
				$count   = sprintf( '<span class="count">%s</span>', esc_html( $field['count'] ) );

				$sections = apply_filters(
					'learn-press/filter-courses/price/sections',
					[
						'input' => [ 'text_html' => $input ],
						'label' => [ 'text_html' => $label ],
						'count' => [ 'text_html' => $count ],
					],
					$field,
					$data
				);

				ob_start();
				Template::instance()->print_sections( $sections );
				$content .= Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}

			$content = $this->html_item( esc_html__( 'Price', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html course list categories.
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.4
	 */
	public function html_category( array $data = [] ): string {
		$content = '';

		try {
			$this->check_param_url_has_lang( $data );
			$params_url            = $data['params_url'] ?? [];
			$data_selected         = $params_url['term_id'] ?? '';
			$data_selected         = explode( ',', $data_selected );
			$data['data_selected'] = $data_selected;
			$parent_cat_id         = 0;

			if ( isset( $params_url['page_term_id_current'] ) ) {
				$category_current_id = $params_url['page_term_id_current'];
				$category_current    = get_term_by( 'id', $category_current_id, LP_COURSE_CATEGORY_TAX );

				if ( ! empty( $category_current ) ) {
					$parent_cat_id = $category_current_id;
					$content       .= $this->html_field_category( $category_current->term_id, $category_current->name, $data );
				}
			}

			// For subcategories.
			ob_start();
			$data['level_current']  = 0;
			$data['parent_term_id'] = $parent_cat_id;
			$this->html_struct_categories( $data );
			$content .= ob_get_clean();

			$html_wrapper = [
				'<div class="lp-course-filter-category">' => '</div>',
			];
			$content      = $this->html_item( esc_html__( 'Categories', 'learnpress' ), $content );
			$content      = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get list categories course.
	 *
	 * @param array $args
	 *
	 * @return void
	 * @since 4.2.6.5
	 * @version 1.0.0
	 */
	public function html_struct_categories( array $args = [] ) {
		$level_current         = $args['level_current'] ?? 0;
		$number_level_category = $args['number_level_category'] ?? 2;
		$parent_term_id        = $args['parent_term_id'] ?? 0;

		if ( $level_current >= $number_level_category ) {
			return;
		}

		$terms = ListCourseCategories::get_all_categories_id_name( [ 'parent' => $parent_term_id ] );
		if ( empty( $terms ) ) {
			return;
		}

		$class_wrapper = 'lp-cate-parent';
		if ( $level_current > 0 ) {
			$class_wrapper = 'lp-cate-child';
		}

		echo sprintf( '<div class="%s">', esc_attr( $class_wrapper ) );
		foreach ( $terms as $term_id => $term_name ) {
			echo sprintf( '<div class="lp-cat-%s">', esc_attr( $term_id ) );
			echo $this->html_field_category( $term_id, $term_name, $args );

			$args['level_current']  = $level_current + 1;
			$args['parent_term_id'] = $term_id;
			$this->html_struct_categories( $args );
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Return string html a field category.
	 *
	 * @param int $category_id
	 * @param string $category_name
	 * @param array $args
	 *
	 * @return false|string
	 * @since 4.2.6.5
	 * @version 1.0.0
	 */
	public function html_field_category( int $category_id, string $category_name, array $args = [] ) {
		$count_courses       = 0;
		$filter              = new LP_Course_Filter();
		$filter->query_count = true;
		$filter->only_fields = [ 'DISTINCT(ID)' ];
		$this->handle_filter_params_before_query( $filter, $args['params_url'] ?? [] );
		$filter->term_ids = [ $category_id ];
		//$filter->debug_string_query = true;
		Courses::get_courses( $filter, $count_courses );

		$disabled = $count_courses > 0 ? '' : 'disabled';
		if ( ! empty( $disabled ) && ( $args['hide_count_zero'] ?? 1 ) ) {
			return '';
		}

		$checked = in_array( $category_id, $args['data_selected'] ?? [] ) && empty( $disabled ) ? 'checked' : '';
		$input   = sprintf(
			'<input name="term_id" type="checkbox" value="%s" %s %s>',
			esc_attr( $category_id ), esc_attr( $checked ),
			$disabled
		);
		$label   = sprintf( '<label for="">%s</label>', wp_kses_post( $category_name ) );
		$count   = sprintf( '<span class="count">%s</span>', esc_html( $count_courses ) );

		$sections = apply_filters(
			'learn-press/filter-courses/course-category/sections',
			[
				'start' => [ 'text_html' => '<div class="lp-course-filter__field">' ],
				'input' => [ 'text_html' => $input ],
				'label' => [ 'text_html' => $label ],
				'count' => [ 'text_html' => $count ],
				'end'   => [ 'text_html' => '</div>' ],
			],
			$category_id,
			$category_name,
			$args
		);

		ob_start();
		Template::instance()->print_sections( $sections );

		return ob_get_clean();
	}

	/**
	 * Get html course tag.
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.3
	 */
	public function html_tag( array $data = [] ): string {
		$content = '';

		try {
			$this->check_param_url_has_lang( $data );
			$params_url      = $data['params_url'] ?? [];
			$data_selected   = $params_url['tag_id'] ?? '';
			$data_selected   = explode( ',', $data_selected );
			$hide_count_zero = $data['hide_count_zero'] ?? 1;
			// Check has in tag page.
			if ( isset( $params_url['page_tag_id_current'] ) &&
				empty( $params_url['tag_id'] ) ) {
				$data_selected[] = $params_url['page_tag_id_current'];
			}

			$terms = get_terms(
				[
					'taxonomy'   => LP_COURSE_TAXONOMY_TAG,
					'hide_empty' => true,
					'count'      => false,
				]
			);

			if ( empty( $terms ) ) {
				return $content;
			}

			foreach ( $terms as $term ) {
				$html_wrapper = [
					'<div class="lp-course-filter__field">' => '</div>',
				];

				$value               = $term->term_id;
				$filter              = new LP_Course_Filter();
				$filter->query_count = true;
				$this->handle_filter_params_before_query( $filter, $params_url );
				$filter->tag_ids = [ $value ];

				$count_courses = 0;
				Courses::get_courses( $filter, $count_courses );
				$disabled = $count_courses > 0 ? '' : 'disabled';
				if ( ! empty( $disabled ) && $hide_count_zero ) {
					continue;
				}
				$checked = in_array( $value, $data_selected ) && empty( $disabled ) ? 'checked' : '';
				$input   = sprintf( '<input name="tag_id" type="checkbox" value="%s" %s %s>', esc_attr( $value ), esc_attr( $checked ), $disabled );
				$label   = sprintf( '<label for="">%s</label>', wp_kses_post( $term->name ) );
				$count   = sprintf( '<span class="count">%s</span>', esc_html( $count_courses ) );

				$sections = apply_filters(
					'learn-press/filter-courses/course-tag/sections',
					[
						'input' => [ 'text_html' => $input ],
						'label' => [ 'text_html' => $label ],
						'count' => [ 'text_html' => $count ],
					],
					$term,
					$data
				);

				ob_start();
				Template::instance()->print_sections( $sections );
				$content .= Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}

			$content = $this->html_item( esc_html__( 'Tags', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html course tag.
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.3
	 */
	public function html_author( array $data = [] ): string {
		$content = '';

		try {
			$this->check_param_url_has_lang( $data );
			$params_url      = $data['params_url'] ?? [];
			$data_selected   = $params_url['c_authors'] ?? '';
			$data_selected   = explode( ',', $data_selected );
			$hide_count_zero = $data['hide_count_zero'] ?? 1;
			$instructors     = get_users(
				array(
					'role__in' => [ LP_TEACHER_ROLE, ADMIN_ROLE ],
					'fields'   => array( 'ID', 'display_name' ),
				)
			);

			foreach ( $instructors as $instructor ) {
				$html_wrapper               = [
					'<div class="lp-course-filter__field">' => '</div>',
				];
				$total_course_of_instructor = 0;

				$filter              = new LP_Course_Filter();
				$filter->query_count = true;
				$filter->only_fields = [ 'DISTINCT(ID)' ];
				$this->handle_filter_params_before_query( $filter, $params_url );
				$filter->post_authors = [ $instructor->ID ];
				Courses::get_courses( $filter, $total_course_of_instructor );

				$value    = $instructor->ID;
				$disabled = $total_course_of_instructor > 0 ? '' : 'disabled';
				if ( ! empty( $disabled ) && $hide_count_zero ) {
					continue;
				}
				$checked = in_array( $value, $data_selected ) && empty( $disabled ) ? 'checked' : '';
				$input   = sprintf( '<input name="c_authors" type="checkbox" value="%s" %s %s>', esc_attr( $value ), esc_attr( $checked ), $disabled );
				$label   = sprintf( '<label for="">%s</label>', esc_html( $instructor->display_name ) );
				$count   = sprintf( '<span class="count">%s</span>', esc_html( $total_course_of_instructor ) );

				$sections = apply_filters(
					'learn-press/filter-courses/author/sections',
					[
						'input' => [ 'text_html' => $input ],
						'label' => [ 'text_html' => $label ],
						'count' => [ 'text_html' => $count ],
					],
					$instructor,
					$total_course_of_instructor,
					$data
				);

				ob_start();
				Template::instance()->print_sections( $sections );
				$content .= Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}

			$content = $this->html_item( esc_html__( 'Author', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html course tag.
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.3
	 */
	public function html_level( array $data = [] ): string {
		$content = '';

		try {
			$this->check_param_url_has_lang( $data );
			$params_url      = $data['params_url'] ?? [];
			$data_selected   = $params_url['c_level'] ?? '';
			$data_selected   = explode( ',', $data_selected );
			$fields          = lp_course_level();
			$hide_count_zero = $data['hide_count_zero'] ?? 1;

			foreach ( $fields as $key => $field ) {
				$html_wrapper = [
					'<div class="lp-course-filter__field">' => '</div>',
				];

				$value = $key;
				if ( empty( $key ) ) {
					$value = 'all';
				}

				$filter = new LP_Course_Filter();
				$this->handle_filter_params_before_query( $filter, $params_url );
				$filter->only_fields = [ 'DISTINCT(ID)' ];
				$filter->query_count = true;
				$filter->levels      = [ $key ];
				$total_courses       = 0;
				Courses::get_courses( $filter, $total_courses );

				$disabled = $total_courses > 0 ? '' : 'disabled';
				if ( ! empty( $disabled ) && $hide_count_zero ) {
					continue;
				}
				$checked = in_array( $value, $data_selected ) && empty( $disabled ) ? 'checked' : '';
				$input   = sprintf(
					'<input name="c_level" type="checkbox" value="%1$s" %2$s %3$s>',
					esc_attr( $value ),
					esc_attr( $checked ),
					esc_attr( $disabled )
				);
				$label   = sprintf( '<label for="">%s</label>', esc_html( $field ) );
				$count   = sprintf( '<span class="count">%s</span>', esc_html( $total_courses ) );

				$sections = apply_filters(
					'learn-press/filter-courses/levels/sections',
					[
						'input' => [ 'text_html' => $input ],
						'label' => [ 'text_html' => $label ],
						'count' => [ 'text_html' => $count ],
					],
					$field,
					$value,
					$data
				);

				ob_start();
				Template::instance()->print_sections( $sections );
				$content .= Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}

			$content = $this->html_item( esc_html__( 'Levels', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html button submit filter.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_btn_submit( array $data = [] ): string {
		return sprintf(
			'<button type="submit" class="course-filter-submit">%s</button>',
			esc_html__( 'Filter', 'learnpress' )
		);
	}

	/**
	 * Get html button reset filter.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_btn_reset( array $data = [] ): string {
		return sprintf(
			'<button class="course-filter-reset">%s</button>',
			esc_html__( 'Reset', 'learnpress' )
		);
	}

	/**
	 * Set params from url for filter.
	 *
	 * @param LP_Course_Filter $filter
	 * @param array $params_url
	 *
	 * @return void
	 */
	public function handle_filter_params_before_query( LP_Course_Filter &$filter, array $params_url = [] ) {
		Courses::handle_params_for_query_courses( $filter, $params_url );

		// Check has in category page.
		if ( isset( $params_url['page_term_id_current'] ) &&
		     empty( $params_url['term_id'] ) ) {
			$filter->term_ids[] = $params_url['page_term_id_current'];
		} // Check has in tag page.
		elseif ( isset( $params_url['page_tag_id_current'] ) &&
		         empty( $params_url['tag_id'] ) ) {
			$filter->tag_ids[] = $params_url['page_tag_id_current'];
		}
	}

	/**
	 * Check param url has lang for multiple lang.
	 *
	 * @param array $data
	 *
	 * @return void
	 * @since 4.2.5.7
	 * @version 1.0.0
	 */
	public function check_param_url_has_lang( array $data = [] ) {
		$params_url = $data['params_url'] ?? [];
		if ( isset( $params_url['lang'] ) ) {
			$_REQUEST['lang'] = sanitize_text_field( $params_url['lang'] );
		}
	}
}
