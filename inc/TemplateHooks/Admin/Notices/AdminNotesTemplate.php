<?php
/**
 * Admin templates for LearnPress notices.
 *
 * @package LearnPress\TemplateHooks\Admin\Tools
 * @since 4.4.6
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Admin\Notices;

use Exception;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Services\AddonService;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminCourseTools
 *
 * @since 4.4.9
 * @version 1.0.0
 */
class AdminNotesTemplate {
	use Singleton;

	/**
	 * Hooks initialization.
	 */
	public function init(): void {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Register AJAX callbacks allowed for loading content via AJAX.
	 *
	 * @param array $callbacks List of allowed callbacks.
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_notices';
		$callbacks[] = self::class . ':dismiss_notice';

		return $callbacks;
	}

	/**
	 * Aggregate and render all admin notices.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return stdClass
	 * @since 4.4.9
	 * @version 1.0.0
	 */
	public static function render_notices( array $data ): stdClass {
		$response          = new stdClass();
		$response->content = '';
		$response->status  = Response::STATUS_SUCCESS;

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception(
					__( 'You do not have permission to perform this action.',
						'learnpress' )
				);
			}

			$notices = [
				self::html_check_wp_remote( $data ),
				self::html_check_plugin_base( $data ),
				self::html_lp_beta_version( $data ),
				self::html_lp_upgrade_db( $data ),
				self::html_lp_permalink( $data ),
				self::html_lp_setup_wizard( $data ),
				self::html_lp_addons_new_version( $data ),
				self::html_addons_need_extend( $data ),
			];

