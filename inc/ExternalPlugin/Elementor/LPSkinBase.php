<?php
namespace LearnPress\ExternalPlugin\Elementor;

use Elementor\Skin_Base;

abstract class LPSkinBase extends Skin_Base {
	/**
	 * @var string
	 */
	public $lp_el_skin_id = '';
	/**
	 * @var string
	 */
	public $lp_el_skin_title = '';

	public function get_id() {
		return 'lp_el_skin_' . $this->lp_el_skin_id;
	}

	public function get_title() {
		return $this->lp_el_skin_title;
	}

	// For hook add controls of each skin type
	protected function _register_controls_actions() {}

	public function render() {
		// Render your skin here
	}
}
