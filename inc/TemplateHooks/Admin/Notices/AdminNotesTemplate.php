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
use LearnPress\Services\AdminNoticeService;
use LearnPress\Services\AddonService;
use LP_Settings;
use LP_Updater;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminNotesTemplate
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
					__(
						'You do not have permission to perform this action.',
						'learnpress'
					)
				);
			}

			$notices = [
				self::html_check_wp_remote( $data ),
				self::html_check_plugin_base( $data ),
				self::html_lp_upgrade_db( $data ),
				self::html_lp_permalink( $data ),
				self::html_lp_setup_wizard( $data ),
				self::html_lp_addons_new_version( $data ),
				self::html_addons_need_extend( $data ),
				self::html_lp_beta_version( $data ),
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
	 * Render a notice when wp_remote_get is unavailable.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_check_wp_remote( array $data = [] ): string {
		$result = AdminNoticeService::check_wp_remote();
		if ( ! is_wp_error( $result ) ) {
			return '';
		}

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
				'<strong>wp_remote</strong>: %s. <a href="%s">%s</a>',
				$result->get_error_message(),
				esc_url( admin_url( 'site-health.php' ) ),
				esc_html__( 'Check Site Health', 'learnpress' )
			)
		);

		return Template::print_message( $message, 'error', false );
	}

	/**
	 * Render a notice when the LearnPress plugin base directory is wrong.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_check_plugin_base( array $data = [] ): string {
		if ( ! AdminNoticeService::check_plugin_base() ) {
			return '';
		}

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
			/* translators: 1: expected plugin basename wrapped in strong, 2: current plugin basename wrapped in strong */
				__(
					'The LearnPress plugin base directory must be %1$s (case-sensitive) to ensure all functions work properly and are fully operational (currently %2$s).',
					'learnpress'
				),
				'<strong>learnpress/learnpress.php</strong>',
				'<strong>' . LP_PLUGIN_BASENAME . '</strong>'
			)
		);

		return Template::print_message( $message, 'warning', false );
	}

	/**
	 * Render a notice to upgrade the LearnPress database.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_lp_upgrade_db( array $data = [] ): string {
		if ( ! LP_Updater::instance()->check_lp_db_need_upgrade() ) {
			return '';
		}

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
				'%s
					<div style="margin-top: 0.5em">
						<a class="button lp-btn-go-upgrade-db" data-context="message" href="%s">%s</a>
					</div>',
				__( '<strong>LearnPress update</strong> – We need to update your database to the latest version.', 'learnpress' ),
				admin_url( 'admin.php?page=learn-press-tools&tab=database&action=upgrade-db' ),
				esc_html__( 'Go to Update', 'learnpress' )
			)
		);

		return Template::print_message( $message, 'warning', false );
	}

	/**
	 * Render a notice when permalink structure is not enabled.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_lp_permalink( array $data = [] ): string {
		$permalink_structure = get_option( 'permalink_structure' );
		if ( ! empty( $permalink_structure ) ) {
			return '';
		}

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
				'LearnPress requires permalink option <strong>Post name</strong> is enabled. Please enable it <a href="%s">here</a> to ensure that all functions work properly.',
				admin_url( 'options-permalink.php' )
			)
		);

		return Template::print_message( $message, 'warning', false );
	}

	/**
	 * Render a notice to run the LearnPress setup wizard.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_lp_setup_wizard( array $data = [] ): string {
		if ( get_option( 'learn_press_setup_wizard_completed', false ) ||
			LP_Settings::is_admin_notice_dismissed( 'lp-setup-wizard' ) ) {
			return '';
		}

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
				'<p><strong>%s</strong></p><p><a class="button button-primary" href="%s">%s</a>%s</p>',
				__( 'LearnPress LMS is ready to set up', 'learnpress' ),
				admin_url( 'index.php?page=lp-setup' ),
				__( 'Quick Setup', 'learnpress' ),
				self::html_button_dismiss( 'lp-setup-wizard' )
			)
		);

		return Template::print_message( $message, 'success', false );
	}

	/**
	 * Render a notice for new versions of LearnPress add-ons.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_lp_addons_new_version( array $data = [] ): string {
		/*if ( LP_Settings::is_admin_notice_dismissed( 'lp-addons-new-version' ) ) {
			return '';
		}*/

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
			'<div class="lp-addons-new-version-totals" data-total="%d">%s</div>',
			count( $addons ),
			sprintf(
				'%s: %s',
				__( 'New version available', 'learnpress' ),
				$addons_html
			)
		);

		return Template::print_message( $message, 'info', false );
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
	public static function html_addons_need_extend( array $data = [] ): string {
		if ( LP_Settings::is_admin_notice_dismissed( 'lp-addons-purchased-extend' ) ||
			! AddonService::instance()->check_addons_purchased_need_extend() ) {
			return '';
		}

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
				'%1$s <strong><a style="color: #E64B50" href="%2$s">%3$s</a></strong>%4$s',
				esc_html__( 'You have LearnPress Add-on licenses that need to be extended.', 'learnpress' ),
				esc_url( admin_url( 'admin.php?page=learn-press-addons&tab=license' ) ),
				esc_html__( 'Check now!', 'learnpress' ),
				self::html_button_dismiss( 'lp-addons-purchased-extend' )
			)
		);

		return Template::print_message(
			$message,
			'info',
			false
		);
	}

	/**
	 * Render a notice for a new LearnPress beta version.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return string
	 */
	public static function html_lp_beta_version( array $data = [] ): string {
		$lp_beta_version_info = AdminNoticeService::check_lp_beta_version();
		if ( ! $lp_beta_version_info instanceof stdClass ) {
			return '';
		}

		$admin_notices_dismiss = get_option( 'lp_admin_notices_dismiss', [] );
		if ( isset( $admin_notices_dismiss['lp-beta-version'] ) ||
			! AddonService::instance()->check_addons_purchased_need_extend() ) {
			return '';
		}

		$version        = $lp_beta_version_info->version ?? '';
		$link_download  = $lp_beta_version_info->link_download ?? '';
		$link_feedback  = $lp_beta_version_info->link_feedback ?? '';
		$link_changelog = $lp_beta_version_info->link_changelog ?? '';
		$description    = __(
			'This version of the LearnPress software is under development. Please do not install, run, or test this version of LearnPress on production or mission-critical websites. Instead, it is recommended that you test Beta version on a staging site.',
			'learnpress'
		);

		$title = sprintf(
		/* translators: %s: beta version number */
			__( 'LearnPress %s Beta is available', 'learnpress' ),
			esc_html( $version )
		);

		$message = sprintf(
			'<div>%s</div>',
			sprintf(
				'<h3>%1$s</h3>
				<p>%2$s</p>
				<p>
					<a class="button" href="%3$s" target="_blank">%4$s</a>
					<a class="button" href="%5$s" target="_blank">%6$s</a>
					<a class="button" href="%7$s" target="_blank">%8$s</a>
				</p>%9$s',
				esc_html( $title ),
				esc_html( $description ),
				esc_url( $link_download ),
				__( 'Download', 'learnpress' ),
				esc_url( $link_feedback ),
				__( 'Feedback', 'learnpress' ),
				esc_url( $link_changelog ),
				__( 'Changelog', 'learnpress' ),
				self::html_button_dismiss( 'lp-beta-version' )
			)
		);

		return Template::print_message( $message, 'info', false );
	}

	/**
	 * Render a dismiss button for a notice.
	 *
	 * @param string $key Notice key.
	 *
	 * @return string
	 */
	public static function html_button_dismiss( string $key ): string {
		$data_send = wp_json_encode(
			array(
				'args'     => array( 'dismiss' => $key ),
				/** @use self::dismiss_notice */
				'callback' => array(
					'class'  => AdminNotesTemplate::class,
					'method' => 'dismiss_notice',
				),
			)
		);

		return sprintf(
			'<span class="btn-lp-notice-dismiss lp-icon-close"
				data-dismiss="%1$s"
				data-send="%2$s"
				data-message="%3$s"
				title="%4$s">
			</span>',
			esc_attr( $key ),
			esc_attr( $data_send ),
			esc_attr__( 'Are you sure you want to dismiss this notice?', 'learnpress' ),
			esc_attr__( 'Dismiss notice', 'learnpress' )
		);
	}

	/**
	 * Dismiss an admin notice.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return stdClass
	 */
	public static function dismiss_notice( array $data = [] ): stdClass {
		$content          = new stdClass();
		$content->content = '';
		$content->status  = Response::STATUS_SUCCESS;
		$content->message = '';

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception(
					__( 'You do not have permission to perform this action.', 'learnpress' )
				);
			}

			$key = sanitize_key( $data['dismiss'] ?? '' );
			if ( empty( $key ) ) {
				throw new Exception( __( 'The notice key is invalid.', 'learnpress' ) );
			}

			$admin_notices_dismiss         = LP_Settings::get_admin_notices_dismiss();
			$admin_notices_dismiss[ $key ] = $key;
			update_option( 'lp_admin_notices_dismiss', $admin_notices_dismiss );

			$content->message = __( 'Note Dismissed!', 'learnpress' );
		} catch ( Throwable $exception ) {
			$content->status  = Response::STATUS_ERROR;
			$content->message = $exception->getMessage();
		}

		return $content;
	}
}
