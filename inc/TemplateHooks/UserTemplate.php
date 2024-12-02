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
	 * Get html avatar of instructor.
	 *
	 * @param UserModel $user
	 * @param array $size_display [ 'width' => 100, 'height' => 100 ]
	 * @param string $class_name
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.3
	 */
	public function html_avatar( UserModel $user, array $size_display = [], string $class_name = 'user' ): string {
		$html = '';

		try {
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
					'wrapper'     => sprintf( '<div class="%s-avatar">', $class_name ),
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
