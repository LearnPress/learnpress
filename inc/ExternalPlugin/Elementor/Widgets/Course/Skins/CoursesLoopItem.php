<?php
/**
 * Skin Loop Item for Widget Courses
 * Call list item on plugin Thim Elementor Kit.
 *
 * @since 4.2.5.7
 * @version 1.0.0
 */
namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins;

use Elementor\Plugin;
use Elementor\Widget_Base;
use LearnPress\Helpers\Config;
use LP_Course;

class CoursesLoopItem extends SkinCoursesBase {
	public $lp_el_skin_id    = 'courses_loop_item';
	public $lp_el_skin_title = 'Courses Loop Item';

	public function controls_on_section_skin( Widget_Base $widget, $args ) {
		// Only load on the Admin
		if ( ! is_admin() ) {
			return;
		}

		$this->parent = $widget;

		Config::instance()->get(
			'courses-loop-item',
			'elementor/course/skin',
			[ 'CoursesLoopItem' => $this ]
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
		global $post;
		$post = get_post( $course->get_id() );
		$id   = $settings['lp_el_skin_courses_loop_item_template_id'] ?? 0;

		return Plugin::instance()->frontend->get_builder_content( $id );
	}
}
