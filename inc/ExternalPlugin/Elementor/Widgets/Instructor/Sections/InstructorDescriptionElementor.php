<?php
/**
 * Class InstructorTitleElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections;

use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\SingleInstructorBaseElementor;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

class InstructorDescriptionElementor extends SingleInstructorBaseElementor {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Instructor Description', 'learnpress' );
		$this->name     = 'instructor_description';
		$this->keywords = [ 'description' ];
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'description', 'elementor/instructor/sections' );
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

			$text_default = sprintf( '<p class="instructor-description">%s</p>', __( 'Instructor Description', 'learnpress' ) );
			$this->detect_instructor_id( $settings, $instructor, $text_default );

			$wrapper = [];
			if ( ! empty( $settings['wrapper_tags'] ) ) {
				foreach ( $settings['wrapper_tags'] as $key => $tag ) {
					$wrapper[ $tag['open_tag'] ?? '' ] = $tag['close_tag'] ?? '';
				}
			}

			echo Template::instance()->nest_elements(
				$wrapper,
				SingleInstructorTemplate::instance()->html_description( $instructor )
			);

		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}
