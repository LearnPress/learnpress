<?php

use LearnPress\Databases\WebhookDB;
use LearnPress\Filters\WebhookFilter;
use LearnPress\Models\Webhook\WebhookModel;
use LearnPress\Webhook\WebhookEvents;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin table for LearnPress webhooks.
 */
class LP_Admin_Webhooks_Table_List extends WP_List_Table {
	/**
	 * Define visible columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'name'         => esc_html__( 'Name', 'learnpress' ),
			'status'       => esc_html__( 'Status', 'learnpress' ),
			'delivery_url' => esc_html__( 'Delivery URL', 'learnpress' ),
			'events'       => esc_html__( 'Events', 'learnpress' ),
			'updated_at'   => esc_html__( 'Updated', 'learnpress' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array<string, array{0:string,1:bool}>
	 */
	protected function get_sortable_columns() {
		return array(
			'name'       => array( 'name', false ),
			'status'     => array( 'status', false ),
			'updated_at' => array( 'updated_at', true ),
		);
	}

	/**
	 * Render name and row actions.
	 *
	 * @param object $item Webhook row.
	 *
	 * @return string
	 */
	protected function column_name( $item ) {
		$edit_data  = wp_json_encode(
			array(
				'webhook_id'   => absint( $item->webhook_id ),
				'name'         => (string) $item->name,
				'delivery_url' => (string) $item->delivery_url,
				'status'       => (string) $item->status,
				'events'       => $this->decode_events( $item->events ),
			)
		);
		$webhook_id = absint( $item->webhook_id );
		$actions    = array(
			'edit'       => sprintf(
				'<a href="#" class="lp-webhook-edit" data-webhook="%1$s">%2$s</a>',
				esc_attr( $edit_data ),
				esc_html__( 'Edit', 'learnpress' )
			),
			/*'regenerate' => sprintf(
				'<a href="#" class="lp-webhook-regenerate" data-webhook-id="%1$d">%2$s</a>',
				$webhook_id,
				esc_html__( 'Regenerate secret', 'learnpress' )
			),*/
			'delete'     => sprintf(
				'<a href="#" class="lp-webhook-delete" data-webhook-id="%1$d">%2$s</a>',
				$webhook_id,
				esc_html__( 'Delete', 'learnpress' )
			),
		);

		return sprintf( '<strong>%1$s</strong> %2$s', esc_html( (string) $item->name ), $this->row_actions( $actions ) );
	}

	/**
	 * Render status column.
	 *
	 * @param object $item Webhook row.
	 *
	 * @return string
	 */
	protected function column_status( $item ) {
		return WebhookModel::STATUS_ACTIVE === $item->status
			? esc_html__( 'Active', 'learnpress' )
			: esc_html__( 'Paused', 'learnpress' );
	}

	/**
	 * Render delivery URL column.
	 *
	 * @param object $item Webhook row.
	 *
	 * @return string
	 */
	protected function column_delivery_url( $item ) {
		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( (string) $item->delivery_url ),
			esc_html( (string) $item->delivery_url )
		);
	}

	/**
	 * Render selected event labels.
	 *
	 * @param object $item Webhook row.
	 *
	 * @return string
	 */
	protected function column_events( $item ) {
		$registry = WebhookEvents::all();
		$labels   = array();

		foreach ( $this->decode_events( $item->events ) as $event_key ) {
			$labels[] = $registry[ $event_key ] ?? $event_key;
		}

		return esc_html( implode( ', ', $labels ) );
	}

	/**
	 * Render last updated or created time.
	 *
	 * @param object $item Webhook row.
	 *
	 * @return string
	 */
	protected function column_updated_at( $item ) {
		$date = ! empty( $item->updated_at ) ? $item->updated_at : $item->created_at;

		return $date ? esc_html( get_date_from_gmt( (string) $date, 'Y-m-d H:i:s' ) ) : '&mdash;';
	}

	/**
	 * Render fallback columns.
	 *
	 * @param object $item        Webhook row.
	 * @param string $column_name Column key.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->{$column_name} ) ? esc_html( (string) $item->{$column_name} ) : '';
	}

	/**
	 * Render status filter.
	 *
	 * @param string $which Table position.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$status = sanitize_key( $_REQUEST['webhook_status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="webhook_status"><?php esc_html_e( 'Filter by status', 'learnpress' ); ?></label>
			<select id="webhook_status" name="webhook_status">
				<option value=""><?php esc_html_e( 'All statuses', 'learnpress' ); ?></option>
				<option value="active" <?php selected( $status, WebhookModel::STATUS_ACTIVE ); ?>><?php esc_html_e( 'Active', 'learnpress' ); ?></option>
				<option value="paused" <?php selected( $status, WebhookModel::STATUS_PAUSED ); ?>><?php esc_html_e( 'Paused', 'learnpress' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'learnpress' ), 'button', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Query and prepare table rows.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function prepare_items() {
		$status   = sanitize_key( $_REQUEST['webhook_status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby  = sanitize_key( $_REQUEST['orderby'] ?? 'updated_at' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = sanitize_key( $_REQUEST['order'] ?? 'desc' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search   = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_by = array(
			'name'       => 'w.' . WebhookFilter::COL_NAME,
			'status'     => 'w.' . WebhookFilter::COL_STATUS,
			'updated_at' => 'COALESCE(w.updated_at, w.created_at)',
		);

		$filter           = new WebhookFilter();
		$filter->limit    = 20;
		$filter->page     = $this->get_pagenum();
		$filter->key_word = $search;
		$filter->order_by = $order_by[ $orderby ] ?? $order_by['updated_at'];
		$filter->order    = 'asc' === strtolower( $order ) ? WebhookFilter::ORDER_ASC : WebhookFilter::ORDER_DESC;

		if ( in_array( $status, array( WebhookModel::STATUS_ACTIVE, WebhookModel::STATUS_PAUSED ), true ) ) {
			$filter->status = $status;
		}

		$total_items = 0;
		$items       = WebhookDB::getInstance()->get_webhooks( $filter, $total_items );

		$this->items           = is_array( $items ) ? $items : array();
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $filter->limit,
			)
		);
	}

	/**
	 * Render empty state.
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No webhooks found.', 'learnpress' );
	}

	/**
	 * Decode stored event JSON.
	 *
	 * @param mixed $events Stored events.
	 *
	 * @return string[]
	 */
	protected function decode_events( $events ): array {
		if ( is_string( $events ) ) {
			$events = json_decode( $events, true );
		}

		return is_array( $events ) ? WebhookEvents::sanitize( $events ) : array();
	}
}
