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
		$callbacks[] = self::class . ':render_addons_need_extend';

		return $callbacks;
	}

	/**
	 * Render paginated list of course items (lesson, quiz, etc.) for reset progress.
	 *
	 * @param array $data Request arguments.
	 *
	 * @return stdClass
	 * @since 4.4.9
	 * @version 1.0.0
	 */
	public static function render_addons_need_extend( array $data ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception(
					__( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$admin_notices_dismiss = get_option( 'lp_admin_notices_dismiss', [] );
			if ( isset( $admin_notices_dismiss['lp-addons-purchased-extend'] ) ||
				! AddonService::instance()->check_addons_purchased_need_extend() ) {
				return $content;
			}

			$button_dismiss = self::html_button_dismiss( 'lp-addons-purchased-extend' );


			$content->content = Template::print_message(
				$button_dismiss . sprintf(
					'%1$s <strong><a style="color: #E64B50" href="%2$s">%3$s</a></strong>',
					esc_html__( 'You have LearnPress Add-on licenses that need to be extended.', 'learnpress' ),
					esc_url( admin_url( 'admin.php?page=learn-press-addons&tab=license' ) ),
					esc_html__( 'Check now!', 'learnpress' )
				),
				'info',
				false
			);
		} catch ( Throwable $exception ) {
			$content->content = Template::print_message(
				$exception->getMessage(),
				Response::STATUS_ERROR,
				false
			);
		}

		return $content;
	}

	public static function html_button_dismiss( string $key ): string {
		ob_start();
		Template::instance()->get_admin_template(
			'admin-notices/button-dismiss.php',
			[ 'key' => $key ]
		);

		return ob_get_clean();
	}
}
