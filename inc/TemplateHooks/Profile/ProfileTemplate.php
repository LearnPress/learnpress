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
	 * @version 1.0.0
	 */
	public function html_cover_image( UserModel $user ): string {
		$html = '';

		try {
			$cover_image_url              = $user->get_cover_image_url();
			$profile                      = LP_Profile::instance();
			$current_section              = LP_Profile::instance()->get_current_section();
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
	 * @since 4.2.7.2
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
	 * HTML sidebar.
	 *
	 * @param string $inner_content
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_sidebar(): string {
		$html = '';

		$user      = LP_Profile::instance()->get_user();
		$userModel = UserModel::find( $user->get_id(), true );
		if ( ! $userModel ) {
			return $html;
		}

		try {
			ob_start();
			do_action( 'learn-press/user-profile-tabs' );
			$action  = ob_get_clean();
			$section = [
				'aside'     => '<aside id="profile-sidebar">',
				'content'   => $action,
				'aside_end' => '</aside>',
			];
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return $html;
		}

		return $html;
	}

	/**
	 * HTML content.
	 * @param LP_Profile $profile
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_content( LP_Profile $profile ): string {
		$html          = '';
		$user          = $profile->get_user();
		$current_tab   = $profile->get_current_tab();
		$user_can_view = $profile->current_user_can( 'view-tab-' . $current_tab );
		if ( ! $user_can_view ) {
			return $html;
		}

		if ( $profile->get_user_current()->is_guest() ) {
			return $html;
		}

		$tabs        = $profile->get_tabs();
		$tab_key     = $profile->get_current_tab();
		$profile_tab = $tabs->get( $tab_key );

		try {
			ob_start();
			do_action( 'learn-press/before-profile-content', $tab_key, $profile_tab, $user );
			$before_content = ob_get_clean();

			ob_start();
			if ( empty( $profile_tab->get( 'sections' ) ) ) {
				if ( $profile_tab->get( 'callback' ) && is_callable( $profile_tab->get( 'callback' ) ) ) {
					echo call_user_func_array(
						$profile_tab->get( 'callback' ),
						[
							$tab_key,
							$profile_tab,
							$user,
						]
					);
				} else {
					do_action( 'learn-press/profile-content', $tab_key, $profile_tab, $user );
				}
			} else {
				foreach ( $profile_tab->get( 'sections' ) as $key => $section ) {
					if ( $profile->get_current_section( '', false, false ) === $section['slug'] ) {
						if ( isset( $section['callback'] ) && is_callable( $section['callback'] ) ) {
							echo call_user_func_array( $section['callback'], array( $key, $section, $user ) );
						} else {
							do_action( 'learn-press/profile-section-content', $key, $section, $user );
						}
					}
				}
			}
			$content = ob_get_clean();

			ob_start();
			do_action( 'learn-press/after-profile-content' );
			$after_content = ob_get_clean();

			$section = [
				'article'     => '<article id="profile-content" class="lp-profile-content">',
				'id'          => sprintf( '<div id="profile-content-%s">', esc_attr( $tab_key ) ),
				'before'      => $before_content,
				'content'     => $content,
				'after'       => $after_content,
				'id_end'      => '</div>',
				'article_end' => '</article>',
			];
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return $html;
		}

		return $html;
	}

	/**
	 * HTML profile.
	 *
	 * @param string $inner_content
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_profile( string $inner_content = '' ): string {
		$html = '';

		try {
			$section = [
				'wrapper'     => '<div class="learnpress">',
				'id'          => '<div id="learn-press-profile" class="lp-user-profile current-user">',
				'area'        => '<div class="lp-content-area">',
				'content'     => $inner_content,
				'area_end'    => '</div>',
				'id_end'      => '</div>',
				'wrapper_end' => '</div>',
			];
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return $html;
		}

		return $html;
	}

	/**
	 * HTML avatar.
	 *
	 * @param UserModel $user
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_avatar( UserModel $user ): string {
		$html = '';

		try {
			$image_url        = $user->get_image_url();
			$image_dimensions = LP_Settings::get_option(
				'avatar_dimensions',
				array(
					'width'  => 250,
					'height' => 250,
				)
			);
			$avatar           = [
				'wrapper'     => '<div class="lp-user-profile-avatar">',
				'img'         => sprintf(
					'<img src="%s" alt="%s" class="%s" width="%s" height="%s"  decoding="async" />',
					$image_url,
					__( 'User Avatar', 'learnpress' ),
					'avatar',
					$image_dimensions['width'],
					$image_dimensions['height']
				),
				'wrapper_end' => '</div>',
			];
			$html             = Template::combine_components( $avatar );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return $html;
		}

		return $html;
	}

	/**
	 * HTML username.
	 *
	 * @param UserModel $user
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_username( UserModel $user ): string {
		$html = '';

		try {
			$username = [
				'wrapper'     => '<h2 class="lp-profile-username">',
				'username'    => wp_kses_post( $user->get_username() ),
				'wrapper_end' => '</h2>',
			];
			$html     = Template::combine_components( $username );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return $html;
		}

		return $html;
	}

	/**
	 * HTML login form.
	 *
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_login_form() {
		$html = '';

		if ( is_user_logged_in() ) {
			return $html;
		}

		try {
			ob_start();
			learn_press_show_message();
			learn_press_get_template( 'global/form-login.php' );
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return $html;
		}

		return $html;
	}
}
