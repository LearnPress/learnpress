<?php
namespace Elementor;

class LearnPress_Widget_Become_A_Teacher extends Widget_Base {

	public function get_name() {
		return 'learnpress_become_a_teacher';
	}

	public function get_title() {
		return esc_html__( 'Become a teacher', 'learnpress' );
	}

	public function get_icon() {
		return 'eicon-site-logo';
	}

	public function get_categories() {
		return array( 'learnpress' );
	}

	public function render() {
		echo do_shortcode( '[learn_press_become_a_teacher]' );
	}
}
