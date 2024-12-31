<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

/**
 * Class Block_Template_Profile_Background_Image
 *
 * Handle register, render block template
 */
class Block_Template_Profile_Background_Image extends Abstract_Block_Template {
	public $slug                          = 'profile-background-image';
	public $name                          = 'learnpress/profile-background-image';
	public $title                         = 'Profile - Background Image (LearnPress)';
	public $description                   = 'Profile Background Image Block Template';
	public $path_html_block_template_file = 'html/profile-background-image.html';
	public $path_template_render_default  = 'profile-background-image.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/profile-background-image.js';

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
			// Display cover image
			ob_start();
			echo ProfileTemplate::instance()->html_cover_image( $userModel );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}
