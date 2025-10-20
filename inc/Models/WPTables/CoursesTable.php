<?php

namespace LearnPress\Models\WPTables;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Abstract_Post_Type;
use LP_Helper;
use WP_List_Table;
use WP_Posts_List_Table;

/**
 * LearnPress Courses Table class.
 */

class CoursesTable extends WP_Posts_List_Table {
	/**
	 * Get the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = parent::get_columns();

		unset( $columns['comments'] );

		$columns = Template::insert_value_to_position_array(
			$columns,
			'before',
			'title',
			'thumbnail',
			esc_html__( 'Thumbnail', 'learnpress' )
		);

		$columns = Template::insert_value_to_position_array(
			$columns,
			'after',
			'author',
			'curriculum',
			esc_html__( 'Curriculum', 'learnpress' )
		);

		$columns = Template::insert_value_to_position_array(
			$columns,
			'after',
			'curriculum',
			'student',
			esc_html__( 'Student', 'learnpress' )
		);

		$columns = Template::insert_value_to_position_array(
			$columns,
			'after',
			'student',
			'price',
			esc_html__( 'Price', 'learnpress' )
		);

		return $columns;
	}

	/**
	 * Set columns can be sortable query
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns           = parent::get_sortable_columns();
		$sortable_columns['author'] = array( 'author', 'asc' );
		$sortable_columns['price']  = array( 'price', 'desc' );
		return $sortable_columns;
	}

	/**
	 * Column thumbnail
	 * @use WP_List_Table::single_row_columns
	 * call_user_func( array( $this, 'column_' . $column_name ), $item )
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_thumbnail( $post ) {
		$courseModel = CourseModel::find( $post->ID, true );
		if ( ! $courseModel ) {
			return;
		}

		$singleCourseTemplate = SingleCourseTemplate::instance();
		echo $singleCourseTemplate->html_image( $courseModel );
	}

	/**
	 * Column author
	 * @use WP_List_Table::single_row_columns
	 * call_user_func( array( $this, 'column_' . $column_name ), $item )
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_author( $post ) {
		LP_Abstract_Post_Type::column_author( $post );
	}

	/**
	 * Column Curriculum
	 * @use WP_List_Table::single_row_columns
	 * call_user_func( array( $this, 'column_' . $column_name ), $item )
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_curriculum( $post ) {
		$courseModel = CourseModel::find( $post->ID, true );
		if ( ! $courseModel ) {
			return;
		}

		$count_sections = $courseModel->get_total_sections();

		$html_count_item      = '';
		$item_types_of_course = CourseModel::item_types_support();
		foreach ( $item_types_of_course as $type ) {
			$count_items      = $courseModel->count_items( $type );
			$html_count_item .= sprintf(
				'<div><strong>%d</strong> %s</div>',
				$count_items,
				LP_Helper::get_i18n_string_plural( $count_items, $type, false )
			);
		}

		$section = [
			'count_section' => sprintf(
				'<div>%d %s</div>',
				$count_sections,
				_n( 'Section', 'Sections', $count_sections, 'learnpress' )
			),
			'count_item'    => $html_count_item,
		];

		echo Template::combine_components( $section );
	}

	/**
	 * Column count student
	 * @use WP_List_Table::single_row_columns
	 * call_user_func( array( $this, 'column_' . $column_name ), $item )
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_student( $post ) {
		$courseModel = CourseModel::find( $post->ID, true );
		if ( ! $courseModel ) {
			return;
		}

		printf(
			'<span class="lp-label-counter">%d</span>',
			$courseModel->count_students()
		);
	}

	/**
	 * Column count student
	 * @use WP_List_Table::single_row_columns
	 * call_user_func( array( $this, 'column_' . $column_name ), $item )
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function column_price( $post ) {
		$courseModel = CourseModel::find( $post->ID, true );
		if ( ! $courseModel ) {
			return;
		}

		echo SingleCourseTemplate::instance()->html_price( $courseModel );
	}
}
