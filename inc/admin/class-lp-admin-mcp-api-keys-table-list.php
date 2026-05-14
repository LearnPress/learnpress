<?php

use LearnPress\MCP\Auth\ApiKeysRepository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin table for LearnPress MCP API keys.
 */
class LP_Admin_MCP_API_Keys_Table_List extends WP_List_Table {
	/**
	 * @var ApiKeysRepository
	 */
	protected $repository;

	/**
	 * @var array<int, object>
	 */
	protected $users_with_keys = array();

	/**
	 * Initialize list-table with MCP keys repository dependency.
	 *
	 * @param ApiKeysRepository $repository Repository for querying and mutating key records.
	 *
	 * @return void
	 */
	public function __construct( ApiKeysRepository $repository ) {
		$this->repository = $repository;

		parent::__construct(
			array(
				'singular' => 'lp_mcp_api_key',
				'plural'   => 'lp_mcp_api_keys',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define visible columns for MCP API key table.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'description' => esc_html__( 'Description', 'learnpress' ),
			'key_ending'  => esc_html__( 'Key Ending', 'learnpress' ),
			'user'        => esc_html__( 'User', 'learnpress' ),
			'permissions' => esc_html__( 'Permissions', 'learnpress' ),
			'last_access' => esc_html__( 'Last Access', 'learnpress' ),
			'call_count'  => esc_html__( 'Calls', 'learnpress' ),
		);
	}

	/**
	 * Define sortable columns and default sort directions.
	 *
	 * @return array<string, array{0:string,1:bool}>
	 */
	protected function get_sortable_columns() {
		return array(
			'description' => array( 'description', false ),
			'user'        => array( 'user', false ),
			'permissions' => array( 'permissions', false ),
			'last_access' => array( 'last_access', true ),
			'call_count'  => array( 'call_count', true ),
		);
	}

	/**
	 * Define supported bulk actions for selected keys.
	 *
	 * @return array<string, string>
	 */
	protected function get_bulk_actions() {
		return array(
			'bulk-revoke' => esc_html__( 'Revoke', 'learnpress' ),
		);
	}

	/**
	 * Render row checkbox used by bulk actions.
	 *
	 * @param object $item Current key row object.
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="key_ids[]" value="%d" />', absint( $item->key_id ) );
	}

	/**
	 * Render description column with inline row actions.
	 *
	 * Row actions include revoke link.
	 *
	 * @param object $item Current key row object.
	 *
	 * @return string
	 */
	protected function column_description( $item ) {
		$description = $item->description ? $item->description : esc_html__( '(No description)', 'learnpress' );

		$base_url = add_query_arg(
			array(
				'page'    => 'learn-press-settings',
				'tab'     => 'mcp',
			),
			admin_url( 'admin.php' )
		);

		$revoke_url = wp_nonce_url(
			add_query_arg(
				array(
					'lp_mcp_key_action' => 'revoke',
					'key_id'            => absint( $item->key_id ),
				),
				$base_url
			),
			'lp_mcp_revoke_key_' . absint( $item->key_id )
		);

		$actions = array(
			'revoke' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $revoke_url ),
				esc_js( __( 'Revoke this API key?', 'learnpress' ) ),
				esc_html__( 'Revoke', 'learnpress' )
			),
		);

		return sprintf( '%1$s %2$s', esc_html( $description ), $this->row_actions( $actions ) );
	}

	/**
	 * Render non-custom columns by column key.
	 *
	 * @param object $item        Current key row object.
	 * @param string $column_name Current column key.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'key_ending':
				return '...' . esc_html( (string) $item->truncated_key );
			case 'user':
				$name = $item->user_display_name ?: $item->user_login;
				if ( ! $name ) {
					$name = esc_html__( '(Missing user)', 'learnpress' );
				}

				return esc_html( $name );
			case 'permissions':
				return esc_html( (string) $item->permissions );
			case 'last_access':
				if ( empty( $item->last_access ) ) {
					return '&mdash;';
				}

				return esc_html( get_date_from_gmt( (string) $item->last_access, 'Y-m-d H:i:s' ) );
			case 'call_count':
				return esc_html( (string) absint( $item->call_count ) );
		}

		return '';
	}

	/**
	 * Render extra controls above table: user filter dropdown.
	 *
	 * @param string $which Table position (`top` or `bottom`).
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$user_filter = absint( $_REQUEST['mcp_user_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="mcp_user_id">' . esc_html__( 'Filter by user', 'learnpress' ) . '</label>';
		echo '<select id="mcp_user_id" name="mcp_user_id">';
		echo '<option value="0">' . esc_html__( 'All users', 'learnpress' ) . '</option>';

		foreach ( $this->users_with_keys as $user ) {
			echo '<option value="' . esc_attr( $user->ID ) . '" ' . selected( $user_filter, absint( $user->ID ), false ) . '>' . esc_html( $user->display_name ) . '</option>';
		}

		echo '</select>';

		submit_button( __( 'Filter', 'learnpress' ), 'button', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Query and prepare list-table items and pagination metadata.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$search   = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id  = absint( $_REQUEST['mcp_user_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby  = sanitize_key( $_REQUEST['orderby'] ?? 'created_at' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = sanitize_key( $_REQUEST['order'] ?? 'desc' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_num = $this->get_pagenum();

		$results = $this->repository->query_keys(
			array(
				'page'     => $page_num,
				'per_page' => 20,
				'search'   => $search,
				'user_id'  => $user_id,
				'orderby'  => $orderby,
				'order'    => $order,
			)
		);

		$this->items           = $results['items'];
		$this->users_with_keys = $this->repository->users_with_keys();

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $results['total'],
				'per_page'    => 20,
			)
		);
	}

	/**
	 * Render empty state when no keys match current filters.
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No MCP API keys found.', 'learnpress' );
	}
}
