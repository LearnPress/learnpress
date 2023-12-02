<?php
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins;

use Elementor\Widget_Base;
use LearnPress\Helpers\Config;

class CoursesGrid extends SkinCoursesBase {
	public $lp_el_skin_id = 'courses_grid';
	public $lp_el_skin_title = 'Courses Grid';

	public function controls_on_section_skin( Widget_Base $widget, $args ) {
		$this->parent = $widget;

		Config::instance()->get(
			'courses-grid',
			'elementor/course/skin',
			[ 'CoursesGrid' => $this ]
		);
	}
}
