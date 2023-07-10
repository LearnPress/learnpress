<?php
/**
 * Class InfoCourseElementor
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

class InfoCourseElementor extends SingleInstructorBaseElementor {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Info Course', 'learnpress' );
		$this->name     = 'info_course';
		$this->keywords = [ 'instructor', 'name' ];
		parent::__construct( $data, $args );
        $this->icon ='eicon-site-identity';
        
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'title', 'elementor/instructor/sections' );
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$info_course = null;
			$settings   = $this->get_settings_for_display();
			if ( ! is_array( $settings ) ) {
				$settings = [];
			}

			$text_default = sprintf( '<ul><li></li><li></li><li></li></ul>', __( 'Info Course', 'learnpress' ) );
			$this->detect_instructor_id( $settings, $info_course, $text_default );

			$wrapper = [];
			if ( ! empty( $settings['wrapper_tags'] ) ) {
				foreach ( $settings['wrapper_tags'] as $key => $tag ) {
					$wrapper[ $tag['open_tag'] ?? '' ] = $tag['close_tag'] ?? '';
				}
			}

			echo Template::instance()->nest_elements(
				$wrapper,
				SingleInstructorTemplate::instance()->html_display_name( $info_course )
			);

		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}