<?php
/**
 * Template hooks Archive Package.
 *
 * @since 4.2.3.2
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Template;
use LP_Course;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Request;
use Throwable;

class FilterCourseTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/filter-courses/layout', [ $this, 'sections' ] );
		//add_action( 'wp_head', [ $this, 'add_internal_scripts_to_head' ] );
	}

	/**
	 * Sections of template filter courses.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function sections( array $data = [] ) {
		wp_enqueue_script( 'lp-course-filter' );
		ob_start();
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
			} elseif ( is_string( $data['fields'] ) ) {
				$data['fields'] = explode( ',', $data['fields'] );
			}

			if ( ! is_array( $data['fields'] ) ) {
				throw new Exception( 'Fields must be array' );
			}

			$data = apply_filters( 'learn-press/filter-courses/data', $data );

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
				}
			}
			Template::instance()->print_sections( $sections );
			$content = ob_get_clean();
			echo Template::instance()->nest_elements( $html_wrapper, $content );
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
		ob_start();
		try {
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
				]
			);
			Template::instance()->print_sections( $sections );
			$content = Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
		} catch ( Throwable $e ) {
			ob_end_clean();
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

			$value    = LP_Request::get_param( 'c_search' );
			$value    = isset( $data['params_url'] ) ? ( $data['params_url']['c_search'] ?? $value ) : $value;
			$content  = sprintf(
				'<input type="text" name="c_search" placeholder="%s" value="%s" class="%s" data-search-suggest="%d">',
				__( 'Search Course', 'learnpress' ),
				$value,
				'lp-course-filter-search',
				$data['search_suggestion'] ?? 1
			);
			$content .= '<span class="lp-loading-circle hide"></span>';
			$content  = Template::instance()->nest_elements( $html_wrapper, $content );
			$content .= '<div class="lp-course-filter-search-result"></div>';
			$content  = $this->html_item( esc_html__( 'Search', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
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
	 */
	public function html_price( array $data = [] ): string {
		$content = '';
		ob_start();
		try {
			$data_selected = LP_Request::get_param( 'sort_by' );
			$data_selected = isset( $data['params_url'] ) ? ( $data['params_url']['sort_by'] ?? $data_selected ) : $data_selected;
			$data_selected = explode( ',', $data_selected );

			// Get number courses free
			$filter_courses_free              = new LP_Course_Filter();
			$filter_courses_free->query_count = true;
			$filter_courses_free->sort_by     = [ 'on_free' ];
			$count_courses_free               = 0;
			LP_Course::get_courses( $filter_courses_free, $count_courses_free );

			// Get number courses has price
			$filter_courses_price              = new LP_Course_Filter();
			$filter_courses_price->query_count = true;
			$filter_courses_price->sort_by     = [ 'on_paid' ];
			$count_courses_paid                = 0;
			LP_Course::get_courses( $filter_courses_price, $count_courses_paid );

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

				$value   = "on_{$key}";
				$checked = in_array( $value, $data_selected ) ? 'checked' : '';
				$input   = sprintf( '<input name="sort_by" type="checkbox" value="%s" %s>', esc_attr( $value ), esc_attr( $checked ) );
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
				$content_item = ob_get_clean();
				echo Template::instance()->nest_elements( $html_wrapper, $content_item );
			}

			$content = ob_get_clean();
			$content = $this->html_item( esc_html__( 'Price', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html course category.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_category( array $data = [] ): string {
		$content = '';
		ob_start();
		try {
			$data_selected = LP_Request::get_param( 'term_id' );
			$data_selected = isset( $data['params_url'] ) ? ( $data['params_url']['term_id'] ?? $data_selected ) : $data_selected;
			$data_selected = explode( ',', $data_selected );
			$terms         = get_terms(
				'course_category',
				array(
					'hide_empty' => false,
				)
			);

			foreach ( $terms as $term ) {
				$html_wrapper = [
					'<div class="lp-course-filter__field">' => '</div>',
				];

				$value   = $term->term_id;
				$checked = in_array( $value, $data_selected ) ? 'checked' : '';
				$input   = sprintf( '<input name="term_id" type="checkbox" value="%s" %s>', esc_attr( $value ), esc_attr( $checked ) );
				$label   = sprintf( '<label for="">%s</label>', wp_kses_post( $term->name ) );
				$count   = sprintf( '<span class="count">%s</span>', esc_html( $term->count ) );

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
				$content_item = ob_get_clean();
				echo Template::instance()->nest_elements( $html_wrapper, $content_item );
			}

			$content = ob_get_clean();
			$content = $this->html_item( esc_html__( 'Categories', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
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
	 */
	public function html_tag( array $data = [] ): string {
		$content = '';
		ob_start();
		try {
			$data_selected = LP_Request::get_param( 'tag_id' );
			$data_selected = isset( $data['params_url'] ) ? ( $data['params_url']['tag_id'] ?? $data_selected ) : $data_selected;
			$data_selected = explode( ',', $data_selected );
			$terms         = get_terms(
				'course_tag',
				array(
					'hide_empty' => false,
				)
			);

			foreach ( $terms as $term ) {
				$html_wrapper = [
					'<div class="lp-course-filter__field">' => '</div>',
				];

				$value   = $term->term_id;
				$checked = in_array( $value, $data_selected ) ? 'checked' : '';
				$input   = sprintf( '<input name="tag_id" type="checkbox" value="%s" %s>', esc_attr( $value ), esc_attr( $checked ) );
				$label   = sprintf( '<label for="">%s</label>', wp_kses_post( $term->name ) );
				$count   = sprintf( '<span class="count">%s</span>', esc_html( $term->count ) );

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
				$content_item = ob_get_clean();
				echo Template::instance()->nest_elements( $html_wrapper, $content_item );
			}

			$content = ob_get_clean();
			$content = $this->html_item( esc_html__( 'Tags', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
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
	 */
	public function html_author( array $data = [] ): string {
		$content = '';
		ob_start();
		try {
			$data_selected = LP_Request::get_param( 'c_authors' );
			$data_selected = isset( $data['params_url'] ) ? ( $data['params_url']['c_authors'] ?? $data_selected ) : $data_selected;
			$data_selected = explode( ',', $data_selected );
			$instructors   = get_users(
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

				$filter = LP_Course_DB::getInstance()->count_courses_of_author( $instructor->ID, [ 'publish' ] );
				LP_Course::get_courses( $filter, $total_course_of_instructor );

				$value   = $instructor->ID;
				$checked = in_array( $value, $data_selected ) ? 'checked' : '';
				$input   = sprintf( '<input name="c_authors" type="checkbox" value="%s" %s>', esc_attr( $value ), esc_attr( $checked ) );
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
				$content_item = ob_get_clean();
				echo Template::instance()->nest_elements( $html_wrapper, $content_item );
			}

			$content = ob_get_clean();
			$content = $this->html_item( esc_html__( 'Author', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
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
	 */
	public function html_level( array $data = [] ): string {
		$content = '';
		ob_start();
		try {
			$data_selected = LP_Request::get_param( 'c_level' );
			$data_selected = isset( $data['params_url'] ) ? ( $data['params_url']['c_level'] ?? $data_selected ) : $data_selected;
			$data_selected = explode( ',', $data_selected );
			$fields        = lp_course_level();

			foreach ( $fields as $key => $field ) {
				$html_wrapper = [
					'<div class="lp-course-filter__field">' => '</div>',
				];

				$value = $key;
				if ( empty( $key ) ) {
					$value = 'all';
				}

				$filter              = new LP_Course_Filter();
				$filter->only_fields = [ 'ID' ];
				$filter->query_count = true;
				$filter->levels      = [ $key ];
				$total_courses       = 0;
				LP_Course::get_courses( $filter, $total_courses );

				$checked = in_array( $value, $data_selected ) ? 'checked' : '';
				$input   = sprintf( '<input name="c_level" type="checkbox" value="%s" %s>', esc_attr( $value ), esc_attr( $checked ) );
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
				$content_item = ob_get_clean();
				echo Template::instance()->nest_elements( $html_wrapper, $content_item );
			}

			$content = ob_get_clean();
			$content = $this->html_item( esc_html__( 'Levels', 'learnpress' ), $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
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
}
