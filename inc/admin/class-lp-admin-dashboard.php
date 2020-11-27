<?php
if ( ! class_exists( 'LP_Admin_Dashboard' ) ) {
	/**
	 * Class LP_Admin_Dashboard
	 *
	 * Displays widgets in admin.
	 */
	class LP_Admin_Dashboard {

		/**
		 * LP_Admin_Dashboard constructor.
		 */
		public function __construct() {
			add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
		}

		public function register() {
			if ( current_user_can( 'manage_options' ) || current_user_can( 'publish_lp_orders' ) ) {
				wp_add_dashboard_widget(
					'learn_press_dashboard_order_statuses',
					esc_html__( 'LearnPress order status', 'learnpress' ),
					array(
						$this,
						'order_statuses',
					)
				);
			}

			wp_add_dashboard_widget(
				'learn_press_dashboard_plugin_status',
				esc_html__( 'LearnPress status', 'learnpress' ),
				array(
					$this,
					'plugin_status',
				)
			);
		}

		/**
		 * Order status widget
		 */
		public function order_statuses() {
			?>

			<ul class="lp-order-statuses">
				<li class="count-number total-raised">
					<strong><?php echo $this->_get_order_total_raised(); ?></strong>
					<p><?php esc_html_e( 'Total Raised', 'learnpress' ); ?></p>
				</li>

				<?php
				$order_statuses    = learn_press_get_order_statuses( true, true );
				$specific_statuses = array( 'lp-completed', 'lp-failed' );

				foreach ( $order_statuses as $status ) {
					if ( ! in_array( $status, $specific_statuses ) ) {
						$specific_statuses[] = $status;
					}
				}

				$counts = learn_press_count_orders( array( 'status' => $specific_statuses ) );

				foreach ( $specific_statuses as $status ) :

					$status_object = get_post_status_object( $status );

					if ( ! $status_object ) {
						continue;
					}

					$count = $counts[ $status ];
					$url   = $count ? admin_url( 'edit.php?post_type=' . LP_ORDER_CPT . '&post_status=' . $status ) : '#';
					?>

					<li class="counter-number order-<?php echo str_replace( 'lp-', '', $status ); ?>">
						<div class="counter-inner">
							<a href="<?php echo esc_url( $url ); ?>">
								<strong>
									<?php
									if ( $count ) {
										printf( translate_nooped_plural( _n_noop( '%d order', '%d orders' ), $count, 'learnpress' ), $count );
									} else {
										printf( __( '%d order', 'learnpress' ), 0 );
									}
									?>
								</strong>
								<p><?php printf( '%s', $status_object->label ); ?></p>
							</a>
						</div>
					</li>
				<?php endforeach; ?>

				<?php
				$eduma_data = $this->_get_theme_info( 14058034 );

				if ( function_exists( 'learn_press_get_item_referral' ) ) {
					$eduma_data['url'] = learn_press_get_item_referral( 14058034 );
				}

				if ( ! empty( $eduma_data ) ) {
					?>

					<li class="clear"></li>
					<li class="featured-theme">
						<?php if ( isset( $eduma_data['name'] ) && isset( $eduma_data['price_cents'] ) ) : ?>
							<p>
								<a href="<?php echo esc_url( $eduma_data['url'] ); ?>">
									<?php echo esc_html( $eduma_data['name'] ); ?>
								</a> - <?php printf( '%s%s', '$', $eduma_data['price_cents'] / 100 ); ?>
							</p>
						<?php endif; ?>

						<?php if ( isset( $eduma_data['rating']['count'] ) && isset( $eduma_data['rating']['rating'] ) ) : ?>
							<div>
								<?php
								wp_star_rating(
									array(
										'rating' => $eduma_data['rating']['rating'],
										'type'   => 'rating',
										'number' => $eduma_data['rating']['count'],
									)
								);
								?>

								<span class="count-rating">(<?php echo $eduma_data['rating']['count']; ?>)</span>
								<span>
									- <?php echo $eduma_data['number_of_sales'] . esc_html__( ' sales', 'learnpress' ); ?>
								</span>
							</div>
						<?php endif; ?>

						<?php if ( isset( $eduma_data['author_username'] ) ) : ?>
							<p>
								<?php esc_html_e( 'Created by: ', 'learnpress' ); ?>
								<a href="https://thimpress.com/" class="author"><?php echo esc_html( $eduma_data['author_username'] ); ?></a>
							</p>
						<?php endif; ?>
					</li>
				<?php } ?>
			</ul>
			<?php
		}

		/**
		 * Get total value of LP orders has completed.
		 *
		 * @return int|string
		 */
		private function _get_order_total_raised() {
			$orders = learn_press_get_orders( array( 'post_status' => 'lp-completed' ) );
			$total  = 0;

			if ( $orders ) {
				foreach ( $orders as $order ) {
					$order = learn_press_get_order( $order->ID );
					$total = $total + floatval( $order->order_total );
				}
			}

			return learn_press_format_price( $total, true );
		}

		/**
		 * @param String $item_id - The ID of an Envato Marketplace item
		 *
		 * @returns mixed
		 */
		private function _get_theme_info( $item_id ) {
			$alls = LP_Plugins_Helper::get_related_themes();

			if ( ! $alls ) {
				return false;
			}

			foreach ( $alls as $k => $types ) {
				if ( isset( $types[ $item_id ] ) ) {
					return $types[ $item_id ];
				}
			}

			return false;
		}

		/**
		 * Get data from wordpress.org
		 *
		 * @since 2.0
		 */
		private function _get_data() {

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$api = get_transient( 'lp_plugin_status' );

			if ( false === $api ) {
				$api = plugins_api(
					'plugin_information',
					array(
						'slug'   => 'learnpress',
						'fields' => array(
							'active_installs'   => true,
							'short_description' => true,
							'description'       => true,
							'ratings'           => true,
							'downloaded'        => true,
						),
					)
				);

				set_transient( 'lp_plugin_status', $api, 12 * HOUR_IN_SECONDS );
			}

			return $api;
		}

		/**
		 * Plugin status widget
		 */
		public function plugin_status() {
			$plugin_data = $this->_get_data();

			if ( ! $plugin_data || is_wp_error( $plugin_data ) ) {
				learn_press_admin_view( 'dashboard/plugin-status/html-no-data' );
			} else {
				learn_press_admin_view( 'dashboard/plugin-status/html-results', array( 'plugin_data' => $plugin_data ) );
			}
		}
	}
}
new LP_Admin_Dashboard();
