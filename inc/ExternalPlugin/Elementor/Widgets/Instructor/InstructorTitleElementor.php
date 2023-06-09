<?php
/**
 * Class InstructorTitleElementor
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Instructor;

use Elementor\Controls_Manager;
use Elementor\Frontend;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Exception;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

class InstructorTitleElementor extends LPElementorWidgetBase {
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Instructor Title', 'learnpress' );
		$this->name     = 'instructor_title';
		$this->keywords = [ 'instructor', 'title', 'instructor title' ];
		parent::__construct( $data, $args );
	}

	public function get_categories() {
		return array( 'learnpress_instructor' );
	}

	protected function register_controls() {
		$this->controls = Config::instance()->get( 'title', 'elementor/instructor' );
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$settings = $this->get_settings_for_display();

			/**
			 * Get instructor id
			 *
			 * If is page single instructor, will be get instructor id from query var
			 * If is set instructor id in setting widget, will be get instructor id from widget
			 */
			//$instructor_id = get_query_var( 'instructor' );
			$instructor_id = 1;
			$instructor    = learn_press_get_user( $instructor_id );
			if ( ! $instructor ) {
				throw new Exception( __( 'Instructor not found', 'learnpress' ) );
			}

			$wrapper = [];
			if ( ! empty( $settings['wrapper_tags'] ) ) {
				foreach ( $settings['wrapper_tags'] as $key => $tag ) {
					$wrapper[ $tag['open_tag'] ?? '' ] = $tag['close_tag'] ?? '';
				}
			}

			echo Template::instance()->nest_elements(
				$wrapper,
				SingleInstructorTemplate::instance()->html_display_name( $instructor )
			);

		} catch ( \Throwable $e ) {
			echo $e->getMessage();
		}
	}
}
