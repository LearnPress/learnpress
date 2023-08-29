<?php
/**
 * Class InstructorTitleElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course\Sections;

use Elementor\Plugin;
use Elementor\Widget_Heading;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;

class CoursePriceElementor extends Widget_Heading {
	use SingleCourseBaseElementor;
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Course Price', 'learnpress' );
		$this->name     = 'course_price';
		$this->keywords = [ 'course price', 'price' ];
		parent::__construct( $data, $args );
	}

	public function get_categories() {
		return array( 'learnpress_course' );
	}


	protected function register_controls() {
		parent::register_controls();

		$this->update_control(
			'title',
			array(
				'dynamic' => array(
					'default' => Plugin::$instance->dynamic_tags->tag_data_to_tag_text( null, 'course-price' ),
				),
			),
			array(
				'recursive' => true,
			)
		);
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			//$this->before_preview_query();
			if ( Plugin::$instance->editor->is_edit_mode() ) {
				echo 'Course Price';
			}

			parent::render();

			//$this->after_preview_query();

		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}
