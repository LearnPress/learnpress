<?php

namespace LearnPress\TemplateHooks\Order;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Helper;

/**
 * class AdminOrderListTemplate
 *
 * @since 4.3.2.8
 * @version 1.0.0
 */
class AdminOrderListTemplate {

	use Singleton;

	public function init() {
		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_export_order_button' ) );
	}

	public function add_export_order_button( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== LP_ORDER_CPT ) {
			return;
		}

		$order_data = [
			'action'    => 'export_order_csv',
			'export_id' => time() . '-' . uniqid(),
		];

		$data_get   = LP_Helper::sanitize_params_submitted( $_GET );
		$order_data = array_merge( $data_get, $order_data );

		$section = [
			'wrap-start'       => '<div class="alignleft actions">',
			'btn-export-start' => sprintf(
				'<button type="button" class="button lp-button lp-btn-export-order-to-csv" data-send="%s">',
				esc_attr( Template::convert_data_to_json( $order_data ) )
			),
			'btn-text'         => esc_html__( 'Export to CSV', 'learnpress' ),
			'btn-export-end'   => '</button>',
			'wrap-end'         => '</div>',
		];

		echo Template::combine_components( $section );
	}
}
