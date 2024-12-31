<?php

/**
 * Class Block_Template_Profile_Username
 *
 * Handle register, render block template
 */
class Block_Template_Profile_Username extends Abstract_Block_Template {
	public $slug                          = 'profile-username';
	public $name                          = 'learnpress/profile-username';
	public $title                         = 'Profile - Username (LearnPress)';
	public $description                   = 'Profile Username Block Template';
	public $path_html_block_template_file = 'html/profile-username.html';
	public $path_template_render_default  = 'profile/header/user-name.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/profile-username.js';
}
