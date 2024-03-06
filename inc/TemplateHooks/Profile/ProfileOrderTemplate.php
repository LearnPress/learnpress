<?php
/**
 * Class ProfileOrdersTemplate.
 *
 * @since 4.2.6.4
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Profile;

use LP_Profile;

class ProfileOrderTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	public static function content() {
		do_action( 'learn-press/profile/layout/order-detail' );
	}

	protected function __construct() {
		add_action( 'learn-press/profile/layout/order-detail', [ $this, 'sections' ] );
	}

	public static function init() {
		self::instance();
	}

	public function sections() {
		$profile = LP_Profile::instance();
		$order = $profile->get_view_order();
		if ( false === $order ) {
			return;
		}

		$can_view = false;
		if ( current_user_can( ADMIN_ROLE )) {
			$can_view = true;
		} elseif ( (int) $order->get_user_id() === get_current_user_id() ) {
			$can_view = true;
		}

		if ( ! $can_view ) {
			return;
		}

		learn_press_get_template( 'order/order-details.php', compact( 'order' ) );
	}
}
