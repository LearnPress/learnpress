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
		$status_label     = $is_cb_admin_mode ? __( 'Enabled', 'learnpress' ) : __( 'Disabled', 'learnpress' );

		?>
		<div class="lp-cb-settings">
			<div class="lp-cb-settings__content">
				<form id="lp-cb-settings-form" method="post" novalidate>
					<div class="lp-cb-settings__section lp-cb-settings__section--policy">
						<div class="lp-cb-settings__section-header">
							<div>
								<h3><?php esc_html_e( 'Instructor Access', 'learnpress' ); ?></h3>
								<p class="lp-cb-settings__intro">
									<?php esc_html_e( 'Control how instructors access the course management workspace across the site.', 'learnpress' ); ?>
								</p>
							</div>
							<span class="lp-cb-settings__status-badge" data-setting-badge data-state="<?php echo esc_attr( $is_cb_admin_mode ? 'enabled' : 'disabled' ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</div>

						<div class="lp-cb-settings__card">
							<div class="lp-cb-settings__field">
								<div class="lp-cb-settings__field-copy">
									<h4><?php esc_html_e( 'Redirect instructors to Course Builder', 'learnpress' ); ?></h4>
									<p class="description"><?php esc_html_e( 'When enabled, instructors are redirected away from most wp-admin pages and continue their work in Course Builder instead. Administrators keep full access.', 'learnpress' ); ?></p>
								</div>

								<label class="lp-cb-settings__checkbox-label">
									<input type="checkbox" name="enable_cb_admin_mode" value="yes" <?php checked( $is_cb_admin_mode ); ?>>
									<span class="screen-reader-text"><?php esc_html_e( 'Enable instructor redirect to Course Builder', 'learnpress' ); ?></span>
								</label>
							</div>

							<ul class="lp-cb-settings__impact-list">
								<li><?php esc_html_e( 'Applies to all instructor accounts site-wide.', 'learnpress' ); ?></li>
								<li><?php esc_html_e( 'Media uploads and AJAX requests remain available so instructors can keep working inside the builder.', 'learnpress' ); ?></li>
								<li><?php esc_html_e( 'WordPress links inside Course Builder are hidden for instructors while this policy is enabled.', 'learnpress' ); ?></li>
							</ul>

							<p class="lp-cb-settings__autosave" data-setting-status data-state="idle" role="status" aria-live="polite">
								<?php esc_html_e( 'Changes save automatically.', 'learnpress' ); ?>
							</p>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
