<?php
namespace LearnPress\TemplateHooks\Order;

use Exception;
use LearnPress\Databases\Order\LPOrderItemsDB;
use LearnPress\Filters\Order\OrderItemsFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CoursePostModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;
use LP_Order;
use Throwable;

/**
 * class AdminOrderItemsTemplate
 *
 * @since 4.3.2
 * @version 1.0.0
 */
class AdminOrderItemsTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', array( $this, 'allow_callback' ) );
		add_action( 'learn-press/admin/order-items/layout', array( $this, 'order_items_layout' ) );
		add_action( 'learn-press/admin/order-details/items/layout', array( $this, 'order_detail_items_layout' ) );
	}

	/**
	 * Allow callback for AJAX.
	 *
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
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function order_items_layout( LP_Order $lp_order ) {
		try {
			$args                            = array(
				'id_url'                            => 'admin-view-order-items',
				'order_id'                          => $lp_order->get_id(),
				'paged'                             => 1,
				'enableScrollToView'                => false,
				'enableUpdateParamsUrl'             => false,
				'html_loading_after_content_loaded' => '<i></i>',
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
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public static function render_order_items( array $data ): stdClass {
		$content  = new stdClass();
		$order_id = $data['order_id'] ?? 0;
		if ( ! $order_id ) {
			throw new Exception( __( 'Order ID is required', 'learnpress' ) );
		}

		$lpOrderItemsDB   = LPOrderItemsDB::getInstance();
		$filter           = new OrderItemsFilter();
		$filter->page     = $data['paged'] ?? 1;
		$filter->order_id = $data['order_id'] ?? 0;
		$filter->limit    = 5;
		$total_row        = 0;
		$items            = $lpOrderItemsDB->get_items( $filter, $total_row );

		$html_items = '';
		if ( empty( $items ) ) {
			$html_items = sprintf( '<li>%s</li>', __( '(No item)', 'learnpress' ) );
		} else {
			foreach ( $items as $i => $itemObj ) {
				// Get meta data
				$itemObjMeta   = self::get_all_metadata_order_item( $itemObj->order_item_id );
				$itemObj->meta = $itemObjMeta;
				$item_type     = $itemObj->item_type ?? '';
				$index         = ( $filter->page - 1 ) * $filter->limit + $i + 1;
				if ( $item_type === LP_COURSE_CPT ) {
					$coursePostModel = CoursePostModel::find_by_id( $itemObj->item_id, true );
					if ( ! $coursePostModel ) {
						$html_items .= sprintf(
							'<li>%s%s (%s)</li>',
							$total_row > 1 ? "$index. " : '',
							esc_html( $itemObj->order_item_name ),
							__( 'The course does not exist now.', 'learnpress' )
						);
					} else {
						$html_items .= sprintf(
							'<li>%s<a href="%s" >%s</a></li>',
							$total_row > 1 ? "$index. " : '',
							esc_url_raw( $coursePostModel->get_edit_link() ),
							esc_html( $itemObj->order_item_name )
						);
					}
				} else {
					if ( has_filter( 'learn-press/order-item-not-course-id' ) ) {
						$item_old       = (array) $itemObj;
						$item_old['id'] = $itemObj->order_item_id;
						$item_old       = array_merge( $item_old, $itemObjMeta );
						$html_items     = apply_filters( 'learn-press/order-item-not-course-id', esc_html__( 'The course does not exist', 'learnpress' ), $item_old );
					} else {
						$html_items = apply_filters( 'learn-press/order-items/item', '', $itemObj, $order_id, $data );
					}
				}
			}
		}

		$total_pages     = $lpOrderItemsDB::get_total_pages( $filter->limit, $total_row );
		$html_pagination = Template::instance()->html_pagination(
			[
				'paged'       => $data['paged'] ?? 1,
				'total_pages' => $total_pages,
			]
		);

		$section          = array(
			'wrap-start'  => '<div class="lp-order-items-wrapper">',
			'total_items' => $total_row > 1 ? sprintf(
				'<p class="total-items">%s: %d</p>',
				__( 'Total items', 'learnpress' ),
				$total_row
			) : '',
			'items'       => '<ul class="order-list-items">' . $html_items . '</ul>',
			'pagination'  => $html_pagination,
			'wrap-end'    => '<div>',
		);
		$content->content = Template::combine_components( $section );

		return $content;
	}

	public function order_detail_items_layout( LP_Order $lp_order ) {
		$args = array(
			'id_url'   => 'admin-view-order-detail-items',
			'order_id' => $lp_order->get_id(),
			'paged'    => 1,
		);

		/**
		 * @use self::render_order_detail_items
		 */
		$call_back = array(
			'class'  => self::class,
			'method' => 'render_order_detail_items',
		);
		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}

	/**
	 * Render html content for order detail items.
	 *
	 * @throws Exception
	 */
	public static function render_order_detail_items( array $data ): stdClass {
		$content  = new stdClass();
		$order_id = $data['order_id'] ?? 0;
		$paged    = $data['paged'] ?? 1;
		if ( ! $order_id ) {
			throw new Exception( __( 'Order ID is required', 'learnpress' ) );
		}

		$lp_order = learn_press_get_order( $order_id );
		if ( ! $lp_order ) {
			throw new Exception( __( 'Order not found', 'learnpress' ) );
		}

		$lpOrderDB        = LPOrderItemsDB::getInstance();
		$filter           = new OrderItemsFilter();
		$filter->order_id = $lp_order->get_id();
		$filter->page     = $paged;
		$filter->limit    = - 1;
		$total_row        = 0;
		$items            = $lpOrderDB->get_items( $filter, $total_row );

		$html_items = '';
		if ( ! empty( $items ) ) {
			foreach ( $items as $itemObj ) {
				$itemObjMeta   = self::get_all_metadata_order_item( $itemObj->order_item_id );
				$itemObj->meta = $itemObjMeta;
				$item_type     = $itemObj->item_type ?? '';

				if ( $item_type === LP_COURSE_CPT ) {
					$html_items .= self::order_item_detail_html( $lp_order, $itemObj );
				} else {
					if ( has_filter( 'learn-press/order-item-not-course-id' ) ) {
						$item_old       = (array) $itemObj;
						$item_old['id'] = $itemObj->order_item_id;
						$item_old       = array_merge( $item_old, $itemObjMeta );

						ob_start();
						do_action( 'learn-press/order-item-not-course', $item_old, $lp_order );
						$html_items .= ob_get_clean();
					} else {
						ob_start();
						do_action( 'learn-press/order-detail/item', $itemObj, $order_id, $data );
						$html_items .= ob_get_clean();
					}
				}
			}
		}

		$total_pages     = $lpOrderDB::get_total_pages( $filter->limit, $total_row );
		$html_pagination = Template::instance()->html_pagination(
			[
				'paged'       => $paged,
				'total_pages' => $total_pages,
			]
		);

		if ( ! empty( $html_pagination ) ) {
			$html_pagination = '<tr><td class="lp-order-items-wrapper" style="padding: 0 0 0 30px;" colspan="4">' . $html_pagination . '</td></tr>';
		}

		$section          = array(
			'wrap-start'       => '<div class="order-items lp-order-detail-items">',
			'table-start'      => '<table class="list-order-items">',
			'table-header'     => self::table_header(),
			'table-body-start' => '<tbody>',
			'items'            => $html_items,
			'pagination'       => $html_pagination,
			'no_item_row'      => sprintf(
				'<tr class="no-order-items %s">
					<td colspan="4">%s</td>
				</tr>',
				! empty( $items ) ? 'lp-hidden' : '',
				__( 'There are no order items', 'learnpress' )
			),
			'table-body-end'   => '</tbody>',
			'table-footer'     => self::table_footer( $lp_order ),
			'table-end'        => '</table>',
			'wrap-end'         => '</div>',
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
			'<thead>
				<tr>
					<th class="column-name">%1$s</th>
					<th class="column-price">%2$s</th>
					<th class="column-quantity">%3$s</th>
					<th class="column-total align-right">%4$s</th>
				</tr>
			</thead>',
			esc_html__( 'Item', 'learnpress' ),
			esc_html__( 'Cost', 'learnpress' ),
			esc_html__( 'Quantity', 'learnpress' ),
			esc_html__( 'Total', 'learnpress' )
		);

		return $content;
	}

	/**
	 * order item html
	 *
	 * @param LP_Order $order
	 * @param object $item lp order item
	 *
	 * @return string html
	 */
	public static function order_item_detail_html( $order, $item ): string {
		$coursePostModel = CoursePostModel::find( $item->item_id, true );
		$total           = $item->meta['_total'] ?? 0;
		$quantity        = $item->meta['_quantity'] ?? 0;
		$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() );
		$item_title      = $item->order_item_name;
		$item_link       = $coursePostModel ? $coursePostModel->get_edit_link() : '';
		ob_start();
		?>
		<tr class="order-item-row" data-item_id="<?php echo esc_attr( $item->order_item_id ); ?>"
			data-id="<?php echo esc_attr( $item->item_id ); ?>"
			data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
			<td class="column-name">
				<?php if ( $order->is_manual() && $order->get_status() === LP_ORDER_PENDING ) : ?>
					<a class="remove-order-item" href="#">
						<span class="dashicons dashicons-no-alt"></span>
					</a>
				<?php endif; ?>
				<?php
				if ( ! empty( $item_link ) ) {
					printf(
						'<a href="%s" >%s</a>',
						esc_url( $item_link ),
						esc_html( $item_title )
					);
				} else {
					echo esc_html( $item_title );
				}
				?>
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
		return ob_get_clean();
	}

	/**
	 * Order Details Footer
	 *
	 * @param LP_Order $order
	 *
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

	/**
	 * Get all meta data of order item
	 *
	 * @param int $order_item_id
	 *
	 * @return array
	 */
	public static function get_all_metadata_order_item( $order_item_id ): array {
		$meta_data = get_metadata( 'learnpress_order_item', $order_item_id, '', true );
		$result    = [];
		if ( is_array( $meta_data ) ) {
			foreach ( $meta_data as $key => $value ) {
				if ( is_array( $value ) && count( $value ) === 1 ) {
					$result[ $key ] = maybe_unserialize( $value[0] );
				} else {
					$result[ $key ] = maybe_unserialize( $value );
				}
			}
		}

		return $result;
	}
}
