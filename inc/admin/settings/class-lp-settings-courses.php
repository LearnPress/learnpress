<?php

/**
 * Class LP_Settings_Courses
 */
class LP_Settings_Courses extends LP_Settings_Base {
	public function __construct() {
		$this->id   = 'courses';
		$this->text = __( 'Courses', 'learnpress' );

		parent::__construct();
	}

	public function _get_sections() {
		$sections = array(
			'general' => array(
				'id'    => 'general',
				'title' => __( 'General', 'learnpress' )
			)
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	public function save() {
		parent::save();
		$course_permalink = $_POST['learn_press_course_base'];
		update_option( 'learn_press_course_base_type', $course_permalink );
		if ( $course_permalink == 'custom' ) {
			$course_permalink = trim( $_POST['course_permalink_structure'], '/' );

			if ( '%course_category%' == $course_permalink ) {
				$course_permalink = _x( 'courses', 'slug', 'learnpress' ) . '/' . $course_permalink;
			}

			$course_permalink = '/' . $course_permalink;
		} elseif ( empty( $course_permalink ) ) {
			$course_permalink = false;
		}

		$course_base = untrailingslashit( $course_permalink );

		update_option( 'learn_press_course_base', $course_base );

		$courses_page_id   = learn_press_get_page_id( 'courses' );
		$courses_permalink = ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learnpress' );

		if ( $courses_page_id && trim( $course_base, '/' ) === $courses_permalink ) {
			update_option( 'learn_press_use_verbose_page_rules', 'yes' );
		} else {
			delete_option( 'learn_press_use_verbose_page_rules' );
		}
	}

	public function output() {
		$view = learn_press_get_admin_view( 'settings/courses.php' );
		require_once $view;
	}

	public function get_settings() {
		return apply_filters(
			'learn_press_courses_settings',
			array(
				array(
					'title'   => __( 'Courses Page', 'learnpress' ),
					'id'      => $this->get_field_name( 'courses_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title'   => __( 'Course category base', 'learnpress' ),
					'id'      => $this->get_field_name( 'course_category_base' ),
					'default' => 'course-category',
					'type'    => 'text'
				),
				array(
					'title'   => __( 'Course tag base', 'learnpress' ),
					'id'      => $this->get_field_name( 'course_tag_base' ),
					'default' => 'course-tag',
					'type'    => 'text'
				),
				array(
					'title'   => __( 'Review course before publish', 'learnpress' ),
					'desc'    => __( 'The course needs to be reviewed by admin before it can be published.', 'learnpress' ),
					'id'      => $this->get_field_name( 'required_review' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Enable edit published course', 'learnpress' ),
					'desc'    => __( 'Allow instructors to edit the course that are published without review.<br /> If this option is disabled, the course status will be changed to Pending Review when the instructor update course.', 'learnpress' ),
					'id'      => $this->get_field_name( 'enable_edit_published' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Hide list of question in quiz', 'learnpress' ),
					'desc'    => __( 'Hide list of question in quiz<br /> If this option is enabled, the course status will be changed to Pending Review when the instructor update course', 'learnpress' ),
					'id'      => $this->get_field_name( 'disable_question_in_quiz' ),
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title' => __( 'Archive', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Courses limit', 'learnpress' ),
					'desc'    => __( 'Number of courses displayed per page.', 'learnpress' ),
					'id'      => $this->get_field_name( 'archive_course_limit' ),
					'default' => '10',
					'type'    => 'number'
				),
				array(
					'title' => __( 'Course thumbnail', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Single course', 'learnpress' ),
					'id'      => $this->get_field_name( 'single_course_image_size' ),
					'default' => array( 800, 450, 'yes' ),
					'type'    => 'image-size'
				),
				array(
					'title'   => __( 'Course thumbnail', 'learnpress' ),
					'id'      => $this->get_field_name( 'course_thumbnail_image_size' ),
					'default' => array( 400, 250, 'yes' ),
					'type'    => 'image-size'
				),
				array(
					'title' => __( 'Single course permalink', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Single course permalink', 'learnpress' ),
					'type'    => 'course-permalink',
					'default' => ''
				)/*,
				array(
					'title'   => __( 'Auto navigation item', 'learnpress' ),
					'type'    => 'auto_nav_course_item',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => __( 'Navigate to next item after user complete', 'learnpress' )
				)*/
			)
		);
	}
}

return new LP_Settings_Courses();