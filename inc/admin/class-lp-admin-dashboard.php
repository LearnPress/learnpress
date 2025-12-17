<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\TemplateAJAX;

if ( ! class_exists( 'LP_Admin_Dashboard' ) ) {
	/**
	 * Class LP_Admin_Dashboard
	 *
	 * Displays widgets in admin.
	 */
	class LP_Admin_Dashboard {
		/**
		 * LP_Admin_Dashboard constructor.
		 */
		public function __construct() {
			// Ignore heartbeat requests.
			if ( isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) {
				return;
			}

			add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
		}

		public function register() {
			$screens = [
				'learn_press_dashboard_order_statuses' => [
					'label'    => esc_html__( 'LearnPress order status', 'learnpress' ),
					'callback' => [ $this, 'order_statuses' ],
				],
				'learn_press_dashboard_plugin_status'  => [
					'label'    => esc_html__( 'LearnPress status', 'learnpress' ),
					'callback' => [ $this, 'plugin_status' ],
				],
			];

			foreach ( $screens as $id => $screen ) {
				wp_add_dashboard_widget(
					$id,
					$screen['label'],
					$screen['callback']
				);
			}
		}

		/**
		 * Order status widget
		 */
		public function order_statuses() {
			$args = [
				'id_url' => 'order-statistic-dashboard',
			];

			/**
			 * @uses order_statistic
			 */
			$callback = [
				'class'  => self::class,
				'method' => 'order_statistic',
			];

			echo TemplateAJAX::load_content_via_ajax( $args, $callback );
		}

		/**
		 * Get order statistic content
		 *
		 * @return stdClass
		 */
		public static function order_statistic(): stdClass {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learnpress' ) );
			}

			$order_statuses = LP_Order::get_order_statuses();
			$lp_order_icons = LP_Order::get_icons_status();

			ob_start();
			$data = compact( 'order_statuses', 'lp_order_icons' );
			Template::instance()->get_admin_template( 'dashboard/html-orders', $data );
			$content          = new stdClass();
			$content->content = sprintf(
				'<ul class="lp-order-statuses lp_append_data">%s</ul>',
				ob_get_clean()
			);
			return $content;
		}

		/**
		 * Plugin status widget
		 */
		public function plugin_status() {
			$args = [
				'id_url' => 'plugin-status-dashboard',
			];

			/**
			 * @uses plugin_status_content
			 */
			$callback = [
				'class'  => self::class,
				'method' => 'plugin_status_content',
			];

			echo TemplateAJAX::load_content_via_ajax( $args, $callback );
		}

		public static function plugin_status_content() {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learnpress' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => 'learnpress',
					'fields' => array(
						'active_installs'   => true,
						'short_description' => true,
						'description'       => true,
						'ratings'           => true,
						'downloaded'        => true,
					),
				)
			);

			$section = [
				'wrap'            => '<div class="lp-plugin-status-wrap">',
				'banner'          => sprintf(
					'<image class="lp-plugin-banner" src="%s" style="%s" />',
					$api->banners ? ( $api->banners['low'] ?? '' ) : '',
					'max-width:100%;height:auto;'
				),
				'active_installs' => sprintf(
					'<div class="lp-plugin-active-installs">%s: <strong>%s</strong></div>',
					esc_html__( 'Active Installations', 'learnpress' ),
					number_format_i18n( $api->active_installs )
				),
				'downloaded'      => sprintf(
					'<div class="lp-plugin-downloaded">%s: <strong>%s</strong></div>',
					esc_html__( 'Total Downloads', 'learnpress' ),
					number_format_i18n( $api->downloaded )
				),
				'ratings'         => sprintf(
					'<div class="lp-plugin-ratings">%s <strong>%s</strong></div>',
					esc_html__( 'Ratings 5 stars is:', 'learnpress' ),
					$api->ratings[5] ?? '0'
				),
				'requires'        => sprintf(
					'<div class="lp-plugin-requires">%s %s</div>',
					esc_html__( 'Required WordPress version:', 'learnpress' ),
					$api->requires ?? esc_html__( 'N/A', 'learnpress' )
				),
				'requires_php'    => sprintf(
					'<div class="lp-plugin-requires_php">%s %s</div>',
					esc_html__( 'Required PHP version:', 'learnpress' ),
					$api->requires_php ?? esc_html__( 'N/A', 'learnpress' )
				),
				'tested'          => sprintf(
					'<div class="lp-plugin-tested">%s %s</div>',
					esc_html__( 'Tested with WordPress version:', 'learnpress' ),
					$api->tested ?? esc_html__( 'N/A', 'learnpress' )
				),
				'last_updated'    => sprintf(
					'<div class="lp-plugin-last_updated">%s %s</div>',
					esc_html__( 'Latest updated:', 'learnpress' ),
					$api->last_updated ?? esc_html__( 'N/A', 'learnpress' )
				),
				'wrap-end'        => '</div>',
			];

			$content          = new stdClass();
			$content->content = Template::combine_components( $section );
			return $content;
		}
	}
}
new LP_Admin_Dashboard();
