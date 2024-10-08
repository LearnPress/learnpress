<?php
/**
 * Class ProfileTemplate.
 *
 * @since 4.2.7.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use Throwable;

class ProfileTemplate {
	use Singleton;

	/**
	 * ProfileTemplate constructor.
	 */
	public function init() {
	}

	/**
	 * HTML upload cover image.
	 *
	 * @param UserModel $user
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_upload_cover_image( UserModel $user ): string {
		$html = '';

		try {
			$cover_image_url = $user->get_cover_image_url();
			$html_area_img = sprintf(
				'<div><span class="lp-icon-plus"></span>%s</div>',
				__( 'upload', 'learnpress' )
			);
			if ( ! empty( $cover_image_url ) ) {
				$html_area_img = sprintf(
					'<img src="%s" class="" alt="%s" />',
					$cover_image_url,
					__( 'Cover Image', 'learnpress' )
				);
			}

			$section_img = [
				'wrapper' => '<div class="lp-user-cover-image__display">',
				'image'   => $html_area_img,
				'wrapper_end' => '</div>',
			];

			$section_btn = [
				'wrapper'     => '<div class="lp-user-cover-image__buttons">',
				'input_file'  => '<input id="lp-cover-image-file" type="file" name="lp-cover-image" accept="image/png, image/jpeg, image/webp" hidden />',
				'choose_file' => '<button id="lp-choose-cover-image" class="lp-button">' . __( 'Upload', 'learnpress' ) . '</button>',
				'save_btn'    => '<button id="lp-save-cover-image" class="lp-button">' . __( 'Save', 'learnpress' ) . '</button>',
				'remove'      => '<button id="lp-remove-cover-image" class="lp-button">' . __( 'Remove', 'learnpress' ) . '</button>',
				'wrapper_end' => '</div>',
			];

			$section = apply_filters(
				'learn-press/profile/html-upload-cover-image',
				[
					'wrapper'     => '<div class="lp-user-cover-image">',
					'title'       => __( 'Cover Image', 'learnpress' ),
					'image'       => Template::combine_components( $section_img ),
					'buttons'     => Template::combine_components( $section_btn ),
					'wrapper_end' => '</div>',
				],
				$user
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}

		return $html;
	}
}
