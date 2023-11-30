<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins;

use Elementor\Widget_Base;
use LearnPress\ExternalPlugin\Elementor\LPSkinBase;

class SkinCoursesBase extends LPSkinBase {
	protected function _register_controls_actions() {
		add_action( 'elementor/element/learnpress_list_courses_by_page/section_content/before_section_end', array( $this, 'register_controls_on_section_content' ), 10, 2 );
		//add_action( 'elementor/element/learnpress_list_courses_by_page/section_query/after_section_end', array( $this, 'register_style_sections' ), 10, 2 );
	}

	public function register_controls_on_section_content( Widget_Base $widget, $args ) {
		$this->parent = $widget;
	}

	public function register_style_sections( Widget_Base $widget, $args ) {
		$this->parent = $widget;
	}

	public function render() {
		echo 'Skin Courses Base';

		// Query course on here and call to skill chose
	}
}
