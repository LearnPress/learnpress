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
use LP_Profile;
use LP_Settings;
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
	 * @version 1.0.1
	 */
	public function html_cover_image( UserModel $user ): string {
		$html = '';

		try {
			$cover_image_url              = $user->get_cover_image_url();
			$profile                      = LP_Profile::instance( $user->get_id() );
			$current_section              = $profile->get_current_section();
			$html_btn_to_edit_cover_image = '';
			$cover_image_dimensions       = LP_Settings::get_option(
				'cover_image_dimensions',
				array(
					'width'  => 1290,
					'height' => 250,
				)
			);

			if ( $user->get_id() === get_current_user_id() ) {
				$html_btn_to_edit_cover_image = sprintf(
					'<a class="lp-btn-to-edit-cover-image" href="%s" data-section-correct="%d">+ %s</a>',
					$profile->get_tab_link( 'settings', 'cover-image' ),
					$current_section === 'cover-image' ? 1 : 0,
					__( 'edit cover image', 'learnpress' )
				);
			}

			$section = apply_filters(
				'learn-press/profile/html-cover-image',
				[
					'wrapper'     => sprintf(
						'<div class="lp-user-cover-image_background %s" style="%s">',
						empty( $cover_image_url ) ? 'lp-hidden' : '',
						sprintf( 'background-image: url(%s);', $cover_image_url )
					),
					'image'       => sprintf(
						'<img src="%s" alt="%s" decoding="async" />',
						$cover_image_url,
						__( 'Cover image', 'learnpress' )
					),
					'btn-edit'    => $html_btn_to_edit_cover_image,
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
	 * @since 4.2.8.2
	 * @version 1.0.0
	 */
	public function html_upload_cover_image( UserModel $user ): string {
		$html = '';

		try {
			$cover_image_url        = $user->get_cover_image_url();
			$cover_image_dimensions = LP_Settings::get_option(
				'cover_image_dimensions',
				array(
					'width'  => 1290,
					'height' => 250,
				)
			);

			$class_hide = 'lp-hidden';

			$section_img_empty_info = [
				'wrapper'     => '<div class="lp-cover-image-empty__info">',
				'top'         => sprintf(
					'<div class="lp-cover-image-empty__info__top">%s%s</div>',
					'<span class="lp-icon-file-image"></span>',
					__( 'Drag and drop or click here to choose image', 'learnpress' )
				),
				'bottom'      => sprintf(
					'<div class="lp-cover-image-empty__info__bottom">%s</div>',
					sprintf(
						__( 'Accepted file types: JPG, PNG %1$d x %2$d (px)', 'learnpress' ),
						$cover_image_dimensions['width'],
						$cover_image_dimensions['height']
					)
				),
				'wrapper_end' => '</div>',
			];
			$html_img_empty         = apply_filters(
				'learn-press/profile/html-cover-image-empty',
				[
					'wrapper'     => sprintf(
						'<div class="lp-cover-image-empty %s">',
						empty( $cover_image_url ) ? '' : $class_hide
					),
					'info'        => Template::combine_components( $section_img_empty_info ),
					'input_file'  => sprintf(
						'<input type="file" class="%s" name="lp-cover-image-file" accept="%s" />',
						'lp-cover-image-file',
						'image/png, image/jpeg, image/webp'
					),
					'wrapper_end' => '</div>',
				],
				$user
			);

			$html_img_preview = sprintf(
				'<img class="lp-cover-image-preview %s" src="%s" alt="%s" decoding="async" />',
				empty( $cover_image_url ) ? $class_hide : '',
				$cover_image_url,
				__( 'Cover image', 'learnpress' )
			);

			$section_img = [
				'wrapper'       => '<div class="lp-user-cover-image__display">',
				'image_empty'   => Template::combine_components( $html_img_empty ),
				'image_preview' => $html_img_preview,
				'wrapper_end'   => '</div>',
			];

			$section_btn = [
				'wrapper'      => '<div class="lp-user-cover-image__buttons">',
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

	/**
	 * HTML upload avatar image.
	 *
	 * @param UserModel $user
	 *
	 * @return string
	 * @since 4.2.8.2
	 * @version 1.0.0
	 */
	public function html_upload_avatar( UserModel $user ): string {
		$html = '';

		try {
			$class_hide       = 'lp-hidden';
			$avatar_image_url = $user->get_upload_avatar_src();

			$html_img_preview = sprintf(
				'<img class="lp-avatar-image %s" src="%s" alt="%s" decoding="async" />',
				empty( $avatar_image_url ) ? $class_hide : '',
				$avatar_image_url,
				__( 'Avatar image', 'learnpress' )
			);
			$html_img_empty   = [
				'start'     => sprintf(
					'<form class="lp_avatar__form %s">',
					empty( $avatar_image_url ) ? '' : $class_hide
				),
				'label'     => '<label for="avatar-file">',
				'div'       => '<div class="learnpress_avatar__form__upload">',
				'upload'    => sprintf(
					'<span>%s</span>',
					__( 'Upload Avatar', 'learnpress' )
				),
				'input'     => sprintf(
					'<input type="file" id="avatar-file" class="%s" name="lp-avatar-file" accept="%s" />',
					'lp-avatar-file lp-hidden',
					'image/png, image/jpeg, image/webp'
				),
				'div_end'   => '</div>',
				'label_end' => '</label>',
				'end'       => '</form>',
			];

			$section_img = [
				'wrapper'       => '<div class="lp-user-avatar-image__display" style="width: 250px;height:250px">',
				'image_empty'   => Template::combine_components( $html_img_empty ),
				'image_preview' => $html_img_preview,
				'wrapper_end'   => '</div>',
			];

			$section_btn = [
				'wrapper'      => '<div class="lp-user-avatar__buttons">',
				'input_action' => '<input type="hidden" name="action" value="upload"  />',
				'choose_file'  => sprintf(
					'<button class="lp-button lp-btn-choose-avatar %s">%s</button>',
					empty( $avatar_image_url ) ? $class_hide : '',
					__( 'Replace', 'learnpress' )
				),
				'save_btn'     => sprintf(
					'<button class="lp-button lp-btn-save-avatar %s">%s</button>',
					$class_hide,
					__( 'Save', 'learnpress' )
				),
				'cancel'       => sprintf(
					'<button class="lp-button lp-btn-cancel-avatar %s">%s</button>',
					$class_hide,
					__( 'Cancel', 'learnpress' )
				),
				'remove'       => sprintf(
					'<button class="lp-button lp-btn-remove-avatar %s">%s</button>',
					empty( $avatar_image_url ) ? $class_hide : '',
					__( 'Remove', 'learnpress' )
				),
				'wrapper_end'  => '</div>',
			];

			$section = apply_filters(
				'learn-press/profile/html-upload-avatar',
				[
					'wrapper'     => '<div id="learnpress-avatar-upload">',
					'image'       => Template::combine_components( $section_img ),
					'buttons'     => Template::combine_components( $section_btn ),
					'wrapper_end' => '</div>',
				],
				$user
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html .= Template::print_message(
				$e->getMessage(),
				'error',
				false
			);
		}
		return $html;
	}
}
