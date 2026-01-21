<?php
/**
 * Class ProfileOrdersTemplate.
 *
 * @since 4.2.6.4
 * @version 1.0.2
 */

namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Models\UserModel;
use LP_Order;
use LP_Profile;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Order_DB;
use LP_Filter;
use stdClass;

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
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
	}

	public static function init() {
		self::instance();
	}

	public function sections() {
		$profile = LP_Profile::instance();
		$order   = $profile->get_view_order();
		if ( false === $order ) {
			return;
		}

		$can_view = false;
		if ( current_user_can( ADMIN_ROLE ) ) {
			$can_view = true;
		} elseif ( (int) $order->get_user_id() === get_current_user_id() ) {
			$can_view = true;
		}

		if ( ! $can_view ) {
			return;
		}

		if ( ! isset( $order ) ) {
			echo esc_html__( 'Invalid order', 'learnpress' );

			return;
		}

		$args      = array(
			'id_url'   => 'lp-profile-view-order-details',
			'order_id' => $order->get_id(),
			'paged'    => 1,
		);
		$call_back = array(
			'class'  => self::class,
			'method' => 'render_order_details',
		);
		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @use self::render_order_details
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_order_details';

		return $callbacks;
	}

	/**
	 * Render order details content via AJAX.
	 *
	 * @param array $args
	 *
	 * @return stdClass
	 * @since 4.3.2
	 * @version 1.0.1
	 */
	public static function render_order_details( array $args ): stdClass {
		$content = new stdClass();

		$order_id = $args['order_id'] ?? 0;
		$paged    = $args['paged'] ?? 1;

		$can_view = false;
		$user_id  = get_current_user_id();

		if ( current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
			$can_view = true;
		} else {
			$order = learn_press_get_order( $order_id );
			if ( $order && (int) $order->get_user_id() === $user_id ) {
				$can_view = true;
			}
		}

		if ( ! $can_view ) {
			return $content;
		}

		$limit         = 10;
		$lp_order_db   = LP_Order_DB::getInstance();
		$filter        = new LP_Filter();
		$filter->limit = $limit;
		$filter->page  = $paged;
		$total_row     = 0;
		$items         = $lp_order_db->get_items( $filter, $order_id, $total_row );
		$html_items    = '';
		$order         = learn_press_get_order( $order_id );

		if ( empty( $items ) ) {
			$html_items = sprintf(
				'<tr class="order-item"><td colspan="4">%s</td></tr>',
				__( 'No items found', 'learnpress' )
			);
		} else {
			foreach ( $items as $item ) {
				$item_course_id_meta = learn_press_get_order_item_meta( $item->order_item_id, '_course_id' );
				// For old data, the data not migrated yet to new column.
				if ( ( empty( $item->item_type ) || empty( $item->item_id ) )
					&& ! empty( $item_course_id_meta ) ) {
					$item->item_id   = $item_course_id_meta;
					$item->item_type = LP_COURSE_CPT;
				}

				$html_items .= self::order_item_row_html( $order, $item );
			}
		}
		$total_pages     = $lp_order_db::get_total_pages( $limit, $total_row );
		$html_pagination = Template::instance()->html_pagination(
			[
				'total_pages' => $total_pages,
				'paged'       => $paged,
			]
		);
		if ( ! empty( $html_pagination ) ) {
			$html_pagination = sprintf(
				'<tr class="order-item">
					<td class="lp-order-items-wrapper" style="margin:0;text-align:left;" colspan="2">%s</td>
				</tr>',
				$html_pagination
			);
		}

		$section          = array(
			'header'          => sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Order Details', 'learnpress' )
			),
			'table'           => '<table class="lp-list-table order-table-details">',
			'table-header'    => self::table_header(),
			'table-body'      => '<tbody>',
			'items'           => $html_items,
			'pagination'      => $html_pagination,
			'do_action_items' => self::action_table_items( $order ),
			'table-body-end'  => '</tbody>',
			'table-footer'    => self::table_footer( $order ),
			'table-end'       => '</table>',
			'footer'          => self::order_detail_content_footer( $order ),
		);
		$content->content = Template::combine_components( $section );

		return $content;
	}

	public static function table_header() {
		$section = array(
			'wrap'     => '<thead>',
			'row'      => sprintf(
				'<tr>
					<th>%s</th>
					<th>%s</th>
				</tr>',
				__( 'Item', 'learnpress' ),
				__( 'Total', 'learnpress' )
			),
			'wrap-end' => '</thead>',
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML for table footer in order detail page.
	 *
	 * @param LP_Order $order
	 *
	 * @return string
	 * @since 4.3.2
	 * @version 1.0.1
	 */
	public static function table_footer( $order ): string {
		ob_start();
		do_action( 'learn-press/order/items-table-foot', $order );
		$html_after_subtotal_row = ob_get_clean();

		$section = array(
			'wrap'         => '<tfoot>',
			'subtotal_row' => sprintf(
				'<tr><th scope="row">%s</th><td>%s</td></tr>',
				esc_html__( 'Subtotal', 'learnpress' ),
				esc_html( $order->get_formatted_order_subtotal() )
			),
			'action'       => $html_after_subtotal_row,
			'total_row'    => sprintf(
				'<tr><th scope="row">%s</th><td>%s</td></tr>',
				esc_html__( 'Total', 'learnpress' ),
				esc_html( $order->get_formatted_order_total() )
			),
			'wrap-end'     => '</tfoot>',
		);

		return Template::combine_components( $section );
	}

	public static function action_table_items( $order ) {
		ob_start();
		do_action( 'learn-press/order/items-table', $order );

		return ob_get_clean();
	}

	/**
	 * HTMl for each order item row in order detail page.
	 *
	 * @param LP_Order $order
	 * @param object $item
	 *
	 * @return string
	 * @since 4.3.2
	 * @version 1.0.1
	 */
	public static function order_item_row_html( $order, $item ): string {
		$total           = learn_press_get_order_item_meta( $item->order_item_id, '_total' );
		$total           = ! empty( $total ) ? $total : 0;
		$quantity        = learn_press_get_order_item_meta( $item->order_item_id, '_quantity' );
		$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() ?? learn_press_get_currency() );
		$item_label      = esc_html( $item->order_item_name );
		if ( $item->item_type === LP_COURSE_CPT ) {
			$item_label = sprintf(
				'<a href="%s">%s</a>',
				esc_url_raw( get_permalink( $item->item_id ) ),
				esc_html( $item->order_item_name )
			);
		}
		$item_label = apply_filters(
			'learn-press/profile/order/item-label',
			$item_label,
			(array) $item,
			$order
		);

		$section = apply_filters(
			'learn-press/profile/order/item-row-html',
			array(
				'wrap'       => '<tr class="order-item">',
				'name-cell'  => sprintf(
					'<td>%s</td>',
					$item_label
				),
				'total-cell' => sprintf(
					'<td><span class="course-price">%s</span></td>',
					learn_press_format_price( $total, $currency_symbol )
				),
				'wrap-end'   => '</tr>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML content footer, below table list items for order detail page.
	 *
	 * @param LP_Order $order
	 *
	 * @return string
	 * @since 4.3.2
	 * @version 1.0.1
	 */
	public static function order_detail_content_footer( $order ): string {
		$section = array(
			'order_key'    => sprintf(
				'<p><strong>%s</strong> %s</p>',
				esc_html__( 'Order key:', 'learnpress' ),
				esc_html( $order->get_order_key() )
			),
			'order_status' => sprintf(
				'<p>
					<strong>%s</strong>
					<span class="lp-label label-%s">%s</span>
				</p>',
				esc_html__( 'Order status:', 'learnpress' ),
				esc_attr( $order->get_status() ),
				wp_kses_post( $order->get_order_status_html() )
			),
		);

		return Template::combine_components( $section );
	}
}
