<?php

/**
 * Class Block_Template_Profile_Background_Image
 *
 * Handle register, render block template
 */
class Block_Template_Profile_Avatar extends Abstract_Block_Template {
	public $slug                          = 'profile-avatar';
	public $name                          = 'learnpress/profile-avatar';
	public $title                         = 'Profile - Avatar (LearnPress)';
	public $description                   = 'Profile Avatar Block Template';
	public $path_html_block_template_file = 'html/profile-avatar.html';
	public $path_template_render_default  = 'profile/avatar.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/profile-avatar.js';
}
