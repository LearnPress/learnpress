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
	 * HTML cover image.
	 *
	 * @param UserModel $user
	 *
	 * @return string
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_cover_image( UserModel $user ): string {
		$html = '';

		try {
			$cover_image_url = $user->get_cover_image_url();

			$section = apply_filters(
				'learn-press/profile/html-cover-image',
				[
					'wrapper'     => sprintf(
						'<div class="lp-user-cover-image_background %s" style="%s">',
						empty( $cover_image_url ) ? 'lp-hidden' : '',
						sprintf( 'background-image: url(%s);', $cover_image_url ),
					),
					'image'       => sprintf(
						'<img src="%s" alt="%s" />',
						$cover_image_url,
						__( 'Cover image', 'learnpress' )
					),
					'wrapper_end' => '</div>',
				],
				$user
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML upload edit cover image.
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

			$class_hide     = 'lp-hidden';
			$html_img_empty = sprintf(
				'<div class="lp-cover-image-empty %s"><span class="lp-icon-plus"></span>%s</div>',
				empty( $cover_image_url ) ? '' : $class_hide,
				__( 'upload', 'learnpress' ),
			);

			$html_img_preview = sprintf(
				'<img class="lp-cover-image-preview %s" src="%s" alt="%s" />',
				empty( $cover_image_url ) ? $class_hide : '',
				$cover_image_url,
				__( 'Cover image', 'learnpress' ),
			);

			$section_img = [
				'wrapper'       => '<div class="lp-user-cover-image__display">',
				'image_empty'   => $html_img_empty,
				'image_preview' => $html_img_preview,
				'wrapper_end'   => '</div>',
			];

			$section_btn = [
				'wrapper'      => '<div class="lp-user-cover-image__buttons">',
				'input_file'   => '<input type="file" class="lp-cover-image-file"
									name="lp-cover-image-file" accept="image/png, image/jpeg, image/webp" hidden />',
				'input_action' => '<input type="hidden" name="action" value="upload"  />',
				'choose_file'  => sprintf(
					'<button class="lp-button lp-btn-choose-cover-image %s">%s</button>',
					empty( $cover_image_url ) ? $class_hide : '',
					__( 'Replace', 'learnpress' )
				),
				'save_btn'     => sprintf(
					'<button class="lp-button lp-btn-save-cover-image %s">%s</button>',
					$class_hide,
					__( 'Save', 'learnpress' )
				),
				'cancel'       => sprintf(
					'<button class="lp-button lp-btn-cancel-cover-image %s">%s</button>',
					$class_hide,
					__( 'Cancel', 'learnpress' )
				),
				'remove'       => sprintf(
					'<button class="lp-button lp-btn-remove-cover-image %s">%s</button>',
					empty( $cover_image_url ) ? $class_hide : '',
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
