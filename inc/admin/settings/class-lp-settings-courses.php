<?php

/**
 * Class LP_Settings_Courses
 */
class LP_Settings_Courses extends LP_Settings_Base {
	function __construct() {
		$this->id   = 'courses';
		$this->text = __( 'Courses', 'learn_press' );

		parent::__construct();
	}

	function _get_sections() {
		$sections = array(
			'general' => __( 'General', 'learn_press' )
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	function save(){
		parent::save();
		$course_permalink = $_POST['learn_press_course_base'];

		if ( $course_permalink == 'custom' ) {
			$course_permalink = trim( $_POST['course_permalink_structure'], '/' );

			if ( '%course_category%' == $course_permalink ) {
				$course_permalink = _x( 'courses', 'slug', 'learn_press' ) . '/' . $course_permalink;
			}

			$course_permalink = '/' . $course_permalink;
		} elseif ( empty( $course_permalink ) ) {
			$course_permalink = false;
		}

		$course_base = untrailingslashit( $course_permalink );

		update_option( 'learn_press_course_base', $course_base );

		$courses_page_id   = learn_press_get_page_id( 'courses' );
		$courses_permalink = ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learn_press' );

		if ( $courses_page_id && trim( $course_base, '/' ) === $courses_permalink ) {
			update_option( 'learn_press_use_verbose_page_rules', 'yes' );
		}else{
			delete_option( 'learn_press_use_verbose_page_rules' );
		}
	}

	function output() {
		$view = learn_press_get_admin_view( 'settings/courses.php' );
		require_once $view;
	}

	function get_settings(){
		return apply_filters(
			'learn_press_courses_settings',
			array(
				array(
					'title'    => __( 'Courses Page', 'learn_press' ),
					'id'       => $this->get_field_name( 'courses_page_id' ),
					'default'  => '',
					'type'     => 'pages-dropdown'
				),
				array(
					'title'    => __( 'Course category base', 'learn_press' ),
					'id'       => $this->get_field_name( 'course_category_base' ),
					'default'  => 'course-category',
					'type'     => 'text'
				),
				array(
					'title'    => __( 'Course tag base', 'learn_press' ),
					'id'       => $this->get_field_name( 'course_tag_base' ),
					'default'  => 'course-tag',
					'type'     => 'text'
				),
				array(
					'title'    => __( 'Review course before publish', 'learn_press' ),
					'desc'		=> __( 'The course need to review by admin before it can be published', 'learn_press' ),
					'id'       => $this->get_field_name( 'required_review' ),
					'default'  => 'yes',
					'type'     => 'checkbox'
				),
				array(
					'title'    => __( 'Enable edit published course', 'learn_press' ),
					'desc'		=> __( 'Allows instructor edit the course that published without review.<br /> If this option is disabled, the course status will be changed to Pending Review when the instructor update course', 'learn_press' ),
					'id'       => $this->get_field_name( 'enable_edit_published' ),
					'default'  => 'yes',
					'type'     => 'checkbox'
				),
				array(
					'title'    => __( 'Course thumbnail', 'learn_press' ),
					'type'     => 'title'
				),
				array(
					'title'    => __( 'Single course', 'learn_press' ),
					'id'       => $this->get_field_name( 'single_course_image_size' ),
					'default'  => array( 800, 450, 'yes' ),
					'type'     => 'image-size'
				),
				array(
					'title'    => __( 'Course thumbnail', 'learn_press' ),
					'id'       => $this->get_field_name( 'course_thumbnail_image_size' ),
					'default'  => array( 400, 250, 'yes' ),
					'type'     => 'image-size'
				),
				array(
					'title'    => __( 'Single course permalink', 'learn_press' ),
					'type'     => 'title'
				),
				array(
					'title'    => __( 'Single course permalink', 'learn_press' ),
					'type'     => 'course-permalink',
					'default' => ''
				),
			)
		);
	}
}

return new LP_Settings_Courses();