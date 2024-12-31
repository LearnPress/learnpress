<?php

/**
 * Class Block_Template_Profile
 *
 * Handle register, render block template
 */
class Block_Template_Profile extends Abstract_Block_Layout {
	public $slug                          = 'lp-profile';
	public $name                          = 'learnpress/lp-profile';
	public $title                         = 'Profile (LearnPress)';
	public $description                   = 'Profile Block Template';
	public $path_html_block_template_file = 'html/lp-profile.html';
	public $path_template_render_default  = 'profile/lp-profile.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lp-profile.js';
}
