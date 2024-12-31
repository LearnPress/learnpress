<?php

/**
 * Class Block_Template_Profile_Content
 *
 * Handle register, render block template
 */
class Block_Template_Profile_Content extends Abstract_Block_Template {
	public $slug                          = 'profile-content';
	public $name                          = 'learnpress/profile-content';
	public $title                         = 'Profile - Content (LearnPress)';
	public $description                   = 'Profile Content Block Template';
	public $path_html_block_template_file = 'html/profile-content.html';
	public $path_template_render_default  = 'profile/content.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/profile-content.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			ob_start();
			$profile  = LP_Profile::instance();
			$template = new LP_Template_Profile();
			$template->content( $profile );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}
