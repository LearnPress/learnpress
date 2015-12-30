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

			/*add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );

			add_action( 'wp_ajax_update_order_status', array( $this, 'update_status' ) );
			add_action( 'admin_head', array( $this, 'admin_head' ) );

			add_action( 'wp_trash_post', array( $this, 'preparing_to_trash_order' ) );

			add_action( 'before_delete_post', array( $this, 'delete_transaction' ) );*/
			parent::__construct();
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

		function delete_transaction( $post_id ) {
			$order_data = get_post_meta( $post_id, '_learn_press_order_items', true );

			// find all courses stored in current order and remove it from user's courses
			if ( $order_data && !empty( $order_data->products ) ) {
				$products = $order_data->products;

				// loop through the list and find the course need to remove
				if ( is_array( $products ) ) foreach ( $products as $course_id => $data ) {
					$user_order = get_post_meta( $post_id, '_learn_press_customer_id', true );

					// all courses user has enrolled
					$user_courses = get_user_meta( $user_order, '_lpr_user_course', true );

					// find the position of the course in the array and remove it if find out
					if ( $user_courses && false !== ( $pos = array_search( $course_id, $user_courses ) ) ) {
						unset( $user_courses[$pos] );

						// update the meta if we have the courses in the list else delete
						if ( sizeof( $user_courses ) ) {
							update_user_meta( $user_order, '_lpr_user_course', $user_courses );
						} else {
							delete_user_meta( $user_order, '_lpr_user_course' );
							break;
						}
					}
				}
			}
		}

		function posts_where_paged( $where ) {
			global $wpdb;
			global $post_type;
			if ( LP()->order_post_type != $post_type ) return $where;
			$where .= " AND (
                {$wpdb->postmeta}.meta_key='_learn_press_customer_id'
            )";
			return $where;
		}

		function posts_fields( $fields ) {
			global $post_type;
			if ( LP()->order_post_type != $post_type ) return $fields;
			$fields .= ", uu.ID as user_ID, uu.display_name as user_display_name";
			return $fields;
		}

		function posts_orderby( $orderby ) {
			global $post_type;
			if ( LP()->order_post_type != $post_type ) return $orderby;
			$args = wp_parse_args(
				$_REQUEST,
				array(
					'orderby' => '',
					'order'   => ''
				)
			);
			if ( $args['orderby'] == 'student' ) {
				$orderby = "user_display_name ";
				if ( $args['order'] ) $orderby .= $args['order'];
			}
			return $orderby;
		}

		function posts_join_paged( $join ) {
			global $post_type;
			if ( LP()->order_post_type != $post_type ) return $join;
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
					var $button = $(this).attr('disabled', 'disabled').html('<?php _e( 'Processing...', 'learn_press' );?>');
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
							$button.removeAttr('disabled').html('<?php _e( 'Apply', 'learn_press' );?>');
						},
						error   : function () {
							$button.removeAttr('disabled').html('<?php _e( 'Apply', 'learn_press' );?>');
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

				$_actions = array();

				if ( !empty( $actions['edit'] ) ) {
					$_actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'View the transaction details', 'learn_press' ) ) . '">' . __( 'Details', 'learn_press' ) . '</a>';
				}

				if ( !empty( $actions['trash'] ) ) $_actions['trash'] = $actions['trash'];
				$actions = $_actions;
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
			if ( isset( $existing['title'] ) )
				unset( $existing['title'] );

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
			$columns['title']         = __( 'Order', 'learn_press' );
			$columns['order_student'] = __( 'Student', 'learn_press' );
			$columns['order_items']   = __( 'Courses', 'learn_press' );
			$columns['order_date']    = __( 'Date', 'learn_press' );
			$columns['order_total']   = __( 'Total', 'learn_press' );
			$columns['order_status']  = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'learn_press' ) . '">' . esc_attr__( 'Status', 'learn_press' ) . '</span>';

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
			//print_r($the_order->get_items());die();
			switch ( $column ) {
				case 'order_student':
					$user = learn_press_get_user( $the_order->user_id );
					printf( '<a href="user-edit.php?user_id=%d">%s (%s)</a>', $the_order->user_id, $user->user_login, $user->display_name ); ?><?php
					printf( '<br /><span>%s</span>', $user->user_email );
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
						$h_time = sprintf( __( '%s ago', 'learn_press' ), human_time_diff( $time ) );
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

		/**
		 * Register post type
		 */
		static function register_post_type() {

			register_post_type( LP_ORDER_CPT,
				array(
					'labels'             => array(
						'name'          => __( 'Orders', 'learn_press' ),
						'menu_name'     => __( 'Orders', 'learn_press' ),
						'singular_name' => __( 'Order', 'learn_press' ),
						'add_new_item'  => __( 'Add New Order', 'learn_press' ),
						'edit_item'     => __( 'Order Details', 'learn_press' ),
						'all_items'     => __( 'Orders', 'learn_press' ),
					),
					'public'             => false,
					'show_ui'            => true,
					'show_in_nav_menus'  => false,
					'show_in_admin_bar'  => false,
					'publicly_queryable' => true,
					'show_in_menu'       => 'learn_press',
					/*'capabilities'       => array(
						'create_posts' => 'do_not_allow'
					),*/
					'map_meta_cap'       => true,
					'capability_type'    => LP()->order_post_type,
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => LP()->order_post_type, 'hierarchical' => true, 'with_front' => true )
				)
			);

			add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );

			register_post_status( 'lpr-draft', array(
				'label'                     => _x( 'Draft Order', 'Order status', 'learn_press' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Draft order <span class="count">(%s)</span>', 'Draft order <span class="count">(%s)</span>', 'learn_press' )
			) );

		}

		static function register_metabox() {

			// Remove Publish metabox
			//remove_meta_box( 'submitdiv', LP()->order_post_type, 'side' );

			// Remove Slug metabox
			//remove_meta_box( 'slugdiv', LP()->order_post_type, 'normal' );

			// Remove screen options tab
			//add_filter('screen_options_show_screen', '__return_false');

			add_meta_box( 'order_details', __( 'Order Details', 'learn_press' ), array( __CLASS__, 'order_details' ), LP()->order_post_type, 'normal', 'core' );
		}

		static function order_details( $post ) {
			learn_press_admin_view( 'meta-boxes/order/details.php', array( 'order' => LP_Order::instance( $post ) ) );
			LP_Assets::enqueue_script( 'learn-press-order', LP()->plugin_url( 'assets/js/admin/meta-box-order.js' ), array( 'backbone', 'wp-util' ) );
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

		/**
		 * Register new post status for order
		 */
		function register_post_statues() {
			register_post_status( 'lp-pending', array(
				'label'                     => _x( 'Pending Payment', 'Order status', 'learn_press' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'learn_press' )
			) );
			register_post_status( 'lp-processing', array(
				'label'                     => _x( 'Processing', 'Order status', 'learn_press' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'learn_press' )
			) );
			register_post_status( 'lp-completed', array(
				'label'                     => _x( 'Completed', 'Order status', 'learn_press' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'learn_press' )
			) );
		}
	}
} // end LP_Order_Post_Type
new LP_Order_Post_Type();