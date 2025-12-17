<?php
/**
 * Class ProfileOrdersTemplate.
 *
 * @since 4.2.6.4
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Models\UserModel;
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
	 * @use self::render_order_detail_items
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
	 */
	public static function render_order_details( array $args ) {
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
			$html_items = sprintf( '<tr class="order-item"><td colspan="4">%s</td></tr>', __( 'No items found', 'learnpress' ) );
		} else {
			foreach ( $items as $item ) {
				if ( ! get_post( $item->item_id ) || empty( get_post_type_object( $item->item_type ) ) ) {
					$html_items .= sprintf( '<tr class="order-item"><td>%s</td></tr>', __( 'The item doesn\'t exist', 'learnpress' ) );
				} else {
					$html_items .= self::order_item_row_html( $order, $item );
				}
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
			$html_pagination = '<tr class="order-item"><td class="lp-order-items-wrapper" style="margin:0;text-align:left;" colspan="2">' . $html_pagination . '</td></tr>';
		}
		$section          = array(
			'tab-content-header' => '<h3>' . esc_html( 'Order Details', 'learnpress' ) . '</h3>',
			'table-start'        => '<table class="lp-list-table order-table-details">',
			'table-header'       => self::table_header(),
			'table-body-start'   => '<tbody>',
			'items'              => $html_items,
			'pagination'         => $html_pagination,
			'do_action_items'    => self::action_table_items( $order ),
			'table-body-end'     => '</tbody>',
			'table-footer'       => self::table_footer( $order ),
			'table-end'          => '</table>',
			'footer'             => self::order_detail_content_footer( $order ),
		);
		$content->content = Template::combine_components( $section );

		return $content;
	}

	public static function table_header() {
		$content = sprintf(
			'<thead> <tr> <th class="course-name">%1$s</th> <th class="course-total">%2$s</th> </tr> </thead>',
			esc_html( 'Item', 'learnpress' ),
			esc_html( 'Total', 'learnpress' )
		);

		return $content;
	}

	public static function table_footer( $order ) {
		ob_start();
		?>
		<tfoot>
		<tr>
			<th scope="row"><?php esc_html_e( 'Subtotal', 'learnpress' ); ?></th>
			<td><?php echo esc_html( $order->get_formatted_order_subtotal() ); ?></td>
		</tr>

		<?php do_action( 'learn-press/order/items-table-foot', $order ); ?>

		<tr>
			<th scope="row"><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
			<td><?php echo esc_html( $order->get_formatted_order_total() ); ?></td>
		</tr>
		</tfoot>
		<?php
		return ob_get_clean();
	}

	public static function action_table_items( $order ) {
		ob_start();
		do_action( 'learn-press/order/items-table', $order );

		return ob_get_clean();
	}

	public static function order_item_row_html( $order, $item ): string {
		$content         = '';
		$total           = learn_press_get_order_item_meta( $item->order_item_id, '_total' );
		$total           = ! empty( $total ) ? $total : 0;
		$quantity        = learn_press_get_order_item_meta( $item->order_item_id, '_quantity' );
		$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() ?? learn_press_get_currency() );
		$item_title      = $item->order_item_name . '(' . get_post_type_object( $item->item_type )->labels->singular_name . ')';
		ob_start();
		?>
		<tr class="order-item">
			<td class="course-name">
				<?php
				echo sprintf(
					'<a href="%s">%s</a>',
					esc_url_raw( get_permalink( $item->order_item_id ) ),
					esc_html( $item_title )
				);
				?>
			</td>

			<td class="course-total">
				<span class="course-price"><?php echo learn_press_format_price( $total, $currency_symbol ); ?></span>
			</td>
		</tr>
		<?php
		$content = ob_get_clean();

		return $content;
	}

	public static function order_detail_content_footer( $order ) {
		ob_start();
		?>
		<p>
			<strong><?php echo esc_html__( 'Order key:', 'learnpress' ); ?></strong>
			<?php echo esc_html( $order->get_order_key() ); ?>
		</p>

		<p>
			<strong><?php esc_html_e( 'Order status:', 'learnpress' ); ?></strong>
			<span class="lp-label label-<?php echo esc_attr( $order->get_status() ); ?>">
				<?php echo wp_kses_post( $order->get_order_status_html() ); ?>
			</span>
		</p>
		<?php
		do_action( 'learn-press/order/after-table-details', $order );

		return ob_get_clean();
	}
}
