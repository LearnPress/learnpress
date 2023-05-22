<?php

namespace LearnPress\Helpers\TemplateHooks;

use LearnPress\Helpers\Template;

class InstructorList {

	public $template;

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		$this->template = Template::instance();
		add_action( 'learnpress/layout/instructor-item/items', array( $this, 'instructor_items' ) );
		add_action( 'learnpress/layout/instructor-item/content', array( $this, 'instructor_item_content' ) );
		add_action( 'learnpress/layout/instructor-item/info', array( $this, 'instructor_item_info' ) );
	}

	public function instructor_items( $data ) {
		$sections = apply_filters(
			'learnpress/instructor-item/items',
			array(
				'instructor-list/instructor-item/avatar.php',
				'instructor-list/instructor-item/content.php',
				'instructor-list/instructor-item/view-profile.php',
			)
		);

		$this->template->get_frontend_templates( $sections, compact( 'data' ) );
	}

	public function instructor_item_content( $data ) {
		$sections = apply_filters(
			'learnpress/instructor-item/content',
			array(
				'instructor-list/instructor-item/content/name.php',
				'instructor-list/instructor-item/content/info.php',
			)
		);

		$this->template->get_frontend_templates( $sections, compact( 'data' ) );
	}

	public function instructor_item_info( $data ) {
		$sections = apply_filters(
			'learnpress/instructor-item/info',
			array(
				'instructor-list/instructor-item/content/course-total.php',
				'instructor-list/instructor-item/content/student-total.php',
			)
		);

		$this->template->get_frontend_templates( $sections, compact( 'data' ) );
	}
}

InstructorList::instance();
