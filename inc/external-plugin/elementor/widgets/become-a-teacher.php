<?php
namespace Elementor;

class LP_Elementor_Widget_Become_A_Teacher extends LP_Elementor_Widget_Base {

	public function get_name() {
		return 'learnpress_become_a_teacher';
	}

	public function get_title() {
		return esc_html__( 'Become a teacher', 'learnpress' );
	}

	public function get_keywords() {
		return array( 'learnpress', 'teacher', 'become' );
	}

	public function get_icon() {
		return 'eicon-site-logo';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'learnpress' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'learnpress' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Become a teacher', 'learnpress' ),
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Description', 'learnpress' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Fill in your information and send us to become a teacher.', 'learnpress' ),
			)
		);

		$this->add_control(
			'submit_button_text',
			array(
				'label'   => esc_html__( 'Button text', 'learnpress' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Submit', 'learnpress' ),
			)
		);

		$this->add_control(
			'submit_button_process_text',
			array(
				'label'   => esc_html__( 'Button Processing text', 'learnpress' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Processing', 'learnpress' ),
			)
		);

		$this->end_controls_section();

		$this->register_control_style_form_title( '#learn-press-become-teacher-form h3' );
		$this->register_controls_style();
		$this->register_control_style_form_field( '.become-teacher-fields', '.form-field' );
		$this->register_control_style_form_button( '.become-teacher-form button[type="submit"]' );
		$this->register_control_style_message();
	}

	protected function register_controls_style() {

		$this->start_controls_section(
			'section_style_description',
			array(
				'label' => esc_html__( 'Description', 'learnpress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'description_align',
			array(
				'label'     => esc_html__( 'Alignment', 'thim-elementorkits' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'thim-elementorkits' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'thim-elementorkits' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'thim-elementorkits' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form .become-teacher-form__description' => 'text-align: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementorkits' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form .become-teacher-form__description' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} #learn-press-become-teacher-form .become-teacher-form__description',
			)
		);

		$this->add_responsive_control(
			'description_spacing',
			array(
				'label'     => esc_html__( 'Spacing', 'thim-elementorkits' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form .become-teacher-form__description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[learn_press_become_teacher_form title="' . $settings['title'] . '" description="' . $settings['description'] . '" submit_button_text="' . esc_html( $settings['submit_button_text'] ) . '" submit_button_process_text="' . $settings['submit_button_process_text'] . '"]' );
	}
}
