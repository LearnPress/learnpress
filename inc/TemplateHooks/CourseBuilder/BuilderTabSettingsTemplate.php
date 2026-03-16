<?php
/**
 * Template hooks Tab Settings in Course Builder.
 *
 * @since 4.3.x
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Settings;
use Throwable;

class BuilderTabSettingsTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/settings/layout', [ $this, 'html_tab_settings' ] );
	}

	public function html_tab_settings() {
		try {
			$user_id = get_current_user_id();
			if ( ! $user_id || ! current_user_can( ADMIN_ROLE ) ) {
				echo Template::print_message( __( 'Only administrators can manage instructor access in Course Builder.', 'learnpress' ), 'error', false );
				return;
			}

			wp_enqueue_script( 'lp-course-builder' );

			$this->render_settings_content();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	protected function render_settings_content() {
		$is_cb_admin_mode = LP_Settings::get_option( 'enable_cb_admin_mode', 'no' ) === 'yes';

		?>
		<div class="lp-cb-settings">
			<div class="lp-cb-settings__content">
				<form id="lp-cb-settings-form" method="post" novalidate>
					<div class="lp-cb-settings__section">
						<div class="form-field lp-cb-settings__field enable_cb_admin_mode_field">
							<label for="enable_cb_admin_mode"><?php esc_html_e( 'Instructor Access', 'learnpress' ); ?></label>
							<input type="checkbox" id="enable_cb_admin_mode" name="enable_cb_admin_mode" value="yes" <?php checked( $is_cb_admin_mode ); ?>>
							<span class="description"><?php esc_html_e( 'When enabled, instructors are redirected away from most wp-admin pages and continue their work in Course Builder instead. Administrators keep full access.', 'learnpress' ); ?></span>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
