<?php

namespace LearnPress\TemplateHooks\Order;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

/**
 * class AdminOrderListTemplate
 *
 * @since 4.3.2
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

		$export_id = get_current_user_id() . '_' . wp_generate_uuid4();

		$order_data = [
			'action'      => 'export_order_csv',
			'export_id'   => $export_id,
			'post_status' => sanitize_key( $_GET['post_status'] ?? '' ),
			'author'      => (int) ( $_GET['author'] ?? 0 ),
			's'           => sanitize_text_field( $_GET['s'] ?? '' ),
			'm'           => (int) ( $_GET['m'] ?? 0 ),
			'paged'       => 1,
			'_wpnonce'    => wp_create_nonce( 'lp_export_order' ),
		];

		if ( empty( $_GET['orderby'] ) ) {
			$order_data['orderby'] = 'ID';
		} else {
			if ( $_GET['orderby'] === 'title' ) {
				$order_data['orderby'] = 'ID';
			} else {
				$order_data['orderby'] = sanitize_key( $_GET['orderby'] );
			}
		}

		$order               = strtolower( $_GET['order'] ?? 'desc' );
		$order               = in_array( $order, [ 'asc', 'desc' ], true ) ? $order : '';
		$order_data['order'] = $order;

		$components = [
			'wrap-start'       => '<div class="alignleft actions">',
			'btn-export-start' => '<button type="button" class="button lp-button export" data-send="' . esc_attr( Template::convert_data_to_json( $order_data ) ) . '">',
			'btn-text'         => esc_html__( 'Export', 'learnpress' ),
			'btn-export-end'   => '</button>',
			'wrap-end'         => '</div>',
		];

		echo Template::combine_components( $components );
	}
}
