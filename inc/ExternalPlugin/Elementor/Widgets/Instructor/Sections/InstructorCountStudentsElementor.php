<?php
/**
 * Class InstructorCountStudentElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections;

use Elementor\Plugin;
use Exception;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\SingleInstructorBaseElementor;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

class InstructorCountStudentsElementor extends SingleInstructorBaseElementor {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Instructor Count Students', 'learnpress' );
		$this->name     = 'instructor_count_students';
		$this->keywords = [ 'instructor', 'count_students' ];
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'count_students', 'elementor/instructor/sections' );
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$instructor = null;
			$settings   = $this->get_settings_for_display();
			if ( ! is_array( $settings ) ) {
				$settings = [];
			}

			$text_default = sprintf( '<span class="instructor-total-students">%s</span>', __( 'Instructor Count Students', 'learnpress' ) );
			$this->detect_instructor_id( $settings, $instructor, $text_default );

			$wrapper = [];
			if ( ! empty( $settings['wrapper_tags'] ) ) {
				foreach ( $settings['wrapper_tags'] as $key => $tag ) {
					$wrapper[ $tag['open_tag'] ?? '' ] = $tag['close_tag'] ?? '';
				}
			}

			echo Template::instance()->nest_elements(
				$wrapper,
				SingleInstructorTemplate::instance()->html_count_students( $instructor )
			);

		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}
