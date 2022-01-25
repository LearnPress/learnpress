<?php
namespace Elementor;

class LP_Elementor_Widget_Become_A_Teacher extends Widget_Base {

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

	public function get_categories() {
		return array( 'learnpress' );
	}

	public function get_help_url() {
		return '';
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

		$this->register_controls_style();
	}

	protected function register_controls_style() {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => esc_html__( 'Title', 'learnpress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'title_align',
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
					'{{WRAPPER}} #learn-press-become-teacher-form h3' => 'text-align: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementorkits' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form h3' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} #learn-press-become-teacher-form h3',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'title_shadow',
				'selector' => '{{WRAPPER}} #learn-press-become-teacher-form h3',
			)
		);

		$this->add_responsive_control(
			'title_spacing',
			array(
				'label'     => esc_html__( 'Spacing', 'thim-elementorkits' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form h3' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

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

		$this->start_controls_section(
			'section_style_form',
			array(
				'label' => esc_html__( 'Form', 'learnpress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'row_gap',
			array(
				'label'     => esc_html__( 'Rows Gap', 'learnpress' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 10,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .become-teacher-fields .form-field' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .become-teacher-fields' => 'margin-bottom: -{{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_label',
			array(
				'label'     => esc_html__( 'Label', 'learnpress' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'label_spacing',
			array(
				'label'     => esc_html__( 'Spacing', 'learnpress' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors' => array(
					'body {{WRAPPER}} .become-teacher-fields .form-field > label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => esc_html__( 'Text Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .become-teacher-fields .form-field > label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .become-teacher-fields .form-field > label',
			)
		);

		$this->add_control(
			'field_label',
			array(
				'label'     => esc_html__( 'Field', 'learnpress' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'field_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .become-teacher-fields .form-field input, {{WRAPPER}} .become-teacher-fields .form-field textarea' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'field_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .become-teacher-fields .form-field input, {{WRAPPER}} .become-teacher-fields .form-field textarea' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'        => 'field_border',
				'label'       => esc_html__( 'Border', 'learnpress' ),
				'placeholder' => '1px',
				'default'     => '1px',
				'selector'    => '{{WRAPPER}} .become-teacher-fields .form-field input, {{WRAPPER}} .become-teacher-fields .form-field textarea',
			)
		);

		$this->add_control(
			'field_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'learnpress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .become-teacher-fields .form-field input, {{WRAPPER}} .become-teacher-fields .form-field textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			array(
				'label' => esc_html__( 'Buttons', 'learnpress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_spacing',
			array(
				'label'     => esc_html__( 'Spacing', 'learnpress' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors' => array(
					'body {{WRAPPER}} #learn-press-become-teacher-form button' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} #learn-press-become-teacher-form button',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} #learn-press-become-teacher-form button',
				'exclude'  => array(
					'color',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'learnpress' ),
			)
		);

		$this->add_control(
			'button_background_color',
			array(
				'label'     => esc_html__( 'Background Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'button_border_border!' => '',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => __( 'Hover', 'elementor-pro' ),
			)
		);

		$this->add_control(
			'button_background_color_hover',
			array(
				'label'     => esc_html__( 'Background Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color_hover',
			array(
				'label'     => esc_html__( 'Text Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color', 'learnpress' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button:hover' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'button_border_border!' => '',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'learnpress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'button_text_padding',
			array(
				'label'      => esc_html__( 'Text Padding', 'learnpress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} #learn-press-become-teacher-form button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_message',
			array(
				'label' => esc_html__( 'Message', 'learnpress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'message_border',
				'selector' => '{{WRAPPER}} .learn-press-message',
				'exclude'  => array( 'color' ),
			)
		);

		$this->add_control(
			'message_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'thim-elementorkits' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .learn-press-message' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .learn-press-message::before, {{WRAPPER}} .learn-press-message::after' => 'background: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'message_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'thim-elementorkits' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .learn-press-message' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'message_color',
			array(
				'label'     => esc_html__( 'Color', 'thim-elementorkits' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .learn-press-message' => 'color: {{VALUE}}',
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
