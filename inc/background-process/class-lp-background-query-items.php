<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Query_Items' ) ) {
	/**
	 * Class LP_Background_Query_Items
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Query_Items extends LP_Abstract_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'query_items';

		/**
		 * @var int
		 */
		protected $queue_lock_time = 3600;

		protected $cron_interval = 60 * 24; // minutes

		/**
		 * @var float|int
		 */
		protected $transient_time = 0;

		/**
		 * LP_Background_Query_Items constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->transient_time = DAY_IN_SECONDS / 2;
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {
			parent::task( $data );

			if ( ! isset( $data['callback'] ) ) {
				return false;
			}

			if ( ! is_callable( array( $this, $data['callback'] ) ) ) {
				return false;
			}

			call_user_func( array( $this, $data['callback'] ) );

			delete_option( 'doing_' . $data['callback'] );

			return false;
		}

		public function get_plugins_from_wp() {
			$method  = 'query_free_addons';
			$plugins = get_transient( 'lp_plugins_wp' );

			if ( ! $plugins && ( 'yes' !== get_option( 'doing_' . $method ) ) ) {
				update_option( 'doing_' . $method, 'yes', 'no' );
				$this->clear_queue()->push_to_queue(
					array(
						'callback' => $method,
					)
				)->save()->dispatch();
			}

			return is_array( $plugins ) ? $plugins : false;
		}

		public function get_plugins_from_tp() {
			$method  = 'query_premium_addons';
			$plugins = get_transient( 'lp_plugins_tp' );

			if ( ! $plugins && ( 'yes' !== get_option( 'doing_' . $method ) ) ) {
				update_option( 'doing_' . $method, 'yes', 'no' );
				$this->clear_queue()->push_to_queue(
					array(
						'callback' => $method,
					)
				)->save()->dispatch();
			}

			return is_array( $plugins ) ? $plugins : false;
		}

		public function get_related_themes() {
			$method = 'query_related_themes';
			$themes = get_transient( 'lp_related_themes' );

			if ( ! $themes && ( 'yes' !== get_option( 'doing_' . $method ) ) ) {
				update_option( 'doing_' . $method, 'yes', 'no' );
				$this->clear_queue()->push_to_queue(
					array(
						'callback' => $method,
					)
				)->save()->dispatch();
			}

			return is_array( $themes ) ? $themes : false;
		}

		public function get_last_checked( $type ) {
			$next = get_option( '_transient_timeout_' . $this->prefix . '_' . $type );

			if ( $next ) {
				return $next - $this->transient_time;
			}

			return 0;
		}

		public function force_update() {
			$transients = array( 'lp_plugins_wp', 'lp_plugins_tp', 'lp_related_themes' );

			foreach ( $transients as $transient ) {
				delete_transient( $transient );
			}

			$this->query_free_addons();
			$this->query_premium_addons();
			$this->query_related_themes();
		}

		/**
		 * Query all free addons from wp.org
		 *
		 * @return array|string
		 */
		public function query_free_addons() {
			LP_Plugins_Helper::require_plugins_api();

			$per_page = 20;
			$paged    = 1;
			$tag      = 'learnpress';

			$query_args = array(
				'page'              => $paged,
				'per_page'          => $per_page,
				'fields'            => array(
					'last_updated'    => true,
					'icons'           => true,
					'active_installs' => true,
				),
				'locale'            => get_locale(),
				'installed_plugins' => LP_Plugins_Helper::get_installed_plugin_slugs(),
				'author'            => 'thimpress',
			);
			$plugins    = array();

			set_transient( 'lp_plugins_wp', __( 'There is no items found!', 'learnpress' ), $this->transient_time );

			try {
				$api = plugins_api( 'query_plugins', $query_args );
				if ( is_wp_error( $api ) ) {
					throw new Exception( __( 'WP query plugins error!', 'learnpress' ) );
				}
				if ( ! is_array( $api->plugins ) ) {
					throw new Exception( __( 'WP query plugins empty!', 'learnpress' ) );
				}
				$all_plugins = get_plugins();
				// Filter plugins with tag contains 'learnpress'
				$_plugins = array_filter( $api->plugins, array( 'LP_Plugins_Helper', '_filter_plugin' ) );

				// Ensure that the array is indexed from 0
				$_plugins = array_values( $_plugins );

				for ( $n = sizeof( $_plugins ), $i = $n - 1; $i >= 0; $i -- ) {
					$plugin = $_plugins[ $i ];

					if ( ! isset( $plugin->slug ) ) {
						return;
					}

					$key = $plugin->slug;
					foreach ( $all_plugins as $file => $p ) {
						if ( strpos( $file, $plugin->slug ) !== false ) {
							$key = $file;
							break;
						}
					}
					$plugin->source  = 'wp';
					$plugins[ $key ] = (array) $plugin;
				}

				if ( sizeof( $plugins ) ) {
					set_transient( 'lp_plugins_wp', $plugins, $this->transient_time );
				}
			} catch ( Exception $ex ) {
			}

			return $plugins;
		}

		/**
		 * Query all available premium addons from thimpress.com
		 *
		 * @return array|bool
		 */
		public function query_premium_addons() {
			$plugins  = array();
			$url      = 'https://thimpress.com/?thimpress_get_addons=premium';
			$response = wp_remote_get( esc_url_raw( $url ), array( 'decompress' => false ) );

			set_transient( 'lp_plugins_tp', __( 'There is no items found!', 'learnpress' ), $this->transient_time );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response, true );

			if ( ! empty( $response ) ) {
				$maps = array(
					'authorize-net-add-on-learnpress'      => 'learnpress-authorizenet-payment',
					'2checkout-add-learnpress'             => 'learnpress-2checkout-payment',
					'commission-add-on-for-learnpress'     => 'learnpress-commission',
					'paid-memberships-pro-add-learnpress'  => 'learnpress-paid-membership-pro',
					'gradebook-add-on-for-learnpress'      => 'learnpress-gradebook',
					'sorting-choice-add-on-for-learnpress' => 'learnpress-sorting-choice',
					'content-drip-add-on-for-learnpress'   => 'learnpress-content-drip',
					'mycred-add-on-for-learnpress'         => 'learnpress-mycred',
					'random-quiz-add-on-for-learnpress'    => 'learnpress-random-quiz',
					'co-instructors-add-on-for-learnpress' => 'learnpress-co-instructor',
					'collections-add-on-for-learnpress'    => 'learnpress-collections',
					'woocommerce-add-on-for-learnpress'    => 'learnpress-woo-payment',
					'stripe-add-on-for-learnpress'         => 'learnpress-stripe',
					'certificates-add-on-for-learnpress'   => 'learnpress-certificates',
					'assignments-add-on-for-learnpress'    => 'learnpress-assignments',
					'announcement-add-on-for-learnpress'   => 'learnpress-announcements',
				);

				foreach ( $response as $key => $item ) {
					$slug = $item['slug'];
					if ( ! empty( $maps[ $slug ] ) ) {
						$plugin_file             = sprintf( '%1$s/%1$s.php', $maps[ $slug ] );
						$plugins[ $plugin_file ] = $item;
					}
				}

				if ( sizeof( $plugins ) ) {
					set_transient( 'lp_plugins_tp', $plugins, $this->transient_time );
				}
			}

			return $plugins;
		}

		/**
		 * @return array|bool
		 */
		public function query_related_themes() {

			set_transient( 'lp_related_themes', __( 'There is no item found!', 'learnpress' ), $this->transient_time );

			$themes   = array();
			$url      = 'https://api.envato.com/v1/discovery/search/search/item?site=themeforest.net&username=thimpress';
			$args     = array(
				'headers' => array(
					'Authorization' => 'Bearer BmYcBsYXlSoVe0FekueDxqNGz2o3JRaP',
				),
			);
			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				error_log( $response->get_error_message() );
				return false;
			}

			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response, true );

			if ( ! empty( $response ) && ! empty( $response['matches'] ) ) {
				$all_themes = array();

				foreach ( $response['matches'] as $theme ) {
					$all_themes[ $theme['id'] ] = $theme;
				}

				$education_themes = apply_filters(
					'learn-press/education-themes',
					array(
						'23451388' => 'kindergarten',
						'22773871' => 'ivy-school',
						'20370918' => 'wordpress-lms',
						'14058034' => 'eduma',
						'17097658' => 'coach',
						'11797847' => 'lms',
					)
				);

				if ( $education_themes ) {
					$themes['other']     = array_diff_key( $all_themes, $education_themes );
					$themes['education'] = array_diff_key( $all_themes, $themes['other'] );
				} else {
					$themes['other'] = $all_themes;
				}

				set_transient( 'lp_related_themes', $themes, $this->transient_time );

			} elseif ( ! empty( $response['message'] ) ) {
				set_transient( 'lp_related_themes', $response['message'], $this->transient_time );
			}

			return $themes;
		}

		/**
		 * @return LP_Background_Query_Items
		 */
		public static function instance() {
			return parent::instance();
		}
	}
}

return LP_Background_Query_Items::instance();
