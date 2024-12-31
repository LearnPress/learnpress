<?php

/**
 * Class Block_Template_Profile_Sidebar
 *
 * Handle register, render block template
 */
class Block_Template_Profile_Sidebar extends Abstract_Block_Template {
	public $slug                          = 'profile-sidebar';
	public $name                          = 'learnpress/profile-sidebar';
	public $title                         = 'Profile - Background Image (LearnPress)';
	public $description                   = 'Profile Background Image Block Template';
	public $path_html_block_template_file = 'html/profile-sidebar.html';
	public $path_template_render_default  = 'profile/sidebar/sidebar.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/profile-sidebar.js';
}
