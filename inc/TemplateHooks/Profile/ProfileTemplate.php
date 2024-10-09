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
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function init() {
	}

	/**
	 * @param UserModel $user
	 *
	 * @return string
	 */
	public function html_cover_image( UserModel $user ): string {
		$html = '';

		try {
			$cover_image_url = $user->get_cover_image_url();

			$html = sprintf(
				'<div class="lp-user-cover-image_background" style="%s;%s;%s" data-height="%s"></div>',
				sprintf( 'background: url(%s) no-repeat', $cover_image_url ),
				'background-size: contain',
				! empty( $cover_image_url ) ? 'height: 250px;' : '',
				'250px' // Get from settings
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
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

			$hide_img_preview = empty( $cover_image_url ) ? 'style="display: none"' : '';
			$hide_img_empty   = ! empty( $cover_image_url ) ? 'style="display: none"' : '';
			$html_img_empty   = sprintf(
				'<div class="lp-cover-image-empty" %s><span class="lp-icon-plus"></span>%s</div>',
				$hide_img_empty,
				__( 'upload', 'learnpress' ),
			);

			$html_img_preview = sprintf(
				'<img class="lp-cover-image-preview" src="%s" alt="%s" %s />',
				$cover_image_url,
				__( 'Cover image', 'learnpress' ),
				$hide_img_preview
			);

			$section_img = [
				'wrapper'       => '<div class="lp-user-cover-image__display">',
				'image_empty'   => $html_img_empty,
				'image_preview' => $html_img_preview,
				'wrapper_end'   => '</div>',
			];

			$hide_btn_choose = empty( $cover_image_url ) ? 'style="display: none"' : '';
			$hide_btn_remove = empty( $cover_image_url ) ? 'style="display: none"' : '';

			$section_btn = [
				'wrapper'      => '<div class="lp-user-cover-image__buttons">',
				'input_file'   => '<input type="file" class="lp-cover-image-file"
									name="lp-cover-image-file" accept="image/png, image/jpeg, image/webp" hidden />',
				'input_action' => '<input type="hidden" name="action" value="upload"  />',
				'choose_file'  => sprintf(
					'<button class="lp-button button lp-btn-choose-cover-image" %s>%s</button>',
					$hide_btn_choose,
					__( 'Replace', 'learnpress' )
				),
				'save_btn'     => '<button class="lp-button button lp-btn-save-cover-image" type="submit" style="display: none">' . __( 'Save', 'learnpress' ) . '</button>',
				'remove'       => sprintf(
					'<button class="lp-button button lp-btn-remove-cover-image" %s>%s</button>',
					$hide_btn_remove,
					__( 'Remove', 'learnpress' )
				),
				'wrapper_end'  => '</div>',
			];

			$section = apply_filters(
				'learn-press/profile/html-upload-cover-image',
				[
					'wrapper'     => '<form class="lp-user-cover-image" enctype="multipart/form-data" method="post">',
					'image'       => Template::combine_components( $section_img ),
					'buttons'     => Template::combine_components( $section_btn ),
					'wrapper_end' => '</form>',
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
