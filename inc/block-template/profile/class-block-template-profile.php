<?php

use LearnPress\TemplateHooks\Profile\ProfileTemplate;

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
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lp-profile.js';

	public function render_content_block_template( array $attributes, $inner_content = '' ) {
		$content = '';

		try {
			if ( ! is_user_logged_in() ) {
				ob_start();
				echo ProfileTemplate::instance()->html_login_form();
				$content = ob_get_clean();
				return $content;
			}

			$profile = LP_Profile::instance();
			if ( $profile->get_user()->is_guest() ) {
				return $content;
			}

			if ( $profile->get_user_current()->is_guest()
				&& 'yes' !== LP_Profile::get_option_publish_profile() ) {
				return $content;
			}

			ob_start();
			echo ProfileTemplate::instance()->html_profile( $inner_content );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}
