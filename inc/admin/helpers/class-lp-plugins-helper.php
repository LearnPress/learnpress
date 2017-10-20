<?php

class LP_Plugins_Helper {
	/**
	 * @var int
	 */
	public static $transient_timeout = HOUR_IN_SECONDS;

	/**
	 * @var array
	 */
	public static $plugins = array(
		'installed' => false,
		'free'      => false,
		'premium'   => false
	);

	/**
	 * @var array
	 */
	public static $themes = array(
		'education' => false,
		'other'     => false
	);

	public static function require_plugins_api() {
		if ( ! function_exists( 'plugins_api' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		}
	}

	/**
	 * Get all add-ons for LearnPress has installed.
	 * Identify a plugin is an add-on if it is already existing a tag 'learnpress' inside
	 *
	 * @param string $type
	 *
	 * @return mixed
	 */
	public static function get_plugins( $type = '' ) {
		self::require_plugins_api();
		$plugins = array();

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		/*
		 * Delete cache so hook for extra plugin headers works
		 */
		$all_plugins = get_plugins();

		// If there is no plugin on our site.
		if ( ! $all_plugins ) {
			return array_key_exists( $type, self::$plugins ) ? self::$plugins[ $type ] : self::$plugins;
		}
		$wp_plugins        = self::get_plugins_from_wp();
		$premium_plugins   = self::get_premium_plugins();
		$wp_installed      = array();
		$premium_installed = array();

		//learn_press_debug( wp_list_pluck( $wp_plugins, 'name' ) );
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {

			// If there is a tag
			if ( empty( $plugin_data['Tags'] ) ) {
				continue;
			}

			// If there is a tag named 'learnpress'
			$tags = ( preg_split( '/\s*,\s*/', $plugin_data['Tags'] ) );
			if ( ! in_array( 'learnpress', $tags ) ) {
				continue;
			}

			$plugin_slug = dirname( $plugin_file );

			if ( isset( $wp_plugins[ $plugin_file ] ) ) {
				$plugins[ $plugin_file ]           = (array) $wp_plugins[ $plugin_file ];
				$plugins[ $plugin_file ]['source'] = 'wp';

				$wp_installed[ $plugin_file ] = true;
			} else if ( isset( $premium_plugins[ $plugin_file ] ) ) {
				$plugins[ $plugin_file ]           = (array) $premium_plugins[ $plugin_file ];
				$plugins[ $plugin_file ]['source'] = 'tp';
				$premium_installed[ $plugin_file ] = true;
			} else {
				$plugin_data             = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
				$plugins[ $plugin_file ] = array(
					'name'              => $plugin_data['Name'],
					'slug'              => $plugin_slug,
					'version'           => $plugin_data['Version'],
					'author'            => sprintf( '<a href="%s">%s</a>', $plugin_data['AuthorURI'], $plugin_data['Author'] ),
					'author_profile'    => '',
					'contributors'      => array(),
					'homepage'          => $plugin_data['PluginURI'],
					'short_description' => $plugin_data['Description'],
					'icons'             => self::get_add_on_icons( $plugin_data, $plugin_file )
				);
				if ( ! empty( $plugin_data['Requires at least'] ) ) {
					$plugins[ $plugin_file ]['requires'] = $plugin_data['Requires at least'];
				}
				if ( ! empty( $plugin_data['Tested up to'] ) ) {
					$plugins[ $plugin_file ]['tested'] = $plugin_data['Tested up to'];
				}
				if ( ! empty( $plugin_data['Last updated'] ) ) {
					$plugins[ $plugin_file ]['last_updated'] = $plugin_data['Last updated'];
				}
			}
		}
		self::$plugins['installed'] = $plugins;
		if ( is_array( $wp_plugins ) ) {
			self::$plugins['free'] = array_diff_key( $wp_plugins, (array) $wp_installed );
		}
		self::$plugins['premium'] = array_diff_key( (array) $premium_plugins, (array) $premium_installed );

		// Sort plugins
		self::_sort_plugins();

		return array_key_exists( $type, self::$plugins ) ? self::$plugins[ $type ] : self::$plugins;
	}

	/**
	 * Query the list of add-ons from wordpress.org with keyword 'learnpress'
	 * This requires have a keyword named 'learnpress' in plugin header Tags
	 *
	 * @param array
	 *
	 * @return mixed
	 */
	public static function get_plugins_from_wp( $args = null ) {
		// the number of plugins on each page queried,
		// when we can reach to this figure?
		$per_page = 20;
		$paged    = 1;
		$tag      = 'learnpress';

		$query_args    = array(
			'page'              => $paged,
			'per_page'          => $per_page,
			'fields'            => array(
				'last_updated'    => true,
				'icons'           => true,
				'active_installs' => true
			),
			'locale'            => get_locale(),
			'installed_plugins' => self::get_installed_plugin_slugs(),
			'author'            => 'thimpress'
		);
		$transient_key = "lp_plugins_wp";
		if ( ! ( $plugins = get_transient( $transient_key ) ) ) {
			self::require_plugins_api();

			$plugins = array();
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
				$_plugins = array_filter( $api->plugins, array( __CLASS__, '_filter_plugin' ) );

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
			}
			catch ( Exception $ex ) {
				$plugins = $ex->getMessage();
				learn_press_add_message( $plugins );
			}
			set_transient( $transient_key, $plugins, self::$transient_timeout );
		}

		return $plugins;
	}

