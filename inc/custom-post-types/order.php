<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !class_exists( 'LP_Order_Post_Type' ) ) {

	// Base class for custom post type to extends
	learn_press_include( 'custom-post-types/abstract.php' );

	// class LP_Order_Post_Type
	final class LP_Order_Post_Type extends LP_Abstract_Post_Type {
		function __construct() {
			//add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'init', array( $this, 'register_post_statues' ) );
			/*Add Coulumn*/
			add_filter( 'manage_edit-lp_order_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_lp_order_posts_custom_column', array( $this, 'columns_content' ) );

			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
			add_filter( 'page_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
			add_filter( 'manage_edit-lp_order_sortable_columns', array( $this, 'sortable_columns' ) );

			// Disable Auto Save
			add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );
			add_action( 'admin_init', array( $this, 'remove_box' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			add_filter( 'admin_footer', array( $this, 'admin_footer' ) );

			/*add_action( 'wp_ajax_update_order_status', array( $this, 'update_status' ) );
			add_action( 'admin_head', array( $this, 'admin_head' ) );

			add_action( 'wp_trash_post', array( $this, 'preparing_to_trash_order' ) );
			*/
			//add_action( 'wp_trash_post', array( $this, 'preparing_to_trash_order' ) );
			add_action( 'before_delete_post', array( $this, 'delete_order_items' ) );
			add_action( 'save_post', array( $this, 'save_order' ) );
			parent::__construct();
		}

		function enqueue_scripts() {
			if ( get_post_type() != 'lp_order' ) {
				return;
			}
			wp_enqueue_script( 'user-suggest' );
		}

		/**
		 * Delete all records related to order being deleted
		 *
		 * @param $post_id
		 */
		function delete_order_items( $post_id ) {
			global $wpdb, $post;
			if ( get_post_type( $post_id ) != 'lp_order' ) {
				return;
			}
			// get order items
			$query = $wpdb->prepare( "
				SELECT order_item_id FROM {$wpdb->prefix}learnpress_order_items
				WHERE order_id = %d
			", $post_id );
			if ( $item_ids = $wpdb->get_col( $query ) ) {
				// delete order item meta data
				$query = "
					DELETE FROM {$wpdb->prefix}learnpress_order_itemmeta
					WHERE learnpress_order_item_id IN(" . join( ',', $item_ids ) . ")
				";
				$wpdb->query( $query );

				// delete order items
				$query = $wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_order_items
					WHERE order_id = %d
				", $post_id );
				$wpdb->query( $query );

				// delete user course related to order
				$query = $wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_user_courses
					WHERE order_id = %d
				", $post_id );
				$wpdb->query( $query );
			}
		}

		function save_order( $post_id ) {
			global $action;
			if ( wp_is_post_revision( $post_id ) )
				return;
			if ( $action == 'editpost' && get_post_type( $post_id ) == 'lp_order' ) {
				remove_action( 'save_post', array( $this, 'save_order' ) );

				$order_statuses = learn_press_get_order_statuses();
				$order_statuses = array_keys( $order_statuses );
				$status         = learn_press_get_request( 'order-status' );

				if ( !in_array( $status, $order_statuses ) ) {
					$status = reset( $order_statuses );
				}
				$order = learn_press_get_order( $post_id );
				$order->update_status( $status );

				$user_id = learn_press_get_request( 'order-customer' );
				//$postdata = array( 'post_status' => $status, 'ID' => $post_id );
				///wp_update_post( $postdata );


				update_post_meta( $post_id, '_user_id', $user_id > 0 ? $user_id : 0 );
			}
		}

		function remove_box() {
			//remove_post_type_support( LP()->order_post_type, 'title' );
			remove_post_type_support( LP()->order_post_type, 'editor' );
		}

		/**
		 * Disable the auto-save functionality for Orders.
		 */
		public function disable_autosave() {
			global $post;

			if ( $post && in_array( get_post_type( $post->ID ), array( 'lp_order' ) ) ) {
				wp_dequeue_script( 'autosave' );
			}
		}

		function admin_footer() {
			if ( !$this->_is_archive() ) {
				return;
			}
			?>
			<script type="text/javascript">
				jQuery(function ($) {
					$('#post-search-input').prop('placeholder', '<?php esc_attr_e( 'Order number, user name, user email, etc...', 'learnpress' );?>').css('width', 400)
				})
			</script>
			<?php
		}

		function posts_where_paged( $where ) {
			global $wpdb, $wp_query;
			if ( !$this->_is_archive() || !$this->_is_search() ) {
				return $where;
			}
			$s      = '%' . $wpdb->esc_like( $wp_query->get( 's' ) ) . '%';
			$append = $wpdb->prepare( " (uu.user_login LIKE %s
					OR uu.user_nicename LIKE %s
					OR uu.user_email LIKE %s
					OR uu.display_name LIKE %s
					OR {$wpdb->posts}.ID LIKE %s
				) OR ", $s, $s, $s, $s, $s );
			$where  = preg_replace( "/({$wpdb->posts}\.post_title LIKE)/", $append . '$1', $where );
			return $where;
		}

		function posts_fields( $fields ) {
			if ( !$this->_is_archive() || !$this->_is_search() ) {
				return $fields;
			}
			$fields .= ", uu.ID as user_ID, uu.display_name as user_display_name";
			return $fields;
		}

		function posts_orderby( $orderby ) {
			if ( !$this->_is_archive() || !$this->_is_search() ) {
				return $orderby;
			}
			return $orderby;
		}

		function posts_join_paged( $join ) {
			if ( !$this->_is_archive() || !$this->_is_search() ) {
				return $join;
			}
			global $wpdb;
			$join .= " INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id";
			$join .= " INNER JOIN {$wpdb->users} uu ON uu.ID = {$wpdb->postmeta}.meta_value";
			return $join;
		}

		/**
		 * Make our custom columns can be sortable
		 *
		 * @param $columns
		 *
		 * @return mixed
		 */
		function sortable_columns( $columns ) {
			$columns['order_student'] = 'student';
			return $columns;
		}

		function admin_head() {

			global $post, $wp_query;

			if ( LP()->order_post_type != get_post_type() ) return;
			ob_start();
			?>
			<script>
				$('#update-order-status').click(function () {
					var $button = $(this).attr('disabled', 'disabled').html('<?php _e( 'Processing...', 'learnpress' );?>');
					$.ajax({
						url     : ajaxurl,
						type    : 'POST',
						dataType: 'json',
						data    : {
							action  : 'update_order_status',
							order_id: '<?php echo $post->ID;?>',
							status  : $('select[name="learn_press_order_status"]').val()
						},
						success : function (res) {
							if (res.status) {
								$('.order-data-status')
									.removeClass('pending completed')
									.html(res.status)
									.addClass(res.class);
							}
							$button.removeAttr('disabled').html('<?php _e( 'Apply', 'learnpress' );?>');
						},
						error   : function () {
							$button.removeAttr('disabled').html('<?php _e( 'Apply', 'learnpress' );?>');
						}
					});
				})
			</script>
			<?php
			$js = preg_replace( '!</?script>!', '', ob_get_clean() );
			learn_press_enqueue_script( $js );
		}

		function update_status() {
			$order_id = !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0;
			$status   = !empty( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'Pending';
			learn_press_update_order_status( $order_id, $status );

			wp_send_json(
				array(
					'status' => $status,
					'class'  => sanitize_title( $status )
				)
			);
		}

		function post_row_actions( $actions, $post ) {

			if ( LP()->order_post_type == $post->post_type ) {
				if ( !empty( $actions['inline hide-if-no-js'] ) ) {
					unset( $actions['inline hide-if-no-js'] );
				}
				if ( !empty( $actions['edit'] ) ) {
					$actions['edit'] = preg_replace( '/>(.*?)<\/a>/', ">" . __( 'View Order', 'learnpress' ) . "</a>", $actions['edit'] );
				}
			}
			return $actions;
		}

		/**
		 * re-order the orders by newest
		 *
		 * @param $wp_query
		 *
		 * @return mixed
		 */
		function pre_get_posts( $wp_query ) {
			if ( is_admin() ) {
				if ( !empty( $wp_query->query['post_type'] ) && ( $wp_query->query['post_type'] == LP()->order_post_type ) ) {
					$wp_query->set( 'orderby', 'date' );
					$wp_query->set( 'order', 'desc' );
				}
			}
			return $wp_query;
		}

		/**
		 *
		 */
		function columns_head( $existing ) {

			// Remove Checkbox - adding it back below
			if ( isset( $existing['cb'] ) ) {
				$check = $existing['cb'];
				unset( $existing['cb'] );
			}

			// Remove Title - adding it back below
			/*if ( isset( $existing['title'] ) )
				unset( $existing['title'] );
			*/
			// Remove Format
			if ( isset( $existing['format'] ) )
				unset( $existing['format'] );

			// Remove Author
			if ( isset( $existing['author'] ) )
				unset( $existing['author'] );

			// Remove Comments
			if ( isset( $existing['comments'] ) )
				unset( $existing['comments'] );

			// Remove Date
			if ( isset( $existing['date'] ) )
				unset( $existing['date'] );

			// Remove Builder
			if ( isset( $existing['builder_layout'] ) )
				unset( $existing['builder_layout'] );

			add_filter( 'the_title', array( $this, 'order_title' ), 5, 2 );

			$columns['cb']            = '<input type="checkbox" />';
			$columns['title']         = __( 'Order', 'learnpress' );
			$columns['order_student'] = __( 'Student', 'learnpress' );
			$columns['order_items']   = __( 'Purchased', 'learnpress' );
			$columns['order_date']    = __( 'Date', 'learnpress' );
			$columns['order_total']   = __( 'Total', 'learnpress' );
			$columns['order_status']  = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'learnpress' ) . '">' . esc_attr__( 'Status', 'learnpress' ) . '</span>';

			$columns = array_merge( $columns, $existing );

			return $columns;
		}

		function order_title( $title, $post_id ) {
			if ( LP()->order_post_type == get_post_type( $post_id ) )
				$title = learn_press_transaction_order_number( $post_id );
			return $title;
		}

		/**
		 * Render column data
		 *
		 */
		function columns_content( $column ) {
			global $post;
			$the_order = learn_press_get_order( $post->ID );
			switch ( $column ) {
				case 'order_student':
					if ( $the_order->user_id ) {
						$user = learn_press_get_user( $the_order->user_id );
						printf( '<a href="user-edit.php?user_id=%d">%s (%s)</a>', $the_order->user_id, $user->user_login, $user->display_name ); ?><?php
						printf( '<br /><span>%s</span>', $user->user_email );
					} else {
						_e( 'Guest', 'learnpress' );
					}
					break;
				case 'order_status' :
					echo learn_press_get_order_status_label( $post->ID );
					break;
				case 'order_date' :

					$t_time = get_the_time( 'Y/m/d g:i:s a' );
					$m_time = $post->post_date;
					$time   = get_post_time( 'G', true, $post );

					$time_diff = current_time( 'timestamp' ) - $time;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
						$h_time = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time ) );
					else
						$h_time = mysql2date( 'Y/m/d', $m_time );

					echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'learn_press_order_column_time', $h_time, $the_order ) ) . '</abbr>';

					break;
				case 'order_items' :
					$links = array();
					foreach ( $the_order->get_items() as $item ) {
						$links[] = '<a href="' . get_the_permalink( $item['course_id'] ) . '">' . get_the_title( $item['course_id'] ) . '</a>';
					}
					echo join( "<br />", $links );
					break;
				case 'order_total' :
					echo learn_press_format_price( $the_order->order_total, learn_press_get_currency_symbol( $the_order->order_currency ) );
					if ( $title = $the_order->get_payment_method_title() ) { ?>
						<div class="payment-method-title">
							<?php echo $the_order->order_total == 0 ? $title : sprintf( __( 'Pay via <strong>%s</strong>', 'learnpress' ), $title ); ?>
						</div>
					<?php }
					break;
				case 'order_title' :
					$order_number = sprintf( "%'.010d", $the_order->ID );
					?>
					<div class="tips">
						<a href="post.php?post=<?php echo $the_order->ID ?>&action=edit"><strong><?php echo learn_press_transaction_order_number( $order_number ); ?></strong></a>
					</div>
					<?php break;
			}
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( 'lp_order' != $post_type ) ) {
				return false;
			}
			return true;
		}

		private function _is_search() {
			return is_search();
		}

		/**
		 * Register post type
		 */
		static function register_post_type() {

			register_post_type( LP_ORDER_CPT,
				array(
					'labels'             => array(
						'name'               => __( 'Orders', 'learnpress' ),
						'menu_name'          => __( 'Orders', 'learnpress' ),
						'singular_name'      => __( 'Order', 'learnpress' ),
						'add_new_item'       => __( 'Add New Order', 'learnpress' ),
						'edit_item'          => __( 'Order Details', 'learnpress' ),
						'all_items'          => __( 'Orders', 'learnpress' ),
						'view_item'          => __( 'View Order', 'learnpress' ),
						'add_new'            => __( 'Add New', 'learnpress' ),
						'update_item'        => __( 'Update Order', 'learnpress' ),
						'search_items'       => __( 'Search Orders', 'learnpress' ),
						'not_found'          => __( 'No order found', 'learnpress' ),
						'not_found_in_trash' => __( 'No order found in Trash', 'learnpress' )
					),
					'public'             => false,
					'show_ui'            => true,
					'show_in_nav_menus'  => false,
					'show_in_admin_bar'  => false,
					'publicly_queryable' => false,
					'show_in_menu'       => 'learn_press',
					/*'capabilities'       => array(
						'create_posts' => 'do_not_allow'
					),*/
					'map_meta_cap'       => true,
					'capability_type'    => LP()->order_post_type,
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => LP()->order_post_type, 'hierarchical' => true, 'with_front' => true ),
					'supports'           => array(
						'title',
						'comments',
						'custom-fields'
					)
				)
			);

			add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		}

		static function register_metabox() {

			// Remove Publish metabox
			remove_meta_box( 'submitdiv', LP()->order_post_type, 'side' );

			remove_meta_box( 'commentstatusdiv', LP()->order_post_type, 'normal' );

			add_meta_box( 'order_details', __( 'Order Details', 'learnpress' ), array( __CLASS__, 'order_details' ), LP()->order_post_type, 'normal', 'high' );
			add_meta_box( 'submitdiv', __( 'Order Actions', 'learnpress' ), array( __CLASS__, 'order_actions' ), LP()->order_post_type, 'side', 'high' );
		}

		static function order_details( $post ) {
			learn_press_admin_view( 'meta-boxes/order/details.php', array( 'order' => LP_Order::instance( $post ) ) );
		}

		static function order_actions( $post ) {
			learn_press_admin_view( 'meta-boxes/order/actions.php', array( 'order' => LP_Order::instance( $post ) ) );
		}

		function preparing_to_trash_order( $post_id ) {
			if ( LP()->order_post_type != get_post_type( $post_id ) ) return;

		}

		/**
		 * Enqueue scripts
		 *
		 * @static
		 */
		static function admin_scripts() {
			/*if ( in_array( get_post_type(), array( LP()->order_post_type ) ) ) {

				wp_enqueue_style( 'lp-meta-boxes', LP()->plugin_url( 'assets/css/meta-boxes.css' ) );

			}*/
		}

		function remove_edit_post_link() {
			return '';
		}

		/**
		 * Register new post status for order
		 */
		function register_post_statues() {
			register_post_status( 'lp-pending', array(
				'label'                     => _x( 'Pending Payment', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'learnpress' )
			) );
			register_post_status( 'lp-processing', array(
				'label'                     => _x( 'Processing', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'learnpress' )
			) );
			register_post_status( 'lp-on-hold', array(
				'label'                     => _x( 'On Hold', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'On Hold <span class="count">(%s)</span>', 'On Hold <span class="count">(%s)</span>', 'learnpress' )
			) );
			register_post_status( 'lp-completed', array(
				'label'                     => _x( 'Completed', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'learnpress' )
			) );
			register_post_status( 'lp-cancelled', array(
				'label'                     => _x( 'Cancelled', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'learnpress' )
			) );
			register_post_status( 'lp-refunded', array(
				'label'                     => _x( 'Refunded', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'learnpress' )
			) );
			register_post_status( 'lp-failed', array(
				'label'                     => _x( 'Failed', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'learnpress' )
			) );
		}
	}
} // end LP_Order_Post_Type
new LP_Order_Post_Type();