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
	 * Table columns.
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
	 * Sortable columns.
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
	 * Bulk actions.
	 */
	protected function get_bulk_actions() {
		return array(
			'bulk-revoke' => esc_html__( 'Revoke', 'learnpress' ),
		);
	}

	/**
	 * Render checkbox column.
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="key_ids[]" value="%d" />', absint( $item->key_id ) );
	}

	/**
	 * Render description with row actions.
	 */
	protected function column_description( $item ) {
		$description = $item->description ? $item->description : esc_html__( '(No description)', 'learnpress' );

		$base_url = add_query_arg(
			array(
				'page'    => 'learn-press-settings',
				'tab'     => 'advanced',
				'section' => 'mcp-keys',
			),
			admin_url( 'admin.php' )
		);

		$edit_url = add_query_arg(
			array(
				'edit_key' => absint( $item->key_id ),
			),
			$base_url
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
			'edit'       => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'learnpress' ) ),
			'regenerate' => sprintf(
				'<a href="#" class="lp-mcp-regenerate-key" data-key-id="%1$d">%2$s</a>',
				absint( $item->key_id ),
				esc_html__( 'Regenerate', 'learnpress' )
			),
			'revoke'     => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $revoke_url ),
				esc_js( __( 'Revoke this API key?', 'learnpress' ) ),
				esc_html__( 'Revoke', 'learnpress' )
			),
		);

		return sprintf( '%1$s %2$s', esc_html( $description ), $this->row_actions( $actions ) );
	}

	/**
	 * Default column renderer.
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
	 * Search + filter controls.
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
	 * Prepare list data.
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
	 * Empty state.
	 */
	public function no_items() {
		echo esc_html__( 'No MCP API keys found.', 'learnpress' );
	}
}
