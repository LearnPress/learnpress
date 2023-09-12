<?php
/**
 * Class SingleInstructorBaseElementor
 *
 * Has general methods for sections single instructor widgets use
 *
 * @sicne 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor\Widgets\Instructor;

use Elementor\Plugin;
use Exception;
use LearnPress\ExternalPlugin\Elementor\LPElementor;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\Helpers\Config;
use LP_User;

class SingleInstructorBaseElementor extends LPElementorWidgetBase {
	/**
	 * Set category for widget
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( LPElementor::CATE_INSTRUCTOR );
	}

	/**
	 * Detect instructor id if is single instructor page
	 *
	 * @param array $settings
	 * @param LP_User|null $instructor
	 * @param string $label_default
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function detect_instructor_id( array $settings, LP_User &$instructor = null, string $label_default = '' ) {
		/**
		 * Get instructor id
		 *
		 * If is page is single instructor, will be get instructor id from query var
		 * Else will be get instructor id from widget
		 */
		$instructor_id = 0;
		if ( get_query_var( 'is_single_instructor' ) ) {
			if ( get_query_var( 'instructor_name' ) && 'page' !== get_query_var( 'instructor_name' ) ) {
				$user = get_user_by( 'slug', get_query_var( 'instructor_name' ) );
				if ( $user ) {
					$instructor_id = $user->ID;
				}
			} else {
				$instructor_id = get_current_user_id();
			}
		} else {
			$instructor_id = $settings['instructor_id'] ?? 0;
		}

		if ( ! $instructor_id && Plugin::$instance->editor->is_edit_mode() ) {
			throw new Exception( $label_default );
		}

		$instructor = learn_press_get_user( $instructor_id );
		if ( ! $instructor ) {
			throw new Exception( __( 'Instructor not found!', 'learnpress' ) );
		}

		if ( ! $instructor->can_create_course() ) {
			throw new Exception( __( 'User is not Instructor!', 'learnpress' ) );
		}
	}
}
