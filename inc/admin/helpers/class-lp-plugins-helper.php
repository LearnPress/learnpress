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
		self::$plugins['free']      = array_diff_key( $wp_plugins, $wp_installed );
		self::$plugins['premium']   = array_diff_key( $premium_plugins, $premium_installed );

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
			'installed_plugins' => learn_press_get_installed_plugin_slugs(),
			'author'            => 'thimpress'
		);
		$transient_key = "lp_plugins_wp";
		if ( ! ( $plugins = get_transient( $transient_key ) ) ) {
			self::require_plugins_api();

			$plugins = array();
			$api     = plugins_api( 'query_plugins', $query_args );
			if ( is_wp_error( $api ) ) {
				echo join( "", $api->errors['plugins_api_failed'] );

				return false;
			}
			if ( ! is_array( $api->plugins ) ) {
				return $plugins;
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
	 *
	 * @return array|mixed
	 */
	public static function get_related_themes( $type = '' ) {

		self::$themes = get_transient( 'lp_related_themes' );

		if ( isset( $_GET['check'] ) && wp_verify_nonce( $_GET['check'], 'lp_check_related_themes' ) || ! self::$themes ) {

			$url      = 'https://api.envato.com/v1/discovery/search/search/item?site=themeforest.net&username=thimpress';
			$args     = array(
				'headers' => array(
					"Authorization" => "Bearer BmYcBsYXlSoVe0FekueDxqNGz2o3JRaP"
				)
			);
			$response = wp_remote_request( $url, $args );

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

		return array_key_exists( $type, self::$themes ) ? self::$themes[ $type ] : self::$themes;

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
	 * Get action link of a plugin.
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
			if ( ! empty( $plugin['source'] ) && $plugin['source'] == 'wp' ) {
				$status = install_plugin_install_status( $plugin );
				switch ( $status['status'] ) {
					case 'install':
						if ( $status['url'] ) {
							/* translators: 1: Plugin name and version. */
							$action_links[] = '<a class="install-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Install Now' ) . '</span></a>';
						}

						break;
					case 'update_available':
						if ( $status['url'] ) {
							/* translators: 1: Plugin name and version */
							$action_links[] = '<a class="update-now button" data-plugin="' . esc_attr( $status['file'] ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Update Now' ) . '</span></a>';
						}

						break;
					case 'latest_installed':
					case 'newer_installed':
						$action_links[] = '<span class="button button-disabled" title="' . esc_attr__( 'This plugin is already installed and is up to date' ) . ' ">' . _x( 'Installed', 'plugin' ) . '</span>';
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
				if ( learn_press_is_plugin_install( $file ) ) {
					if ( is_plugin_active( $file ) ) {
						$action_links[] = '<a class="button disable-now" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=' . $file, 'deactivate-plugin_' . $file ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Disable %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Disable Now', 'learnpress' ) . '</span></a>';
					} else {
						$action_links[] = '<a class="button enable-now" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( wp_nonce_url( 'plugins.php?action=activate&plugin=' . $file, 'activate-plugin_' . $file ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Enable %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '"><span>' . __( 'Enable Now', 'learnpress' ) . '</span></a>';
					}
				} else {
					if ( $plugin['url'] ) {
						$action_links[] = '<a class="buy-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $plugin['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Buy %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Buy Now' ) . '</a>';
					}
				}
			}
			$action_links[] = '<p class="plugin-version">' . sprintf( __( 'Version: %s', 'learnpress' ), $plugin['version'] ) . '</p>';
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

	////////

	function learn_press_count_add_ons() {
		$count_fields = apply_filters(
			'learn_press_count_add_ons_fields',
			array( 'installed', 'all_plugins', 'related_themes' )
		);

		$count = 0;
		foreach ( $count_fields as $type ) {
			switch ( $type ) {
				case 'installed':
					$count = sizeof( LP_Addons_Helper::get_add_ons() );
					break;

				case 'all_plugins':
					$plugins = learnpress_get_all_plugins();
					$count   = 0;
					foreach ( $plugins as $type_plugins ) {
						$count += sizeof( $type_plugins );
					}
					break;

				case 'related_themes':
					$count = learn_press_related_themes();
					$count = sizeof( $count );
					break;
				default:
					$count = apply_filters( 'learn_press_count_add_ons_' . $type );

			}
			$count_fields[ $type ] = $count;
		}

		return $count_fields;
	}

	function learn_press_get_all_add_ons( $args = array() ) {
		$args   = wp_parse_args(
			$args,
			array( 'transient_key' => 'lp_more_add_ons', 'force' => false )
		);
		$addons = self::get_plugins_from_wp( $args );

		return apply_filters( 'learn_press_all_add_ons', $addons );
	}

	function learnpress_get_all_plugins() {

		if ( isset( $_GET['check'] ) && wp_verify_nonce( $_GET['check'], 'lp_check_all_plugins' ) ) {

			// Remove transient
			delete_transient( 'lp_more_add_ons' );
			delete_transient( 'lp_plugins_premium' );
		}

		$plugins = array();

		$plugins['free']    = learn_press_get_all_add_ons();
		$plugins['premium'] = learn_press_get_add_ons_premium();

		return $plugins;
	}

	function learn_press_get_more_add_ons() {
		$defaults = array();
	}

	/**
	 * Default tabs for add ons page
	 *
	 * @return array
	 */
	function learn_press_get_add_on_tabs() {
		$counts   = learn_press_count_add_ons();
		$defaults = array(
			'installed'      => array(
				'text'  => sprintf( __( 'Installed <span class="count">(%s)</span>', 'learnpress' ), $counts['installed'] ),
				'class' => '',
				'url'   => ''
			),
			'all_plugins'    => array(
				'text'  => sprintf( __( 'Add-ons <span class="count">(%s)</span>', 'learnpress' ), $counts['all_plugins'] ),
				'class' => '',
				'url'   => ''
			),
			'related_themes' => array(
				'text'  => sprintf( __( 'Related Themes <span class="count">(%s)</span>', 'learnpress' ), $counts['related_themes'] ),
				'class' => '',
				'url'   => ''
			)
		);

		return apply_filters( 'learn_press_add_on_tabs', $defaults );
	}

	/**
	 * Print all tab
	 *
	 * @param $current
	 */
	function learn_press_print_add_on_tab( $current ) {
		$active = ( empty( $current ) || 'installed' == $current ) ? 'nav-tab-active' : '';
		?>
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo $active; ?>"
               href="<?php echo admin_url( 'admin.php?page=learn-press-addons' ); ?>"><?php _e( 'All', 'learnpress' ); ?></a>
			<?php do_action( 'learn_press_print_add_on_page_tab', $current ) ?>
        </h2>
		<?php
	}

	/**
	 * Print get more tab
	 *
	 * @param $current
	 */
	function learn_press_print_get_more_tab( $current ) {
		$active = ( empty( $current ) || 'get_more' == $current ) ? 'nav-tab-active' : '';
		?>
        <a class="nav-tab <?php echo $active; ?>"
           href="<?php echo admin_url( 'admin.php?page=learn-press-addons&tab=get_more' ); ?>"><?php _e( 'Get More', 'learnpress' ); ?></a>
		<?php
	}


	function learn_press_get_installed_plugin_slugs() {
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
	 * @param $slug
	 *
	 * @return mixed|void
	 */
	function learn_press_get_add_on( $slug ) {
		if ( $add_ons = LP_Addons_Helper::get_add_ons() ) {
			if ( ! empty( $add_ons[ $slug ] ) ) {
				return $add_ons[ $slug ];
			}
		}

		return apply_filters( 'learn_press_get_addon', false, $slug, $add_ons );
	}

	function learn_press_add_on_header( $headers ) {
		$headers['Tags']              = 'Tags';
		$headers['Requires at least'] = 'Requires at least';
		$headers['Tested up to']      = 'Tested up to';
		$headers['Last updated']      = 'Last updated';

		return $headers;
	}

	function learn_press_get_plugin_data( $plugins ) {
		global $wp_version;
		//$plugins = get_plugins();
		$translations = wp_get_installed_translations( 'plugins' );

		$active  = get_option( 'active_plugins', array() );
		$current = get_site_transient( 'update_plugins' );

		$to_send = compact( 'plugins', 'active' );

		$locales = array( get_locale() );

		$options = array(
			'timeout'    => 30,
			'body'       => array(
				'plugins'      => wp_json_encode( $to_send ),
				'translations' => wp_json_encode( $translations ),
				'locale'       => wp_json_encode( $locales ),
				'all'          => wp_json_encode( true ),
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		);

		/*if ( $extra_stats ) {
			$options['body']['update_stats'] = wp_json_encode( $extra_stats );
		}*/

		$url = $http_url = 'http://api.wordpress.org/plugins/update-check/1.1/';
		if ( $ssl = wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$raw_response = wp_remote_post( $url, $options );
		if ( $ssl && is_wp_error( $raw_response ) ) {
			trigger_error( __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.', 'learnpress' ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)', 'learnpress' ), headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE );
			$raw_response = wp_remote_post( $http_url, $options );
		}
		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
		//print_r($response);
	}


	/**
	 * Install and active a plugin from slug name
	 *
	 * @param $slug
	 *
	 * @return array
	 */
	function learn_press_install_and_active_add_on( $slug ) {
		self::require_plugins_api();
		if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		$api   = plugins_api( 'plugin_information', array(
			'slug'   => $slug,
			'fields' => array( 'sections' => false )
		) );
		$title = sprintf( __( 'Installing Plugin: %s', 'learnpress' ), $api->name . ' ' . $api->version );
		$nonce = 'install-plugin_' . $slug;
		$url   = 'update.php?action=install-plugin&plugin=' . urlencode( $slug );

		$plugin = learn_press_plugin_basename_from_slug( $slug );
		$return = array();
		if ( ! learn_press_is_plugin_install( $plugin ) ) {

			$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				$return['error']       = $result;
				$return['status']      = 'not_install';
				$return['status_text'] = __( 'Not install', 'learnpress' );
			} else {

				$return['result']      = $result;
				$return['status']      = 'installed';
				$return['status_text'] = __( 'Installed', 'learnpress' );
			}
		}
		$plugin = learn_press_plugin_basename_from_slug( $slug );
		//echo "[$plugin]";
		if ( learn_press_is_plugin_install( $plugin ) ) {
			activate_plugin( $plugin, false, is_network_admin() );
			// ensure that plugin is enabled
			$is_activate           = is_plugin_active( $plugin );
			$return['status']      = $is_activate ? 'activate' : 'deactivate';
			$return['status_text'] = $is_activate ? __( 'Enabled', 'learnpress' ) : __( 'Disabled', 'learnpress' );
		}
		$return['plugin_file'] = $plugin;

		return $return;
	}

	/**
	 * Print plugin information after WP installed a plugin
	 *
	 * @param $a
	 * @param $b
	 * @param $result
	 */
	function learn_press_upgrader_post_install( $a, $b, $result ) {
		if ( ! empty( $_REQUEST['learnpress'] ) && $_REQUEST['learnpress'] == 'active' ) {
			if ( is_wp_error( $result ) ) {

			} else {
				$slug   = $_REQUEST['plugin'];
				$plugin = learn_press_plugin_basename_from_slug( $slug );


				activate_plugin( $plugin, false, is_network_admin() );
				// ensure that plugin is enabled
				$is_activate           = is_plugin_active( $plugin );
				$result['status']      = $is_activate ? 'activate' : 'deactivate';
				$result['status_text'] = $is_activate ? __( 'Enabled', 'learnpress' ) : __( 'Disabled', 'learnpress' );
			}
			learn_press_send_json( $result );
		}
	}


	public static function require_plugins_api() {
		if ( ! function_exists( 'plugins_api' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		}
	}

	function learn_press_add_on_tab_description( $description ) {
		?>
        <p class="description"><?php echo $description; ?></p>
		<?php
	}

	function learn_press_add_ons_content_tab_installed( $current ) {
		$add_ons = LP_Addons_Helper::get_add_ons();
		learn_press_add_on_tab_description( __( 'All add-ons that you have installed', 'learnpress' ) );
		learn_press_output_add_ons_list( $add_ons, $current );
	}


	function learn_press_add_ons_content_tab_all_plugins( $current ) {

		/* Description */
		$time        = get_option( '_transient_timeout_lp_more_add_ons' );
		$description = __( 'All add-ons we provide.', 'learnpress' );
		$description .= ' ' . sprintf( __( 'Last checked %s ago', 'learnpress' ), human_time_diff( $time - LP_ADD_ON_TRANSIENT_TIME ) );
		$description .= ' ' . sprintf( __( '<a href="%s">%s</a>' ), admin_url( 'admin.php?page=learn-press-addons&tab=all_plugins&check=' . wp_create_nonce( 'lp_check_all_plugins' ) ), __( 'Check again!', 'learnpress' ) );
		learn_press_add_on_tab_description( $description );
		/* End Description */

		// Render Content Tab
		$plugins = array(
			'free'    => get_transient( 'lp_more_add_ons' ),
			'premium' => get_transient( 'lp_plugins_premium' ),
		);
		learn_press_output_add_ons_all_plugins( $plugins, $current );
	}


	function learn_press_get_add_ons_premium() {

		$plugins  = array();
		$url      = 'https://thimpress.com/?thimpress_get_addons=premium';
		$response = wp_remote_get( esc_url_raw( $url ), array( 'decompress' => false ) );

		if ( ! is_wp_error( $response ) ) {

			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response, true );

			if ( ! empty( $response ) ) {

				foreach ( $response as $key => $item ) {
					if ( empty( $item['price'] ) ) {
						unset( $response[ $key ] );
					}
				}

				$plugins = $response;

				set_transient( 'lp_plugins_premium', $plugins, LP_ADD_ON_TRANSIENT_TIME );

			}
		}

		return $plugins;
	}

	function learn_press_add_ons_content_tab_related_themes( $current ) {

		$related_themes = get_transient( 'lp_addon_related_themes' );
		$time           = get_option( '_transient_timeout_lp_addon_related_themes' );
		$description    = __( 'All add-ons we provide.', 'learnpress' );
		$description    .= ' ' . sprintf( __( 'Last checked %s ago', 'learnpress' ), human_time_diff( $time - 24 * LP_ADD_ON_TRANSIENT_TIME ) );
		$description    .= ' ' . sprintf( __( '<a href="%s">%s</a>' ), admin_url( 'admin.php?page=learn-press-addons&tab=related_themes&check=' . wp_create_nonce( 'lp_check_related_themes' ) ), __( 'Check again!', 'learnpress' ) );
		learn_press_add_on_tab_description( $description );
		learn_press_output_related_themes_list( $related_themes, $current );
	}


	function learn_press_get_add_ons_themes() {
		global $learnpress_list_themes;
		$list_theme = array();

		if ( ! empty( $learnpress_list_themes ) ) {
			return $learnpress_list_themes;
		}

		if ( ! get_transient( 'learnpress_theme_premium' ) !== false ) {
			$list_theme = get_transient( 'learnpress_theme_premium' );
			$list_theme = json_decode( $list_theme );

			if ( empty( $learnpress_list_themes ) ) {
				$learnpress_list_themes = $list_theme;
			}

			return $list_theme;
		}

		$url      = 'https://api.envato.com/v1/market/new-files-from-user:thimpress,themeforest.json';
		$args     = array(
			'headers' => array(
				"Authorization" => "Bearer BmYcBsYXlSoVe0FekueDxqNGz2o3JRaP"
			)
		);
		$response = wp_remote_request( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response, true );
			if ( ! empty( $response ) && ! empty( $response['new-files-from-user'] ) ) {
				$add_ons = $response['new-files-from-user'];

				foreach ( $add_ons as $add_on ) {
					$url      = 'https://api.envato.com/v3/market/catalog/item?id=' . $add_on['id'];
					$response = wp_remote_request( $url, $args );

					if ( ! is_wp_error( $response ) ) {
						$response = wp_remote_retrieve_body( $response );
						$response = json_decode( $response, true );

						$list_theme[] = $response;
					}
				}
			}
			if ( empty( $learnpress_list_themes ) ) {
				$learnpress_list_themes = $list_theme;
			}
			$learnpress_add_on_theme = json_encode( $list_theme );


			set_transient( 'learnpress_theme_premium', $learnpress_add_on_theme, LP_ADD_ON_TRANSIENT_TIME );
		}

		return $list_theme;
	}


	function learn_press_output_add_ons_all_plugins( $plugins, $tab = '' ) {

		if ( ! is_array( $plugins ) ) {
			printf( '<h3>%s</h3>', __( 'No add-on found', 'learnpress' ) );

			return false;
		}
		$size = 0;

		foreach ( $plugins as $plugin ) {
			$size += sizeof( $plugin );
		}

		if ( $size === 0 ) {
			printf( '<h3>%s</h3>', __( 'No add-on found', 'learnpress' ) );

			return false;

		}

		echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';

		// Render Free Plugins
		if ( ! empty( $plugins['free'] ) ) {
			/* Arrange plugin free for number of download */
			$number_downloads = array();
			foreach ( $plugins['free'] as $key => $plugin ) {
				$number_downloads[ $key ] = $plugin['active_installs'];
			}
			arsort( $number_downloads );
			$sort_plugins = array();
			foreach ( $number_downloads as $key => $plugin ) {
				$sort_plugins[ $key ] = $plugins['free'][ $key ];
			}
			?>
            <li class="learnpress-free-plugin-wrap">
				<?php learn_press_output_add_ons_list( $sort_plugins, 'learnpress-free-plugin' ); ?>
            </li>
			<?php
		}

		// Render Premium Plugins
		if ( ! empty( $plugins['premium'] ) ) {
			?>
            <li class="learnpress-premium-plugin-wrap">
				<?php learn_press_output_premium_add_ons_list( $plugins['premium'], 'learnpress-premium-plugin' ); ?>
            </li>
			<?php
		}
		echo '</ul>';

	}

	function learn_press_output_add_ons_list( $add_ons, $tab = '' ) {

		if ( $tab === 'learnpress-free-plugin' ) {
			echo '<h2>' . __( 'Free Add-ons', 'learnpress' ) . ' (<span class="learnpress-count-addon">' . sizeof( $add_ons ) . '</span>) </h2>';
		}
		echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';
		foreach ( $add_ons as $file => $add_on ) {

			$action_links = learn_press_get_add_on_action_link( $add_on, $file );

			?>
            <li class="plugin-card plugin-card-learnpress" id="learn-press-plugin-<?php echo $add_on['slug']; ?>">
                <div class="plugin-card-top">
                <span class="plugin-icon"><img
                            src="<?php echo learn_press_get_add_on_icon( $add_on['icons'] ); ?>"></span>

                    <div class="name column-name">
                        <h3><?php echo $add_on['name']; ?></h3>
                    </div>
                    <div class="action-links">
						<?php
						if ( $action_links ) {
							echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
						}
						?>
                    </div>
                    <div class="desc column-description">
                        <p><?php echo $add_on['short_description']; ?></p>

                        <p class="authors"><?php printf( __( '<cite>By %s</cite>', 'learnpress' ), $add_on['author'] ); ?></p>
                    </div>
                </div>
                <div class="plugin-card-bottom">
					<?php if ( array_key_exists( 'rating', $add_on ) ) { ?>
                        <div class="vers column-rating">
							<?php wp_star_rating( array(
								'rating' => $add_on['rating'],
								'type'   => 'percent',
								'number' => $add_on['num_ratings']
							) ); ?>
                            <span class="num-ratings">(<?php echo number_format_i18n( $add_on['num_ratings'] ); ?>
                                )</span>
                        </div>
					<?php } ?>
					<?php if ( array_key_exists( 'last_updated', $add_on ) ) { ?>
						<?php
						$date_format            = 'M j, Y @ H:i';
						$last_updated_timestamp = strtotime( $add_on['last_updated'] );
						?>
                        <div class="column-updated">
                            <strong><?php _e( 'Last Updated:', 'learnpress' ); ?></strong> <span
                                    title="<?php echo esc_attr( date_i18n( $date_format, $last_updated_timestamp ) ); ?>">
						<?php printf( __( '%s ago', 'learnpress' ), human_time_diff( $last_updated_timestamp ) ); ?>
					    </span>
                        </div>
					<?php } ?>
					<?php if ( array_key_exists( 'active_installs', $add_on ) ) { ?>
                        <div class="column-downloaded">
							<?php
							if ( $add_on['active_installs'] >= 1000000 ) {
								$active_installs_text = _x( '1+ Million', 'Active plugin installs' );
							} else {
								$active_installs_text = number_format_i18n( $add_on['active_installs'] ) . '+';
							}
							printf( __( '%s Active Installs', 'learnpress' ), $active_installs_text );
							?>
                        </div>
					<?php } ?>

                    <div class="column-compatibility">
						<?php
						if ( ! empty( $add_on['tested'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['tested'] ) ), $add_on['tested'], '>' ) ) {
							echo '<span class="compatibility-untested">' . __( 'Untested with your version of WordPress', 'learnpress' ) . '</span>';
						} elseif ( ! empty( $plugin['requires'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['requires'] ) ), $add_on['requires'], '<' ) ) {
							echo '<span class="compatibility-incompatible">' . __( '<strong>Incompatible</strong> with your version of WordPress', 'learnpress' ) . '</span>';
						} else {
							echo '<span class="compatibility-compatible">' . __( '<strong>Compatible</strong> with your version of WordPress', 'learnpress' ) . '</span>';
						}
						?>
                    </div>
                </div>
            </li>
			<?php
		}
		echo '</ul>';
	}

	function learn_press_output_premium_add_ons_list( $add_ons, $tab = '' ) {

		echo '<h2>' . __( 'Premium Add-ons', 'learnpress' ) . ' (<span class="learnpress-count-addon">' . sizeof( $add_ons ) . '</span>) </h2>';
		echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';
		foreach ( $add_ons as $file => $add_on ) {
			$add_on['permarklink'] = add_query_arg( array(
				'ref'        => 'ThimPress',
				'utm_source' => 'lp-backend',
				'utm_medium' => 'lp-addondashboard'
			), $add_on['permarklink'] );
			?>
            <li class="plugin-card plugin-card-learnpress" id="learn-press-plugin-<?php echo $add_on['slug']; ?>">
                <div class="plugin-card-top">
                    <a href="<?php echo esc_url( $add_on['permarklink'] ); ?>">
                        <span class="plugin-icon"><img src="<?php echo esc_url( $add_on['icons'] ); ?>"></span>
                    </a>

                    <div class="name column-name">
                        <h3><?php echo $add_on['name']; ?></h3>
                    </div>
                    <div class="action-links">
                        <ul class="plugin-action-buttons">
                            <li>
                                <a class="button"
                                   href="<?php echo esc_url( $add_on['permarklink'] ); ?>"><?php echo __( 'Buy Now', 'learnpress' ) ?></a>
                            </li>
                            <li>
                            <span class="price">
                                <?php
                                if ( ! empty( $add_on['sale'] ) && absint( $add_on['regular_price'] ) != 0 ) {
	                                ?>
                                    <del>
                                        <span class="amount">
                                            <span class="currencySymbol">$</span><?php echo esc_html( $add_on['regular_price'] ); ?>
                                        </span>
                                    </del>
	                                <?php
                                }
                                ?>
                                <ins>
                                    <span class="amount">
                                        <span class="currencySymbol">$</span><?php echo esc_html( $add_on['price'] ); ?>
                                    </span>
                                </ins>

                            </span>
                            </li>
                        </ul>
                    </div>
                    <div class="desc column-description">
                        <p><?php echo $add_on['short_description']; ?></p>

                        <p class="authors"><?php printf( __( '<cite>By %s</cite>', 'learnpress' ), $add_on['author'] ); ?></p>
                    </div>
                </div>
            </li>
			<?php
		}
		echo '</ul>';
	}

	function learn_press_output_related_themes_list( $add_ons, $tab = '' ) {
		if ( ! is_array( $add_ons ) || sizeof( $add_ons ) == 0 ) {
			printf( '<h3>%s</h3>', __( 'No theme found', 'learnpress' ) );

			return false;
		}
		/* ID of items education */
		$themes_education = $add_ons;

		$themes_id = array(
			'14058034' => 'eduma',
			'17097658' => 'coach',
			'11797847' => 'lms'
		);
		foreach ( $themes_education as $key => $theme ) {

			if ( ! array_key_exists( $theme['id'], $themes_id ) ) {
				unset( $themes_education[ $key ] );
			} else {
				unset( $add_ons[ $key ] );
			}
		}
		$list_themes = array(
			'education' => $themes_education,
			'other'     => $add_ons
		);

		echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';
		foreach ( $list_themes as $file => $list ) {
			$functions = 'learn_press_output_related_themes_list_' . $file
			?>
            <li class="learnpress-theme-<?php echo esc_attr( $file ); ?>">
				<?php call_user_func( $functions, $list, $file ); ?>
            </li>
			<?php
		}
		echo '</ul>';

	}

	function learn_press_output_related_themes_list_education( $add_ons, $tab ) {
		echo '<h2 class="learnpress-title">' . __( 'Education Support' ) . ' (<span class="learnpress-count">' . sizeof( $add_ons ) . '</span>) </h2>';
		echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';
		foreach ( $add_ons as $file => $add_on ) {
			$add_on['url'] = add_query_arg( array(
				'ref'        => 'ThimPress',
				'utm_source' => 'lp-backend',
				'utm_medium' => 'lp-addondashboard'
			), $add_on['url'] );
			?>
            <li class="plugin-card plugin-card-learnpress" id="learn-press-theme-<?php echo $add_on['id']; ?>">
                <div class="plugin-card-top">
                    <div class="image-thumbnail">
                        <a href="<?php echo esc_url( $add_on['url'] ); ?>">
                            <img src="<?php echo esc_url( $add_on['previews']['landscape_preview']['landscape_url'] ); ?>"
                                 alt="<?php echo esc_attr( $add_on['name'] ); ?>">
                        </a>
                    </div>

                    <div class="theme-content">
                        <h2 class="theme-title">
                            <a href="<?php echo esc_url( $add_on['url'] ); ?>">
								<?php echo wp_kses_post( $add_on['name'] ); ?>
                            </a>
                        </h2>
                        <div class="theme-detail">
                            <div class="theme-price">
								<?php echo $add_on['price_cents'] / 100 . __( '$', 'learnpress' ); ?>
                            </div>
                            <div class="number-sale">
								<?php echo $add_on['number_of_sales'] . __( ' sales', 'learnpress' ); ?>
                            </div>
                        </div>

                        <div class="theme-description">
							<?php
							$description = $add_on['description'];
							$description = preg_replace( "/<(.*?)>/", '', $description );
							echo wp_kses_post( $description );

							?>
                        </div>
                        <div class="theme-footer">
							<?php
							$demo          = $add_on['attributes'][4];
							$demo['value'] = add_query_arg( array(
								'ref'        => 'ThimPress',
								'utm_source' => 'lp-backend',
								'utm_medium' => 'lp-addondashboard'
							), $demo['value'] );
							?>
                            <a class="button button-primary"
                               href="<?php echo esc_url( $add_on['url'] ); ?>"><?php echo __( 'Get it now', 'learnpress' ) ?></a>
                            <a class="button"
                               href="<?php echo esc_url( $demo['value'] ); ?>"><?php _e( 'View Demo', 'learnpress' ); ?></a>
                            <div class="theme-rating">
                            <span class="">
                                <?php wp_star_rating( array(
	                                'rating' => $add_on['rating']['rating'],
	                                'type'   => 'rating',
	                                'number' => $add_on['rating']['count']
                                ) ); ?>
                            </span>
                                <span class="count-rating">(<?php echo $add_on['rating']['count']; ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
			<?php
		}
		echo '</ul>';
	}

	function learn_press_output_related_themes_list_other( $add_ons, $tab ) {
		echo '<h2 class="learnpress-title">' . __( 'Other' ) . ' (<span class="learnpress-count">' . sizeof( $add_ons ) . '</span>) </h2>';
		echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';
		foreach ( $add_ons as $file => $add_on ) {
			$add_on['url'] = add_query_arg( array(
				'ref'        => 'ThimPress',
				'utm_source' => 'lp-backend',
				'utm_medium' => 'lp-addondashboard'
			), $add_on['url'] );
			?>
            <li class="plugin-card plugin-card-learnpress" id="learn-press-theme-<?php echo $add_on['id']; ?>">
                <div class="plugin-card-top">
                    <div class="image-thumbnail">
                        <a href="<?php echo esc_url( $add_on['url'] ); ?>">
                            <img src="<?php echo esc_url( $add_on['previews']['landscape_preview']['landscape_url'] ); ?>"
                                 alt="<?php echo esc_attr( $add_on['name'] ); ?>">
                        </a>
                    </div>

                    <div class="theme-content">
                        <h2 class="theme-title">
                            <a href="<?php echo esc_url( $add_on['url'] ); ?>">
								<?php echo wp_kses_post( $add_on['name'] ); ?>
                            </a>
                        </h2>
                        <div class="theme-detail">
                            <div class="theme-price">
								<?php echo $add_on['price_cents'] / 100 . __( '$', 'learnpress' ); ?>
                            </div>
                            <div class="number-sale">
								<?php echo $add_on['number_of_sales'] . __( ' sales', 'learnpress' ); ?>
                            </div>
                        </div>

                        <div class="theme-description">
							<?php
							$description = $add_on['description'];
							$description = preg_replace( "/<(.*?)>/", '', $description );
							echo wp_kses_post( $description );

							?>
                        </div>
                        <div class="theme-footer">
							<?php
							$demo          = $add_on['attributes'][4];
							$demo['value'] = add_query_arg( array(
								'ref'        => 'ThimPress',
								'utm_source' => 'lp-backend',
								'utm_medium' => 'lp-addondashboard'
							), $demo['value'] );
							?>
                            <a class="button button-primary"
                               href="<?php echo esc_url( $add_on['url'] ); ?>"><?php echo __( 'Get it now', 'learnpress' ) ?></a>
                            <a class="button"
                               href="<?php echo esc_url( $demo['value'] ); ?>"><?php _e( 'View Demo', 'learnpress' ); ?></a>
                            <div class="theme-rating">
                            <span class="">
                                <?php wp_star_rating( array(
	                                'rating' => $add_on['rating']['rating'],
	                                'type'   => 'rating',
	                                'number' => $add_on['rating']['count']
                                ) ); ?>
                            </span>
                                <span class="count-rating">(<?php echo $add_on['rating']['count']; ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
			<?php
		}
		echo '</ul>';
	}

	function learn_press_add_on_admin_script() {
		wp_enqueue_media();
	}


	function learn_press_get_premium_add_ons( $addons ) {
		$add                          = end( $addons );
		$addons['this-is-new-add-on'] = $add;

		return $addons;
	}

	public
	static function init() {
		require_once( LP_PLUGIN_PATH . '/inc/admin/class-lp-upgrader.php' );
		add_filter( 'extra_plugin_headers', 'learn_press_add_on_header' );
		add_filter( 'upgrader_post_install', 'learn_press_upgrader_post_install', 10, 3 );

		add_action( 'admin_enqueue_scripts', 'learn_press_add_on_admin_script' );
		add_action( 'learn_press_add_ons_content_tab_all_plugins', 'learn_press_add_ons_content_tab_all_plugins' );
		add_action( 'learn_press_add_ons_content_tab_related_themes', 'learn_press_add_ons_content_tab_related_themes' );
		add_action( 'learn_press_add_ons_content_tab_installed', 'learn_press_add_ons_content_tab_installed' );
	}

}