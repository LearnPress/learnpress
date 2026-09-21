<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Services\AddonService;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Admin Add-ons page renderer.
 *
 * @since 4.2.8
 * @version 1.0.1
 */
class AdminAddonsPage {
	use Singleton;

	/**
	 * Singleton initialization hook.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Render add-ons list for the AJAX response.
	 *
	 * @return stdClass
	 */
	public static function render_addons(): stdClass {
		$response = new stdClass();

		try {
			// Check permission
			if ( is_multisite() ? ! is_super_admin() : ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'Access denied.', 'learnpress' ) );
			}

			$addons = AddonService::instance()->get_addons();

			$response->content = learn_press_admin_view_content(
				'addons',
				array( 'addons' => $addons )
			);
		} catch ( Throwable $e ) {
			$response->content = Template::print_message( $e->getMessage(), Response::STATUS_ERROR, false );
		}

		return $response;
	}

	/**
	 * Render the LearnPress Add-ons page.
	 *
	 * @return void
	 */
	public function html_page() {
		ob_start();
		?>
		<p class="lp-addons-page-subtitle">
			<?php esc_html_e( 'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.', 'learnpress' ); ?>
		</p>
		<div class="lp-addons-page">
			<?php
			/** @use self::render_addons */
			echo TemplateAJAX::load_content_via_ajax(
				array(
					'id_url' => 'data-addons',
				),
				array(
					'class'  => self::class,
					'method' => 'render_addons',
				)
			);
			?>
		</div>
		<?php
		$content = ob_get_clean();

		echo AdminTemplate::html_on_wp_admin_screen(
			array(
				'content' => $content,
				'title'   => __( 'LearnPress Add-ons New', 'learnpress' ),
				'id'      => 'learn-press-addons',
			)
		);
	}
}