			$response->content = implode( '', array_filter( $notices ) );
		} catch ( Throwable $exception ) {
			$response->status  = Response::STATUS_ERROR;
			$response->content = Template::print_message(
				$exception->getMessage(),
				Response::STATUS_ERROR,
				false
			);
		}

		return $response;
	}

	/**
	 * Render add-on licenses need extend notice.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 * @since 4.4.9
	 * @version 1.0.0
	 */
	public static function html_addons_need_extend( array $data ): string {
		$admin_notices_dismiss = get_option( 'lp_admin_notices_dismiss', [] );
		if ( isset( $admin_notices_dismiss['lp-addons-purchased-extend'] ) ||
			! AddonService::instance()->check_addons_purchased_need_extend() ) {
			return '';
		}

		$button_dismiss = self::html_button_dismiss( 'lp-addons-purchased-extend' );

		return Template::print_message(
			$button_dismiss . sprintf(
				'%1$s <strong><a style="color: #E64B50" href="%2$s">%3$s</a></strong>',
				esc_html__( 'You have LearnPress Add-on licenses that need to be extended.', 'learnpress' ),
				esc_url( admin_url( 'admin.php?page=learn-press-addons&tab=license' ) ),
				esc_html__( 'Check now!', 'learnpress' )
			),
			'info',
			false
		);
	}

	public static function html_check_wp_remote( array $data ): string {
		$test_url = LP_PLUGIN_URL . 'assets/images/icon-128x128.png';
		$args     = [
			'timeout' => 5,
		];
		$result   = wp_remote_get( $test_url, $args );

		if ( ! is_wp_error( $result ) ) {
			return '';
		}


		$message = sprintf(
			'<strong>wp_remote_get</strong>: %s. <a href="%s">%s</a>',
			$result->get_error_message(),
			admin_url( 'site-health.php' ),
			__( 'Check Site Health', 'learnpress' )
		);

		return Template::print_message( $message, 'error', false );
	}

	public static function html_check_plugin_base( array $data ): string {
		if ( ! \LP_Admin_Notice::check_plugin_base() ) {
			return '';
		}

		$message = sprintf(
			__(
				'The LearnPress plugin base directory must be <strong>learnpress/learnpres.php</strong> (case-sensitive) to ensure all functions work properly and are fully operational (currently <strong>%s</strong>)',
				'learnpress'
			),
			LP_PLUGIN_BASENAME
		);

		return Template::print_message( $message, 'warning', false );
	}

	public static function html_lp_beta_version( array $data ): string {
		$lp_beta_version_info = \LP_Admin_Notice::check_lp_beta_version();
		$show_notice          = $lp_beta_version_info && empty( $data['has_tab'] ) &&
			( ! isset( $_COOKIE['lp_beta_version'] ) ||
				version_compare( $_COOKIE['lp_beta_version'], $lp_beta_version_info['version'], '<' ) );

		if ( ! $show_notice ) {
			return '';
		}

		$data_info = \LP_Admin_Notice::get_data_lp_beta( $lp_beta_version_info );
		$message   = sprintf(
			'<h3>%s</h3>%s%s',
			wp_kses_post( $data_info['title'] ?? '' ),
			wp_kses_post( $data_info['description'] ?? '' ),
			self::html_button_dismiss( 'lp-beta-version' )
		);

		return Template::print_message( $message, 'info', false );
	}

	public static function html_lp_upgrade_db( array $data ): string {
		if ( ! \LP_Updater::instance()->check_lp_db_need_upgrade() ) {
			return '';
		}

		$message = sprintf(
			'<p>%s</p><p><a class="button button-primary lp-btn-go-upgrade-db" data-context="message" href="%s">%s</a></p>',
			__( '<strong>LearnPress update</strong> – We need to update your database to the latest version.', 'learnpress' ),
			admin_url( 'admin.php?page=learn-press-tools&tab=database&action=upgrade-db' ),
			__( 'Go to Update', 'learnpress' )
		);

		return Template::print_message( $message, 'warning', false );
	}

	public static function html_lp_permalink( array $data ): string {
		if ( get_option( 'permalink_structure' ) ) {
			return '';
		}

		$message = sprintf(
			'LearnPress requires permalink option <strong>Post name</strong> is enabled. Please enable it <a href="%s">here</a> to ensure that all functions work properly.',
			admin_url( 'options-permalink.php' )
		);

		return Template::print_message( $message, 'warning', false );
	}

	public static function html_lp_setup_wizard( array $data ): string {
		$admin_notices_dismiss = get_option( 'lp_admin_notices_dismiss', [] );
		if ( get_option( 'learn_press_setup_wizard_completed', false ) ||
			isset( $admin_notices_dismiss['lp-setup-wizard'] ) ) {
			return '';
		}

		$message = sprintf(
			'<p><strong>%s</strong></p><p><a class="button button-primary" href="%s">%s</a>%s</p>',
			__( 'LearnPress LMS is ready to set up', 'learnpress' ),
			admin_url( 'index.php?page=lp-setup' ),
			__( 'Quick Setup', 'learnpress' ),
			self::html_button_dismiss( 'lp-setup-wizard' )
		);

		return Template::print_message( $message, 'info', false );
	}

	public static function html_lp_addons_new_version( array $data ): string {
		$admin_notices_dismiss = get_option( 'lp_admin_notices_dismiss', [] );
		if ( isset( $admin_notices_dismiss['lp-addons-new-version'] ) ) {
			return '';
		}

		$addons = AddonService::instance()->list_addon_new_version();
		if ( empty( $addons ) ) {
			return '';
		}

		$addons_html_arr = [];
		foreach ( $addons as $addon ) {
			$addons_html_arr[] = sprintf(
				'<a href="%s" class="">%s</a>',
				admin_url( 'admin.php?page=learn-press-addons&tab=update' ),
				$addon->name
			);
		}

		$addons_html = implode( ', ', $addons_html_arr );


		$message = sprintf(
			'%s: %s %s',
			__( 'New version available', 'learnpress' ),
			$addons_html,
			self::html_button_dismiss( 'lp-addons-new-version' )
		);

		$input_html = sprintf( '<input type="hidden" name="lp-addons-new-version-totals" value="%d"/>', count( $addons ) );

		return Template::print_message( $message, 'info', false ) . $input_html;
	}

	public static function html_button_dismiss( string $key ): string {
		return sprintf(
			'<span class="btn-lp-notice-dismiss lp-icon-close"
				data-dismiss="%1$s" title="%2$s">
			</span>',
			esc_attr( $key ),
			esc_attr__( 'Dismiss notice', 'learnpress' ),
		);
	}

	public static function dismiss_notice( array $data ): stdClass
	{
		$content = new stdClass();
		$content->content = '';

		try {
			if (!current_user_can(UserModel::ROLE_ADMINISTRATOR)) {
				throw new Exception(
					__('You do not have permission to perform this action.', 'learnpress')
				);
			}

			$key = sanitize_key($data['dismiss'] ?? '');
			if (empty($key)) {
				throw new Exception(__('The notice key is invalid.', 'learnpress'));
			}

			$lp_beta_version_info = \LP_Admin_Notice::check_lp_beta_version();
			if ($lp_beta_version_info) {
				learn_press_setcookie('lp_beta_version', $lp_beta_version_info['version'] ?? 0);
			}

			$admin_notices_dismiss = get_option('lp_admin_notices_dismiss', []);
			$admin_notices_dismiss[$key] = $key;
			update_option('lp_admin_notices_dismiss', $admin_notices_dismiss);
			$content->message = __('Dismissed!', 'learnpress');
		} catch (Throwable $exception) {
			$content->status = Response::STATUS_ERROR;
			$content->message = $exception->getMessage();
		}

		return $content;
	}
}
