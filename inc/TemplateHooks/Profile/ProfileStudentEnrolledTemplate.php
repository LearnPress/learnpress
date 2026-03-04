<?php
/**
 * Class ProfileStudentEnrolledTemplate.
 *
 * @since 4.3.3
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Singleton;
use LP_Profile;

class ProfileStudentEnrolledTemplate {
	use Singleton;

	public function init() {
		add_filter( 'learn-press/get-profile-tabs', array( $this, 'display_profile_tab' ), 10, 3 );
	}

	/**
	 * Show tab for instructor viewing own profile only.
	 *
	 * @param array $tabs
	 * @param mixed $user_of_profile
	 * @param mixed $user_current
	 *
	 * @return array
	 */
	public function display_profile_tab( array $tabs, $user_of_profile, $user_current ): array {
		if ( ! isset( $tabs['enrolled-students'] ) ) {
			return $tabs;
		}

		// Force callback to bound instance method to ensure call_user_func_array() can execute.
		$tabs['enrolled-students']['callback'] = array( $this, 'tab_content' );

		if ( ! is_object( $user_of_profile ) || ! method_exists( $user_of_profile, 'is_instructor' ) ||
			! method_exists( $user_of_profile, 'get_id' ) ) {
			unset( $tabs['enrolled-students'] );
			return $tabs;
		}

		if ( ! is_object( $user_current ) || ! method_exists( $user_current, 'get_id' ) ) {
			unset( $tabs['enrolled-students'] );
			return $tabs;
		}

		if ( ! $user_of_profile->can_create_course() ) {
			unset( $tabs['enrolled-students'] );
			return $tabs;
		}

		// Show tab for instructor viewing own profile only.
		if ( (int) $user_of_profile->get_id() !== (int) $user_current->get_id() ) {
			unset( $tabs['enrolled-students'] );
			return $tabs;
		}

		return $tabs;
	}

	/**
	 * Render content of profile tab "Enrolled Students".
	 *
	 * @param string $tab_key
	 * @param mixed  $profile_tab
	 * @param mixed  $user
	 *
	 * @return string
	 */
	public function tab_content( $tab_key = '', $profile_tab = null, $user = null ): string {
		$profile      = LP_Profile::instance();
		$user         = is_object( $user ) ? $user : $profile->get_user();
		$user_current = $profile->get_user_current();

		if ( ! is_object( $user ) || ! method_exists( $user, 'can_create_course' ) || ! method_exists( $user, 'get_id' ) ) {
			return '';
		}

		if ( ! is_object( $user_current ) || ! method_exists( $user_current, 'get_id' ) ) {
			return '';
		}

		if ( ! $user->can_create_course() || (int) $user->get_id() !== (int) $user_current->get_id() ) {
			return '';
		}

		ob_start();
		echo '<div id="lp-enrolled-students">';
		do_action( 'learn-press/admin/enrolled-students/layout', (int) $user->get_id() );
		echo '</div>';

		return ob_get_clean();
	}
}
