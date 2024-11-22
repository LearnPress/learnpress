<?php
/**
 * Template hooks User.
 *
 * @since 4.2.7.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course;
use LP_Course_Filter;
use LP_User;
use Throwable;
use WP_Query;

class UserTemplate {
	use Singleton;

	public function init() {
	}

	/**
	 * Get display name html of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 * @param string $class
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_display_name( $instructor, string $class = 'user' ): string {
		$section = [
			'wrapper'      => sprintf( '<div class="%s-display-name">', $class ),
			'display_name' => $instructor->get_display_name(),
			'wrapper_end'  => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html social of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_social( $instructor, string $class = 'user' ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<div class="instructor-social">' => '</div>',
			];
			$socials      = $instructor->get_profile_social( $instructor->get_id() );
			ob_start();
			foreach ( $socials as $k => $social ) {
				echo $social;
			}
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html description of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_description( $instructor ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<div class="instructor-description">' => '</div>',
			];

			$content = Template::instance()->nest_elements( $html_wrapper, $instructor->get_description() );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html avatar of instructor.
	 *
	 * @param UserModel $user
	 * @param array $size_display [ 'width' => 100, 'height' => 100 ]
	 * @param string $class
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.2
	 */
	public function html_avatar( UserModel $user, array $size_display = [], string $class = 'user' ): string {
		$html = '';

		try {
			if ( empty( $size_display ) ) {
				$size_display = learn_press_get_avatar_thumb_size();
			}

			$width = $height = $size_display;
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
					'wrapper'     => sprintf( '<div class="%s-avatar">', $class ),
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
}
