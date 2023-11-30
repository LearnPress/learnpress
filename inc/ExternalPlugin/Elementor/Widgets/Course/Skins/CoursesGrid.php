<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class CoursesGrid extends SkinCoursesBase {
	public $lp_el_skin_id = 'courses_grid';
	public $lp_el_skin_title = 'Courses Grid';

	public function register_controls_on_section_content( Widget_Base $widget, $args ) {
		$this->parent = $widget;
		//parent::register_controls_on_section_content( $widget, $args );

		// remove control columns.
		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'learnpress' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'      => array(
					'{{WRAPPER}}' => '--lp-el-list-courses-grid-columns: {{VALUE}}',
				),
			)
		);
	}
}
