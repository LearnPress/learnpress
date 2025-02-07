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
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
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
	 * @param UserModel|false $instructor
	 * @param string $label_default
	 *
	 * @since 4.2.3
	 * @version 1.0.1
	 * @return void
	 * @throws Exception
	 */
	protected function detect_instructor_id( array $settings, &$instructor = null, string $label_default = '' ) {
		/**
		 * Get instructor id
		 *
		 * If is page is single instructor, will be get instructor id from query var
		 * Else will be get instructor id from widget
		 */
		$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();
		if ( ! $instructor instanceof UserModel ) {
			$instructor_id = (int) $settings['instructor_id'] ?? 0;
			$instructor    = UserModel::find( $instructor_id, true );
		}

		if ( ! $instructor ) {
			if ( Plugin::$instance->editor->is_edit_mode() ) {
				throw new Exception( $label_default );
			} else {
				throw new Exception( __( 'Instructor not found!', 'learnpress' ) );
			}
		}

		if ( ! $instructor->is_instructor() ) {
			throw new Exception( __( 'User is not Instructor!', 'learnpress' ) );
		}
	}
}
