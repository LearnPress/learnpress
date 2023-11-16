<?php
/**
 * Class FilterCourseElementor
 *
 * @sicne 4.2.5
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Course;

use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use Elementor\Icons_Manager;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use Throwable;

class FilterCourseElementor extends LPElementorWidgetBase {
    public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Filter Course', 'learnpress' );
		$this->name     = 'filter_course';
		$this->keywords = [ 'filter course' ];
		$this->icon     = 'eicon-taxonomy-filter';

		wp_enqueue_script( 'lp-course-filter' );
		parent::__construct( $data, $args );
	}

    /**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->controls = Config::instance()->get(
			'filter-course-el',
			'elementor/course'
		);
		parent::register_controls();
	}

    protected function render() {
        try {
			$settings 	= $this->get_settings_for_display();
			$filter   	= FilterCourseTemplate::instance();
			$extraClass = '';
			if ( empty( $settings['item_filter'] ) ) {
				return;
			}

			if ( $settings['layout'] == 'popup' ) {
				$text_popup = $settings['button_popup'] ?? esc_html__( 'Filter', 'learnpress' );
				$extraClass = 'lp-filter-popup';
				
				echo '<button class="lp-button-popup">';
				if ( ! empty( $settings['icon_popup'] ) ) {
					Icons_Manager::render_icon(
						$settings['icon_popup'],
						array(
							'aria-hidden' => 'true',
							'class'       => 'icon-align-' . esc_attr( $settings['icon_align'] ),
						)
					);
				}
				echo $text_popup ;
				echo self::selected_style_number();
				echo '</button>';
			}

			$html_wrapper = apply_filters(
				'learn-press/filter-courses/sections/wrapper',
				[
					'<form class="lp-form-course-filter '. esc_attr( $extraClass ) .'">' => '</form>',
				],
				$settings
			);
			$sections     = [];
			
			foreach ( $settings['item_filter'] as $field ) {
				if ( is_callable(  array( $filter, 'html_' . $field['item_fields'])  ) ) {
					$sections[ $field['item_fields'] ] = [ 'text_html' => $filter->{'html_' . $field['item_fields']}( $settings ) ];
				}
			}
			
			ob_start();
			Template::instance()->print_sections( $sections );
			echo Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
    }

	protected function selected_style_number(){
		$cat = $tag = $price = $level = $author = $total = 0;

		if( ! empty( $_GET['term_id'] ) ){
			$cat =  count(explode(',',$_GET['term_id']));
		}
		if( ! empty( $_GET['tag_id'] ) ){
			$tag =  count(explode(',',$_GET['tag_id']));
		}
		if( ! empty( $_GET['sort_by'] ) ){
			$price =  count(explode(',',$_GET['sort_by']));
		}
		if( ! empty( $_GET['c_level'] ) ){
			$level =  count(explode(',',$_GET['c_level']));
		}
		if( ! empty( $_GET['c_authors'] ) ){
			$author =  count(explode(',',$_GET['c_authors']));
		}
		$total = $cat + $tag + $price + $level + $author;

		if( $total == 0 ){
			$total = '';
		}

		if( ! empty( $total ) ){
			echo '<span>'. $total .'</span>';
		}
	}
}