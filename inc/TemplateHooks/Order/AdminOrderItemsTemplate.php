<?php
namespace LearnPress\TemplateHooks\Order;

use Exception;
use LearnPress\Databases\Order\LPOrderItemsDB;
use LearnPress\Filters\Order\LPOrderItemsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Order_DB;
use LP_Filter;
use stdClass;
use LP_Order;
use Throwable;

/**
 *
 */
final class AdminOrderItemsTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
		add_action( 'learn-press/admin/order-items/layout', array( $this, 'view_order_items_layout' ) );
		add_action( 'learn-press/admin/order-details/order-items/layout', array( $this, 'order_detail_view_items_layout' ), 10, 1 );
	}
	/**
	 * Allow callback for AJAX.
	 *
	 * @use self::render_order_items
	 * @use self::render_order_detail_items
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_order_items';
		$callbacks[] = get_class( $this ) . ':render_order_detail_items';

		return $callbacks;
	}

	/**
	 * HTML layout for order items on screen list orders.
	 *
	 * @throws Exception
	 * @since 4.2.8.8
	 * @version 1.0.0
	 */
	public function view_order_items_layout( LP_Order $lp_order ) {
		try {
			$args                            = array(
				'id_url'   => 'admin-view-order-items',
				'order_id' => $lp_order->get_id(),
				'paged'    => 1,
			);
			$content_obj                     = self::render_order_items( $args );
			$args['html_no_load_ajax_first'] = $content_obj->content;
			$call_back                       = array(
				'class'  => self::class,
				'method' => 'render_order_items',
			);
			echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
		} catch ( Throwable $e ) {

		}
	}

	/**
	 * Render html content for order items.
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 * @throws Exception
	 * @since 4.2.8.8
	 * @version 1.0.0
	 */
	public static function render_order_items( array $data ): stdClass {
		$content  = new stdClass();
		$order_id = $data['order_id'] ?? 0;
		if ( ! $order_id ) {
			throw new Exception( __( 'Order ID is required', 'learnpress' ) );
		}

		$lpOrderItemsDB   = LPOrderItemsDB::getInstance();
		$filter           = new LPOrderItemsFilter();
		$filter->page     = $data['paged'] ?? 1;
		$filter->order_id = $data['order_id'] ?? 0;
		$total_row        = 0;
		$items            = $lpOrderItemsDB->get_items( $filter, $total_row );

		$html_items = '';
		if ( empty( $items ) ) {
			$html_items = sprintf( '<li>%s</li>', __( 'No items found', 'learnpress' ) );
		} else {
			foreach ( $items as $itemObj ) {
				$item_post = get_post( $itemObj->item_id );
				if ( ! $item_post || empty( get_post_type_object( $itemObj->item_type ) ) ) {
					$html_items .= sprintf(
						'<li>(#%d - %s) %s: %s</li>',
						$itemObj->item_id,
						$itemObj->item_type,
						$itemObj->order_item_name,
						__( "doesn't exist", 'learnpress' )
					);
				} else {
					$html_items .= sprintf(
						'<li><a href="%1$s" >%2$s</a></li>',
						get_edit_post_link( $itemObj->item_id ),
						$itemObj->order_item_name
					);
				}
			}
		}

		$total_pages     = $lpOrderItemsDB::get_total_pages( $filter->limit, $total_row );
		$html_pagination = self::pagination_html( $data, $total_pages );

		$section          = array(
			'wrap-start' => '<div class="lp-order-items-wrapper">',
			'ul'         => '<ul class="order-list-items">',
			'items'      => $html_items,
			'ul-end'     => '</ul>',
			'pagination' => $html_pagination,
			'wrap-end'   => '<div>',
		);
		$content->content = Template::combine_components( $section );

		return $content;
	}
	/**
	 * [pagination_html description]
	 *
	 * @param  array   $args TemplateAJAX params
	 * @param  integer $total_pages
	 * @return string
	 */
	public static function pagination_html( $args, $total_pages ): string {
		$html_li_number = '';
		if ( $total_pages > 2 ) {
			$page_numbers       = paginate_links(
				apply_filters(
					'learn_press_pagination_args',
					array(
						'base'      => add_query_arg( 'paged', '%#%', \LP_Helper::getUrlCurrent() ),
						'format'    => '',
						'add_args'  => '',
						'current'   => max( 1, $args['paged'] ),
						'total'     => $total_pages,
						'prev_text' => '<i class="lp-icon-arrow-left"></i>',
						'next_text' => '<i class="lp-icon-arrow-right"></i>',
						'type'      => 'array',
						'end_size'  => 2,
						'mid_size'  => 2,
					)
				)
			);
			$html_li_number     = ! empty( $page_numbers ) ? implode(
				'',
				array_map(
					function ( $link ) {
						return '<li>' . $link . '</li>';
					},
					$page_numbers
				)
			) : '';
			$section_pagination = array(
				'wrap'     => '<ul class="pagination">',
				'numbers'  => $html_li_number,
				'wrap_end' => '</ul>',
			);
			return Template::combine_components( $section_pagination );
		} else {
			return '';
		}
	}

	public function order_detail_view_items_layout( LP_Order $lp_order ) {
		ob_start();
		lp_skeleton_animation_html( 10 );
		$html_loading = ob_get_clean();
		$args         = array(
			'id_url'   => 'admin-view-order-detail-items',
			'order_id' => $lp_order->get_id(),
			'paged'    => 1,
		);
		$call_back    = array(
			'class'  => self::class,
			'method' => 'render_order_detail_items',
		);
		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}
	/*
	 * Order item details html content
	 */
	public static function render_order_detail_items( $args ) {
		$content       = new stdClass();
		$limit         = 10;
		$lp_order_db   = LP_Order_DB::getInstance();
		$filter        = new LP_Filter();
		$filter->limit = $limit;
		$filter->page  = $args['paged'];
		$total_row     = 0;
		$items         = $lp_order_db->get_items( $filter, $args['order_id'], $total_row );
		$html_items    = '';
		$order         = learn_press_get_order( $args['order_id'] );
		if ( empty( $items ) ) {
			if ( ! $order->is_manual() ) {
				$html_items = sprintf( '<tr><td colspan="4">%s</td></tr>', __( 'No items found', 'learnpress' ) );
			}
		} else {
			foreach ( $items as $item ) {
				if ( ! get_post( $item->item_id ) || empty( get_post_type_object( $item->item_type ) ) ) {
					$html_items .= sprintf( '<tr><td>%s</td></tr>', __( 'The item doesn\'t exist', 'learnpress' ) );
				} else {
					$html_items .= self::order_item_detail_html( $order, $item );
				}
			}
		}
		$total_pages     = $lp_order_db::get_total_pages( $limit, $total_row );
		$html_pagination = self::pagination_html( $args, $total_pages );
		if ( ! empty( $html_pagination ) ) {
			$html_pagination = '<tr><td class="lp-order-items-wrapper" style="padding: 0 0 0 30px;" colspan="4">' . $html_pagination . '</td></tr>';
		}
		$section          = array(
			'table-start'      => '<table class="list-order-items">',
			'table-header'     => self::table_header(),
			'table-body-start' => '<tbody>',
			'items'            => $html_items,
			'pagination'       => $html_pagination,
			'no_item_row'      => self::no_item_row( $items ),
			'table-body-end'   => '</tbody>',
			'table-footer'     => self::table_footer( $order ),
			'table-end'        => '</table>',
		);
		$content->content = Template::combine_components( $section );

		return $content;
	}
	/**
	 * Table header
	 *
	 * @return string
	 */
	public static function table_header(): string {
		$content = sprintf(
			'<thead> <tr> <th class="column-name">%1$s</th> <th class="column-price">%2$s</th> <th class="column-quantity">%3$s</th> <th class="column-total align-right">%4$s</th> </tr> </thead>',
			esc_html( 'Item', 'learnpress' ),
			esc_html( 'Cost', 'learnpress' ),
			esc_html( 'Quantity', 'learnpress' ),
			esc_html( 'Total', 'learnpress' )
		);
		return $content;
	}
	/**
	 * no item row html
	 *
	 * @param  array $items all order items
	 * @return string
	 */
	public static function no_item_row( $items ): string {
		$content = sprintf(
			'<tr class="no-order-items %1$s">
				<td colspan="4">%2$s</td>
			</tr>',
			esc_attr( $items ? 'hide-if-js' : '' ),
			esc_html( 'There are no order items', 'learnpress' )
		);
		return $content;
	}
	/**
	 * order item html
	 *
	 * @param  LP_Order $order
	 * @param  object   $item lp order item
	 * @return string html
	 */
	public static function order_item_detail_html( $order, $item ): string {
		$content         = '';
		$total           = learn_press_get_order_item_meta( $item->order_item_id, '_total' );
		$total           = ! empty( $total ) ? $total : 0;
		$quantity        = learn_press_get_order_item_meta( $item->order_item_id, '_quantity' );
		$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() ?? learn_press_get_currency() );
		$item_title      = $item->order_item_name . '(' . get_post_type_object( $item->item_type )->labels->singular_name . ')';
		ob_start();
		?>
		<tr class="order-item-row" data-item_id="<?php echo esc_attr( $item->order_item_id ); ?>" data-id="<?php echo esc_attr( $item->item_id ); ?>" data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
			<td class="column-name">
				<?php if ( $order->is_manual() && $order->get_status() === LP_ORDER_PENDING ) : ?>
					<a class="remove-order-item" href="#">
						<span class="dashicons dashicons-no-alt"></span>
					</a>
				<?php endif; ?>
				<a href="<?php echo get_the_permalink( $item->order_item_id ); ?>">
					<?php echo esc_html( $item_title ); ?>
				</a>
			</td>

			<td class="column-price align-right">
				<?php echo learn_press_format_price( $total, $currency_symbol ); ?>
			</td>

			<td class="column-quantity align-right">
				<small class="times">×</small>
				<?php echo esc_html( $quantity ); ?>
			</td>

			<td class="column-total align-right"><?php echo learn_press_format_price( $total, $currency_symbol ); ?></td>
		</tr>
		<?php
		$content = ob_get_clean();
		return $content;
	}
	/**
	 * Order Details Footer
	 *
	 * @param  LP_Order $order
	 * @return string table footer
	 */
	public static function table_footer( $order ) {
		$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() ?? learn_press_get_currency() );
		ob_start();
		?>
		<tfoot>
		<tr>
			<td colspan="2"></td>
			<td colspan="2"></td>
		</tr>
		<tr class="row-subtotal">
			<td width="300" colspan="3" class="align-right">
				<?php esc_html_e( 'Subtotal:', 'learnpress' ); ?>
			</td>
			<td width="100" class="align-right">
					<span class="order-subtotal">
						<?php echo learn_press_format_price( $order->get_data( 'order_subtotal' ), $currency_symbol ); ?>
					</span>
			</td>
		</tr>
		<?php do_action( 'learn-press/admin/order/detail/before-total', $order ); ?>
		<tr class="row-total">
			<td class="align-right" colspan="3">
				<?php esc_html_e( 'Total:', 'learnpress' ); ?>
			</td>
			<td class="align-right total">
					<span class="order-total">
						<?php echo learn_press_format_price( $order->get_data( 'order_total' ), $currency_symbol ); ?>
					</span>
			</td>
		</tr>
		<tr>
			<td colspan="2"></td>
			<td colspan="2" style="border-bottom: 1px dashed #DDD;"></td>
		</tr>
		<?php if ( $order->is_manual() ) : ?>
			<tr>
				<td class="align-right" colspan="4" style="border-top: 1px solid #DDD;">
					<?php if ( 'pending' === $order->get_status() ) : ?>
						<button class="button" type="button" id="learn-press-add-order-item">
							<?php esc_html_e( 'Add item(s)', 'learnpress' ); ?>
						</button>
					<?php else : ?>
						<p class="description"> <?php esc_html_e( 'In order to change the order item, please change the order status to \'Pending\'.', 'learnpress' ); ?> </p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
		</tfoot>
		<?php
		return ob_get_clean();
	}
}
?>
