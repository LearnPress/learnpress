<?php
/**
 * Send emails in background
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		protected $action = 'lp_mailer';

		/**
		 * @var int
		 */
		protected $queue_lock_time = 3600;

		/**
		 * LP_Background_Query_Items constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @param mixed $data
		 *
		 * @return bool
		 */
		protected function task( $data ) {

			if ( ! isset( $data['callback'] ) ) {
				return false;
			}

			if ( ! is_callable( $data['callback'] ) ) {
				return false;
			}

			call_user_func( $data['callback'] );

			return false;
		}

		/**
		 * Query all free addons from wp.org
		 *
		 * @return array|string
		 */
		public static function query_free_addons() {
			LP_Plugins_Helper::require_plugins_api();
			// the number of plugins on each page queried,
			// when we can reach to this figure?
			$per_page = 20;
			$paged    = 1;
			$tag      = 'learnpress';

			$query_args = array(
				'page'              => $paged,
				'per_page'          => $per_page,
				'fields'            => array(
					'last_updated'    => true,
					'icons'           => true,
					'active_installs' => true
				),
				'locale'            => get_locale(),
				'installed_plugins' => LP_Plugins_Helper::get_installed_plugin_slugs(),
				'author'            => 'thimpress'
			);
			$plugins    = array();
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
					$key    = $plugin->slug;
					foreach ( $all_plugins as $file => $p ) {
						if ( strpos( $file, $plugin->slug ) !== false ) {
							$key = $file;
							break;
						}
					}
					$plugin->source  = 'wp';
					$plugins[ $key ] = (array) $plugin;
				}

				// Cache in a half of day
				set_transient( 'lp_plugins_wp', $plugins, DAY_IN_SECONDS / 2 );
			}
			catch ( Exception $ex ) {
			}

			return $plugins;
		}

		/**
		 * Query all available premium addons from thimpress.com
		 *
		 * @return array|bool
		 */
		public static function query_premium_addons() {
			$plugins  = array();
			$url      = 'https://thimpress.com/?thimpress_get_addons=premium';
			$response = wp_remote_get( esc_url_raw( $url ), array( 'decompress' => false ) );

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
					'certificates-add-on-for-learnpress'   => 'learnpress-certificates'
				);

				foreach ( $response as $key => $item ) {
					$slug = $item['slug'];
					if ( ! empty( $maps[ $slug ] ) ) {
						$plugin_file             = sprintf( '%1$s/%1$s.php', $maps[ $slug ] );
						$plugins[ $plugin_file ] = $item;
					}
				}

				// Cache in a half of day
				set_transient( 'lp_plugins_tp', $plugins, DAY_IN_SECONDS / 2 );

			}

			return $plugins;
		}

		/**
		 * @return array|bool
		 */
		public static function get_related_themes() {
			$themes   = array();
			$url      = 'https://api.envato.com/v1/discovery/search/search/item?site=themeforest.net&username=thimpress';
			$args     = array(
				'headers' => array(
					"Authorization" => "Bearer BmYcBsYXlSoVe0FekueDxqNGz2o3JRaP"
				)
			);
			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response, true );

			if ( ! empty( $response ) && ! empty( $response['matches'] ) ) {
				$all_themes = array();
				foreach ( $response['matches'] as $theme ) {
					$all_themes[ $theme['id'] ] = $theme;
				}

				if ( $education_themes = learn_press_get_education_themes() ) {
					$themes['other']     = array_diff_key( $all_themes, $education_themes );
					$themes['education'] = array_diff_key( $all_themes, $themes['other'] );
				} else {
					$themes['other'] = $all_themes;
				}
				set_transient( 'lp_related_themes', $themes, DAY_IN_SECONDS / 2 );
			}

			return $themes;
		}
	}
}