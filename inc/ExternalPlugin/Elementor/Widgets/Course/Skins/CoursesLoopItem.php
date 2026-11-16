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
	 * Sign template id, generated on server only.
	 *
	 * @param int|string $template_id
	 *
	 * @return string
	 */
	public static function sign_template_id( $template_id ): string {
		return wp_hash( 'lp_el_loop_item_template|' . absint( $template_id ) );
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
		$id  = absint( $settings['lp_el_skin_courses_loop_item_template_id'] ?? 0 );
		$sig = (string) ( $settings['lp_el_template_id_sig'] ?? '' );
		if ( ! $id || '' === $sig || ! hash_equals( self::sign_template_id( $id ), $sig ) ) {
			return '';
		}

		// Only render published, non password-protected courses.
		if ( 'publish' !== get_post_status( $course->get_id() ) || post_password_required( $course->get_id() ) ) {
			return '';
		}

		global $post;
		$post = get_post( $course->get_id() );

		return Plugin::instance()->frontend->get_builder_content( $id );
	}
}
