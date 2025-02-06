<?php
/**
 * Template hooks User.
 *
 * @since 4.2.7.2
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks;

use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LP_Profile;
use LP_User;
use Throwable;

class UserTemplate {
	public $class_name;

	public function __construct( $class_name = 'user' ) {
		$this->class_name = $class_name;
	}

	/**
	 * Get display name html of user.
	 *
	 * @param UserModel|LP_User $userModel
	 *
	 * @return string
	 */
	public function html_display_name( $userModel ): string {
		if ( $userModel instanceof LP_User ) {
			$userModel = UserModel::find( $userModel->get_id(), true );
		}

		if ( ! $userModel ) {
			return '';
		}

		$sections = [
			'wrapper'     => sprintf( '<span class="%s-display-name">', $this->class_name ),
			'content'     => $userModel->get_display_name(),
			'wrapper_end' => '</span>',
		];

		return Template::combine_components( $sections );
	}

	/**
	 * Get html description of instructor.
	 *
	 * @param UserModel|LP_User $userModel
	 *
	 * @return string
	 * @since 4.2.3.4
	 * @version 1.0.1
	 */
	public function html_description( $userModel ): string {
		$content = '';

		try {
			if ( $userModel instanceof LP_User ) {
				$userModel = UserModel::find( $userModel->get_id(), true );
			}

			if ( ! $userModel ) {
				return $content;
			}

			$description = $userModel->get_description();
			if ( empty( $description ) ) {
				return $content;
			}

			$sections = apply_filters(
				'learn-press/user/html-description',
				[
					'wrapper'     => sprintf( '<div class="%s-description">', $this->class_name ),
					'content'     => wpautop( wp_kses_post( $description ) ),
					'wrapper_end' => '</div>',
				],
				$userModel
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html avatar of user.
	 *
	 * @param UserModel $user
	 * @param array $size_display [ 'width' => 100, 'height' => 100 ]
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.4
	 */
	public function html_avatar( UserModel $user, array $size_display = [] ): string {
		$html = '';

		try {
			if ( $user instanceof LP_User ) {
				$user = UserModel::find( $user->get_id(), true );
			}

			if ( ! $user ) {
				return '';
			}

			if ( empty( $size_display ) ) {
				$size_display = learn_press_get_avatar_thumb_size();
			}

			$width  = $size_display;
			$height = $size_display;
			if ( is_array( $size_display ) ) {
				$width  = $size_display['width'];
				$height = $size_display['height'];
			}

			$avatar_url = $user->get_avatar_url();
			$img_avatar = sprintf(
				'<img alt="%s" class="avatar" src="%s" width="%d" height="%d" decoding="async" />',
				esc_attr__( 'User Avatar', 'learnpress' ),
				$avatar_url,
				$width,
				$height
			);

			$section = apply_filters(
				'learn-press/user/html-avatar',
				[
					'wrapper'     => sprintf( '<div class="%s-avatar">', $this->class_name ),
					'avatar'      => $img_avatar,
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
	 * Get html avatar of instructor with edit link.
	 * Don't wrapper this html with tag <a> because has tag <a> inside.
	 *
	 * @param UserModel $user
	 * @param array $size_display [ 'width' => 100, 'height' => 100 ]
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_avatar_edit( UserModel $user, array $size_display = [] ): string {
		$html = '';

		try {
			if ( $user instanceof LP_User ) {
				$user = UserModel::find( $user->get_id(), true );
			}

			if ( ! $user ) {
				return '';
			}

			if ( empty( $size_display ) ) {
				$size_display = learn_press_get_avatar_thumb_size();
			}

			$profile = LP_Profile::instance( $user->get_id() );
			$width   = $size_display;
			$height  = $size_display;
			if ( is_array( $size_display ) ) {
				$width  = $size_display['width'];
				$height = $size_display['height'];
			}

			$avatar_url = $user->get_avatar_url();
			$img_avatar = sprintf(
				'<img alt="%s" class="avatar" src="%s" width="%d" height="%d" decoding="async" />',
				esc_attr__( 'User Avatar', 'learnpress' ),
				$avatar_url,
				$width,
				$height
			);

			$html_btn_to_edit_avatar = '';
			if ( $user->get_id() === get_current_user_id() ) {
				$html_btn_to_edit_avatar = sprintf(
					'<a class="lp-btn-to-edit-avatar" href="%s" data-section-correct="%d" title="%s">+ %s</a>',
					$profile->get_tab_link( 'settings', 'avatar' ),
					'avatar',
					esc_attr__( 'Edit avatar', 'learnpress' ),
					__( 'edit avatar', 'learnpress' )
				);
			}

			$section = apply_filters(
				'learn-press/user/html-avatar-edit',
				[
					'wrapper'     => sprintf( '<div class="%s-avatar">', $this->class_name ),
					'avatar'      => $img_avatar,
					'btn_edit'    => $html_btn_to_edit_avatar,
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
	 * Get html social of instructor.
	 *
	 * @param UserModel|LP_User $userModel
	 *
	 * @return string
	 */
	public function html_social( $userModel ): string {
		$content = '';

		try {
			if ( $userModel instanceof LP_User ) {
				$userModel = UserModel::find( $userModel->get_id(), true );
			}

			if ( ! $userModel ) {
				return '';
			}

			$socials = $userModel->get_profile_social();
			if ( empty( $socials ) ) {
				return $content;
			}

			$sections = [
				'wrapper'     => sprintf( '<div class="%s-social">', $this->class_name ),
				'content'     => Template::combine_components( $socials ),
				'wrapper_end' => '</div>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