	public static function get_premium_plugins() {
		$transient_key = "lp_plugins_tp";
		if ( ! ( $plugins = get_transient( $transient_key ) ) ) {
			$plugins  = array();
			$url      = 'https://thimpress.com/?thimpress_get_addons=premium';
			$response = wp_remote_get( esc_url_raw( $url ), array( 'decompress' => false ) );

			if ( ! is_wp_error( $response ) ) {

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

					set_transient( $transient_key, $plugins, self::$transient_timeout );

				}
			}
		}

		return $plugins;
	}

	/**
	 * Get our related themes.
	 *
	 * @param string $type
	 * @param array  $args
	 *
	 * @return array|mixed
	 */
	public static function get_related_themes( $type = '', $args = array() ) {

		self::$themes = get_transient( 'lp_related_themes' );

		if ( isset( $_GET['check'] ) && wp_verify_nonce( $_GET['check'], 'lp_check_related_themes' ) || ! self::$themes ) {

			self::$themes = array();
			$url          = 'https://api.envato.com/v1/discovery/search/search/item?site=themeforest.net&username=thimpress';
			$args         = array(
				'headers' => array(
					"Authorization" => "Bearer BmYcBsYXlSoVe0FekueDxqNGz2o3JRaP"
				)
			);
			$response     = wp_remote_request( $url, $args );

			if ( ! is_wp_error( $response ) ) {
				$response = wp_remote_retrieve_body( $response );
				$response = json_decode( $response, true );
				if ( ! empty( $response ) && ! empty( $response['matches'] ) ) {
					$themes = array();
					foreach ( $response['matches'] as $theme ) {
						$themes[ $theme['id'] ] = $theme;
					}
					if ( $education_themes = learn_press_get_education_themes() ) {
						self::$themes['other']     = array_diff_key( $themes, $education_themes );
						self::$themes['education'] = array_diff_key( $themes, self::$themes['other'] );
					} else {
						self::$themes['other'] = $themes;
					}
					delete_transient( 'lp_related_themes' );
					set_transient( 'lp_related_themes', self::$themes, self::$transient_timeout );
				}
			}
		}

		if ( $type && array_key_exists( $type, self::$themes ) ) {
			$themes = self::$themes[ $type ];
			$args   = wp_parse_args( $args, array( 'include' => '' ) );
			if ( $themes && $args['include'] ) {
				$search_results = array();
				foreach ( $themes as $theme ) {
					if ( in_array( $theme['id'], $args['include'] ) ) {
						$search_results[] = $theme;
					}
				}
				$themes = $search_results;
			}
		} else {
			$themes = self::$themes;
		}

		return $themes;
	}


	/**
	 * Count themes.
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public static function count_themes( $type = '' ) {
		$themes = self::get_related_themes();
		$count  = 0;

		if ( array_key_exists( $type, $themes ) ) {
			$count = ! empty( $themes[ $type ] ) ? sizeof( $themes[ $type ] ) : 0;
		} else {
			foreach ( $themes as $k => $v ) {
				$count += ! empty( $v ) ? sizeof( $v ) : 0;
			}
		}

		return $count;
	}

	/**
	 * Get action links of a plugin.
	 *
	 * @param object $plugin
	 * @param string $file
	 *
	 * @return array
	 */
	public static function get_add_on_action_link( $plugin, $file ) {
		$action_links = array();
		if ( ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) ) {
			$name = '';
			// action links for publish add-ons
			if ( ! empty( $plugin['source'] ) && $plugin['source'] == 'wp' ) {
				$status = install_plugin_install_status( $plugin );
				switch ( $status['status'] ) {
					case 'install':
						if ( $status['url'] ) {
							/* translators: 1: Plugin name and version. */
							$action_links[] = '<a class="install-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '" data-success="Installed"><span>' . __( 'Install Now' ) . '</span></a>';
						}

						break;
					case 'update_available':
						if ( $status['url'] ) {
							/* translators: 1: Plugin name and version */
							$action_links[] = '<a class="update-now button" data-plugin="' . esc_attr( $status['file'] ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Update Now' ) . '</span></a>';
						}

						break;
				}
				if ( learn_press_is_plugin_install( $file ) ) {
					if ( is_plugin_active( $file ) ) {
						$action_links[] = '<a class="button disable-now" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=' . $file, 'deactivate-plugin_' . $file ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Disable %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Disable Now', 'learnpress' ) . '</span></a>';
					} else {
						$action_links[] = '<a class="button enable-now" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( wp_nonce_url( 'plugins.php?action=activate&plugin=' . $file, 'activate-plugin_' . $file ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Enable %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Enable Now', 'learnpress' ) . '</span></a>';
					}
				}

			} else {
				// action links for premium add-ons installed
				if ( learn_press_is_plugin_install( $file ) ) {
					if ( is_plugin_active( $file ) ) {
						$action_links[] = '<a class="button disable-now" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=' . $file, 'deactivate-plugin_' . $file ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Disable %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Disable Now', 'learnpress' ) . '</span></a>';
					} else {
						$action_links[] = '<a class="button enable-now" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( wp_nonce_url( 'plugins.php?action=activate&plugin=' . $file, 'activate-plugin_' . $file ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Enable %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Enable Now', 'learnpress' ) . '</span></a>';
					}
				} else {
					// buy now button for premium add-ons
					if ( isset( $plugin['permarklink'] ) ) {
						$action_links[] = '<a class="buy-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $plugin['permarklink'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Buy %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Buy Now' ) . '</a>';
					}
				}
			}
		}

		return $action_links;
	}

	/**
	 * @param $icons
	 *
	 * @return string
	 */
	public static function get_add_on_icon( $icons ) {
		$icon = '';
		if ( ! empty( $icons['2x'] ) ) {
			$icon = $icons['2x'];
		} elseif ( ! empty( $icons['1x'] ) ) {
			$icon = $icons['1x'];
		} else {
			$icon = LP_PLUGIN_URL . 'assets/images/icon-128x128.png';
		}

		return $icon;
	}

	/**
	 * Get plugin icon.
	 *
	 * @param object $plugin_data
	 * @param string $plugin_file
	 *
	 * @return array
	 */
	public static function get_add_on_icons( $plugin_data, $plugin_file ) {
		$plugin_path = ABSPATH . LP_WP_CONTENT . '/plugins/' . $plugin_file;
		$icon_path   = dirname( $plugin_path ) . '/assets/images';
		$icons       = array(
			'2x' => '',
			'1x' => ''
		);
		foreach ( array( '2x' => 'icon-256x256', '1x' => 'icon-128x128' ) as $s => $name ) {
			foreach ( array( 'png', 'svg' ) as $t ) {
				if ( file_exists( $icon_path . "/{$name}.{$t}" ) ) {
					$icons[ $s ] = plugins_url( '/', $plugin_path ) . "assets/images/{$name}.{$t}";
					break;
				}
			}
		}

		return $icons;
	}

	/**
	 * Count plugins.
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public static function count_plugins( $type = '' ) {
		$plugins = self::get_plugins();
		if ( $type === 'installed' ) {
			return ! empty( $plugins['installed'] ) ? sizeof( $plugins['installed'] ) : 0;
		} else {
			$wp_plugins = ! empty( $plugins['free'] ) ? sizeof( $plugins['free'] ) : 0;
			$tp_plugins = ! empty( $plugins['premium'] ) ? sizeof( $plugins['premium'] ) : 0;

			return $wp_plugins + $tp_plugins;
		}
	}

	/**
	 * Filter plugin if it slug is starts with 'learnpress'
	 *
	 * @param object $plugin
	 *
	 * @return bool
	 */
	public static function _filter_plugin( $plugin ) {
		return $plugin && preg_match( '!^learnpress-.*!', $plugin->slug );
	}

	/**
	 * Sort plugins.
	 */
	public static function _sort_plugins() {
		foreach ( self::$plugins as $k => $plugin ) {
			if ( is_array( $plugin ) ) {
				ksort( $plugin );
				self::$plugins[ $k ] = $plugin;
			}
		}
	}

	/**
	 * Get all slugs of plugins have installed on site
	 *
	 * @return array
	 */
	public static function get_installed_plugin_slugs() {
		$slugs = array();

		$plugin_info = get_site_transient( 'update_plugins' );
		if ( isset( $plugin_info->no_update ) ) {
			foreach ( $plugin_info->no_update as $plugin ) {
				$slugs[] = $plugin->slug;
			}
		}

		if ( isset( $plugin_info->response ) ) {
			foreach ( $plugin_info->response as $plugin ) {
				$slugs[] = $plugin->slug;
			}
		}

		return $slugs;
	}

	/**
	 * Register extra headers for our plugins.
	 *
	 * @param $headers
	 *
	 * @return mixed
	 */
	public static function add_on_header( $headers ) {
		$headers['Tags']              = 'Tags';
		$headers['Requires at least'] = 'Requires at least';
		$headers['Tested up to']      = 'Tested up to';
		$headers['Last updated']      = 'Last updated';

		return $headers;
	}

	/**
	 * Initialize
	 */
	public static function init() {
		require_once( LP_PLUGIN_PATH . '/inc/admin/class-lp-upgrader.php' );
		add_filter( 'extra_plugin_headers', array( __CLASS__, 'add_on_header' ) );
	}
}

// Init hooks, etc...
LP_Plugins_Helper::init();