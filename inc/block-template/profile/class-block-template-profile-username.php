<?php

use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

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
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/profile-username.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$profile = LP_Profile::instance();
			if ( $profile->get_user()->is_guest() ) {
				return;
			}

			if ( $profile->get_user_current()->is_guest()
				&& 'yes' !== LP_Profile::get_option_publish_profile() ) {
				return;
			}

			$user      = $profile->get_user();
			$userModel = UserModel::find( $user->get_id(), true );
			ob_start();
			echo ProfileTemplate::instance()->html_username( $userModel );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}
