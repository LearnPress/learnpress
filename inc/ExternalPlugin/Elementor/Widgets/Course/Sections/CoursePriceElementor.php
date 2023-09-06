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
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;

class CoursePriceElementor extends Widget_Heading {
	use SingleCourseBaseElementor;
	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Course Price', 'learnpress' );
		$this->name     = 'course_price';
		$this->keywords = [ 'course price', 'price' ];
		parent::__construct( $data, $args );
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

		$this->start_controls_section(
			'section_price_style',
			array(
				'label' => esc_html__( 'Price Origin', 'learnpress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'origin_price_color',
			array(
				'label'     => esc_html__( 'Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .course-item-price .origin-price' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'origin_price_typography',
				'selector' => '{{WRAPPER}} .course-item-price .origin-price',
			)
		);

		$this->add_responsive_control(
			"origin_padding",
			array(
				'label'      => esc_html__( 'Padding', 'thim-elementor-kit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					"{{WRAPPER}} .course-item-price .origin-price" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

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
