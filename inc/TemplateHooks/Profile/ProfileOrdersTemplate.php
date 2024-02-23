<?php
/**
 * Class ProfileOrdersTemplate.
 *
 * @since 4.2.6.2
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Template;

class ProfileOrdersTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	public static function tab_content() {
		do_action( 'learn-press/profile/layout/orders' );
	}

	protected function __construct() {
		add_action( 'learn-press/profile/layout/orders', [ $this, 'sections' ], 2 );
	}

	public static function init() {
		self::instance();
	}

	public function sections( $data ) {
		echo '<div class="profile-orders">';
		Template::instance()->get_template(
			LP_PLUGIN_PATH . 'templates/profile/tabs/orders/list.php'
		);
		Template::instance()->get_template(
			LP_PLUGIN_PATH . 'templates/profile/tabs/orders/recover-order.php'
		);
		echo '</div>';
	}
}
