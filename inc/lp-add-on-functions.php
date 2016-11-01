<?php
/**
 * Common functions use for add-ons manager page
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'LP_ADD_ON_TRANSIENT_TIME', 60 * 5 );
function learn_press_count_add_ons() {
	$count_fields = apply_filters(
		'learn_press_count_add_ons_fields',
		array( 'installed', 'bundle_activate', 'more' )
	);
	$count        = 0;
	foreach ( $count_fields as $type ) {
		switch ( $type ) {
			case 'installed':
				$count = sizeof( learn_press_get_add_ons() );
				break;
			case 'bundle_activate':
				$count = sizeof( learn_press_get_bundle_activate_add_ons() );
				break;
			case 'more':
				if ( $plugins = learn_press_get_all_add_ons() ) {
					$count = sizeof( $plugins );
				}
				break;
			default:
				$count = apply_filters( 'learn_press_count_add_ons_' . $type );

		}
		$count_fields[$type] = $count;
	}
	return $count_fields;
}

function learn_press_get_all_add_ons( $args = array() ) {
	$args   = wp_parse_args(
		$args,
		array( 'transient_key' => 'lp_more_add_ons', 'force' => false )
	);
	$addons = learn_press_get_add_ons_from_wp( $args );
	return apply_filters( 'learn_press_all_add_ons', $addons );
}

function learn_press_get_bundle_activate_add_ons() {
	$defaults = array(
		'learnpress-course-review',
		'learnpress-import-export',
		'learnpress-prerequisites-courses',
		'learnpress-wishlist'
	);
	return apply_filters( 'learn_press_bundle_activate_add_ons', $defaults );
}

function learn_press_get_more_add_ons() {
	$defaults = array();
}

require_once( LP_PLUGIN_PATH . '/inc/admin/class-lp-upgrader.php' );
/**
 * Default tabs for add ons page
 *
 * @return array
 */
function learn_press_get_add_on_tabs() {
	$counts   = learn_press_count_add_ons();
	$defaults = array(
		'installed'       => array(
			'text'  => sprintf( __( 'Installed <span class="count">(%s)</span>', 'learnpress' ), $counts['installed'] ),
			'class' => '',
			'url'   => ''
		),
		'bundle_activate' => array(
			'text'  => sprintf( __( 'Bundle Activate <span class="count">(%s)</span>', 'learnpress' ), $counts['bundle_activate'] ), __( '', 'learnpress' ),
			'class' => '',
			'url'   => ''
		),
		'more'            => array(
			'text'  => sprintf( __( 'Get more <span class="count">(%s)</span>', 'learnpress' ), $counts['more'] ), __( '', 'learnpress' ),
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
		<a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons' ); ?>"><?php _e( 'All', 'learnpress' ); ?></a>
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
	<a class="nav-tab <?php echo $active; ?>" href="<?php echo admin_url( 'admin.php?page=learn_press_add_ons&tab=get_more' ); ?>"><?php _e( 'Get More', 'learnpress' ); ?></a>
	<?php
}

function learn_press_get_add_on_icons( $plugin_data, $plugin_file ) {
	$plugin_path = ABSPATH . 'wp-content/plugins/' . $plugin_file;
	$icon_path   = dirname( $plugin_path ) . '/assets/images';
	$icons       = array(
		'2x' => '',
		'1x' => ''
	);
	foreach ( array( '2x' => 'icon-256x256', '1x' => 'icon-128x128' ) as $s => $name ) {
		foreach ( array( 'png', 'svg' ) as $t ) {
			if ( file_exists( $icon_path . "/{$name}.{$t}" ) ) {
				$icons[$s] = plugins_url( '/', $plugin_path ) . "assets/images/{$name}.{$t}";
				break;
			}
		}
	}
	return $icons;
}

function learn_press_get_add_on_icon( $icons ) {
	$icon = '';
	if ( !empty( $icons['2x'] ) ) {
		$icon = $icons['2x'];
	} elseif ( !empty( $icons['1x'] ) ) {
		$icon = $icons['1x'];
	} else {
		$icon = LP_PLUGIN_URL . 'assets/images/icon-128x128.png';
	}
	return $icon;
}

/**
 * Get all add-ons for LearnPress has installed
 * Identify a plugin is an add-on if it is already existing a tag 'learnpress' inside
 *
 * @param array $options
 *
 * @return mixed|string|void
 */
function learn_press_get_add_ons( $options = array() ) {
	$plugins = array();
	if ( !function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}
	/*
	 * Delete cache so hook for extra plugin headers works
	 */
	wp_cache_delete( 'plugins', 'plugins' );
	$all_plugins = get_plugins();
	if ( $all_plugins ) {
		$wp_plugins = learn_press_get_add_ons_from_wp();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			if ( !empty( $plugin_data['Tags'] ) ) {
				$tags = ( preg_split( '/\s*,\s*/', $plugin_data['Tags'] ) );
				if ( !in_array( 'learnpress', $tags ) ) {
					continue;
				}
				$plugin_slug = dirname( $plugin_file );
				if ( isset( $wp_plugins[$plugin_file] ) ) {
					$plugins[$plugin_file]           = (array) $wp_plugins[$plugin_file];
					$plugins[$plugin_file]['source'] = 'wp';
				} else {
					$plugin_data           = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
					$plugins[$plugin_file] = array(
						'name'              => $plugin_data['Name'],
						'slug'              => $plugin_slug,
						'version'           => $plugin_data['Version'],
						'author'            => sprintf( '<a href="%s">%s</a>', $plugin_data['AuthorURI'], $plugin_data['Author'] ),
						'author_profile'    => '',
						'contributors'      => array(),
						//'requires' => ,
						//'tested' => $plugin_data['Tested up to'],
						//'rating' => null,
						//'num_ratings' => null,
						//'ratings' => null,
						//'active_installs' => null,
						//'last_updated' => isset( $plugin_data['Requires at least'] ) ? $plugin_data['Requires at least'] : '',
						'homepage'          => $plugin_data['PluginURI'],
						'short_description' => $plugin_data['Description'],
						'icons'             => learn_press_get_add_on_icons( $plugin_data, $plugin_file )
					);
					if ( !empty( $plugin_data['Requires at least'] ) ) {
						$plugins[$plugin_file]['requires'] = $plugin_data['Requires at least'];
					}
					if ( !empty( $plugin_data['Tested up to'] ) ) {
						$plugins[$plugin_file]['tested'] = $plugin_data['Tested up to'];
					}
					if ( !empty( $plugin_data['Last updated'] ) ) {
						$plugins[$plugin_file]['last_updated'] = $plugin_data['Last updated'];
					}
				}
			}
		}
	}

	return $plugins;
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
	if ( $add_ons = learn_press_get_add_ons() ) {
		if ( !empty( $add_ons[$slug] ) )
			return $add_ons[$slug];
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

add_filter( 'extra_plugin_headers', 'learn_press_add_on_header' );
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
	if ( $ssl = wp_http_supports( array( 'ssl' ) ) )
		$url = set_url_scheme( $url, 'https' );

	$raw_response = wp_remote_post( $url, $options );
	if ( $ssl && is_wp_error( $raw_response ) ) {
		trigger_error( __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.', 'learnpress' ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)', 'learnpress' ), headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE );
		$raw_response = wp_remote_post( $http_url, $options );
	}
	$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
	//print_r($response);
}


/**
 * Query the list of add-ons from wordpress.org with keyword 'learnpress'
 * This requires have a keyword named 'learnpress' in plugin header Tags
 *
 * @param array
 *
 * @return array
 */
function learn_press_get_add_ons_from_wp( $args = null ) {
	learn_press_require_plugins_api();
	$tag  = 'learnpress';
	$args = wp_parse_args(
		$args,
		array(
			'search'        => array( $tag ),
			'include'       => null,
			'exclude'       => null,
			'transient_key' => null,
			'force'         => false
		)
	);
	// the number of plugins on each page queried, when we can reach to this figure?
	$per_page   = 20;
	$paged      = 1;
	$query_args = array(
		'page'              => $paged,
		'per_page'          => $per_page,
		'fields'            => array(
			'last_updated'    => true,
			'icons'           => true,
			'active_installs' => true
		),
		//'tag'               => $tag,
		// Send the locale and installed plugin slugs to the API so it can provide context-sensitive results.
		'locale'            => get_locale(),
		'installed_plugins' => learn_press_get_installed_plugin_slugs(),
		'search'            => $args['search'],
		'include'           => $args['include'],
		'exclude'           => $args['exclude'] // not effect for search, only to build unique id for transient
	);

	if ( !empty( $args['transient_key'] ) ) {
		$transient_key = $args['transient_key'];
	} else {
		$transient_key = "lpaddons_" . md5( serialize( $query_args ) );
	}
	//delete_transient( $transient_key );
	if ( !( $plugins = get_transient( $transient_key ) ) || ( !empty( $args['force'] ) && $args['force'] == true ) ) {
		$plugins = array();
		learn_press_require_plugins_api();
		$api = plugins_api( 'query_plugins', $query_args );
		if ( is_wp_error( $api ) ) {
			echo join( "", $api->errors['plugins_api_failed'] );
			return false;
		}
		if ( is_array( $api->plugins ) ) {
			$all_plugins = get_plugins();
			// filter plugins with tag contains 'learnpress'
			$_plugins = array_filter( $api->plugins, create_function( '$plugin', 'return preg_match(\'!^learnpress.*!\', $plugin->slug);' ) );

			// Ensure that the array is indexed from 0
			$_plugins = array_values( $_plugins );

			$exclude     = (array) $args['exclude'];
			$include     = $args['include'];
			$has_include = is_array( $include ) ? sizeof( $include ) : false;
			for ( $n = sizeof( $_plugins ), $i = $n - 1; $i >= 0; $i -- ) {

				$plugin = $_plugins[$i];
				$key    = $plugin->slug;
				foreach ( $all_plugins as $file => $p ) {
					if ( strpos( $file, $plugin->slug ) !== false ) {
						$key = $file;
						break;
					}
				}
				$plugin->source = 'wp';
				if ( !in_array( $plugin->slug, $exclude ) ) {
					if ( $has_include ) {
						if ( in_array( $plugin->slug, $include ) ) {
							$plugins[$key] = (array) $plugin;
						}
					} else {
						$plugins[$key] = (array) $plugin;
					}
				}
			}
			set_transient( $transient_key, $plugins, LP_ADD_ON_TRANSIENT_TIME );
		} else {

		}
	} else {
	}
	return $plugins;
}

/**
 * Install and active a plugin from slug name
 *
 * @param $slug
 *
 * @return array
 */
function learn_press_install_and_active_add_on( $slug ) {

	learn_press_require_plugins_api();
	if ( !class_exists( 'Plugin_Upgrader', false ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}
	$api   = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
	$title = sprintf( __( 'Installing Plugin: %s', 'learnpress' ), $api->name . ' ' . $api->version );
	$nonce = 'install-plugin_' . $slug;
	$url   = 'update.php?action=install-plugin&plugin=' . urlencode( $slug );

	$plugin = learn_press_plugin_basename_from_slug( $slug );
	$return = array();
	if ( !learn_press_is_plugin_install( $plugin ) ) {

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
	if ( !empty( $_REQUEST['learnpress'] ) && $_REQUEST['learnpress'] == 'active' ) {
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

add_filter( 'upgrader_post_install', 'learn_press_upgrader_post_install', 10, 3 );

function learn_press_require_plugins_api() {
	if ( !function_exists( 'plugins_api' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	}
}

function learn_press_add_on_tab_description( $description ) {
	?>
	<p class="description"><?php echo $description; ?></p>
	<?php
}

function learn_press_add_ons_content_tab_installed( $current ) {
	$add_ons = learn_press_get_add_ons();
	learn_press_add_on_tab_description( __( 'All add-ons that you have installed', 'learnpress' ) );
	learn_press_output_add_ons_list( $add_ons, $current );
}

add_action( 'learn_press_add_ons_content_tab_installed', 'learn_press_add_ons_content_tab_installed' );

function learn_press_add_ons_content_tab_more( $current ) {
	$add_ons = learn_press_get_all_add_ons( array( 'transient_key' => 'lp_more_add_ons', 'force' => wp_verify_nonce( learn_press_get_request( 'check' ), 'check_more' ) ) );

	$time        = get_option( '_transient_timeout_lp_more_add_ons' );
	$description = __( 'All add-ons we provide.', 'learnpress' );
	$description .= ' ' . sprintf( __( 'Last checked %s ago', 'learnpress' ), human_time_diff( $time - LP_ADD_ON_TRANSIENT_TIME ) );
	$description .= ' ' . sprintf( __( '<a href="%s">%s</a>' ), admin_url( 'admin.php?page=learn_press_add_ons&tab=more&check=' . wp_create_nonce( 'check_more' ) ), __( 'Check again!', 'learnpress' ) );
	learn_press_add_on_tab_description( $description );
	learn_press_output_add_ons_list( $add_ons, $current );
}

add_action( 'learn_press_add_ons_content_tab_more', 'learn_press_add_ons_content_tab_more' );

function learn_press_add_ons_content_tab_bundle_activate( $current ) {
	$add_ons = learn_press_get_add_ons_from_wp(
		array(
			'transient_key' => 'lp_ba_add_ons',
			'include'       => learn_press_get_bundle_activate_add_ons(),
			'force'         => wp_verify_nonce( learn_press_get_request( 'check' ), 'check_bundle_activate' )
		)
	);

	$time        = get_option( '_transient_timeout_lp_ba_add_ons' );
	$description = __( 'All add-ons that provide basic features for your LMS site.', 'learnpress' );
	$description .= ' ' . sprintf( __( 'Last checked %s ago' ), human_time_diff( $time - LP_ADD_ON_TRANSIENT_TIME ) );
	$description .= ' ' . sprintf( __( '<a href="%s">%s</a>' ), admin_url( 'admin.php?page=learn_press_add_ons&tab=bundle_activate&check=' . wp_create_nonce( 'check_bundle_activate' ) ), __( 'Check again!', 'learnpress' ) );

	learn_press_add_on_tab_description( $description );
	learn_press_output_add_ons_list( $add_ons, $current );
}

add_action( 'learn_press_add_ons_content_tab_bundle_activate', 'learn_press_add_ons_content_tab_bundle_activate' );

function learn_press_get_add_on_action_link( $plugin, $file ) {
	$action_links = array();
	if ( ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) ) {
		$name = '';

		if ( !empty( $plugin['source'] ) && $plugin['source'] == 'wp' ) {
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

function learn_press_output_add_ons_list( $add_ons, $tab = '' ) {
	if ( !is_array( $add_ons ) || sizeof( $add_ons ) == 0 ) {
		printf( '<h3>%s</h3>', __( 'No add-on found', 'learnpress' ) );
		return false;
	}
	echo '<ul class="learn-press-add-ons widefat ' . $tab . '">';
	foreach ( $add_ons as $file => $add_on ) {

		$action_links = learn_press_get_add_on_action_link( $add_on, $file );
		/*if ( is_plugin_active( $file ) ) {
			$action_links[] = '<input data-state="enabled" type="checkbox" class="lpr-fancy-checkbox" checked="checked" data-plugin="' . $file . '" data-url="' . wp_nonce_url( "plugins.php?action=deactivate&plugin={$file}", 'deactivate-plugin_' . $file ) . '" />';
		} else {
			$action_links[] = '<input data-state="disabled" type="checkbox" class="lpr-fancy-checkbox" data-plugin="' . $file . '" data-url="' . wp_nonce_url( "plugins.php?action=activate&plugin={$file}", 'activate-plugin_' . $file ) . '" />';
		}*/


		?>
		<li class="plugin-card plugin-card-learnpress" id="learn-press-plugin-<?php echo $add_on['slug']; ?>">
			<div class="plugin-card-top">
				<span class="plugin-icon"><img src="<?php echo learn_press_get_add_on_icon( $add_on['icons'] ); ?>"></span>

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
						<?php wp_star_rating( array( 'rating' => $add_on['rating'], 'type' => 'percent', 'number' => $add_on['num_ratings'] ) ); ?>
						<span class="num-ratings">(<?php echo number_format_i18n( $add_on['num_ratings'] ); ?>)</span>
					</div>
				<?php } ?>
				<?php if ( array_key_exists( 'last_updated', $add_on ) ) { ?>
					<?php
					$date_format            = 'M j, Y @ H:i';
					$last_updated_timestamp = strtotime( $add_on['last_updated'] );
					?>
					<div class="column-updated">
						<strong><?php _e( 'Last Updated:', 'learnpress' ); ?></strong> <span title="<?php echo esc_attr( date_i18n( $date_format, $last_updated_timestamp ) ); ?>">
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
					if ( !empty( $add_on['tested'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['tested'] ) ), $add_on['tested'], '>' ) ) {
						echo '<span class="compatibility-untested">' . __( 'Untested with your version of WordPress', 'learnpress' ) . '</span>';
					} elseif ( !empty( $plugin['requires'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['requires'] ) ), $add_on['requires'], '<' ) ) {
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

function learn_press_add_on_admin_script() {
	wp_enqueue_media();
}

add_action( 'admin_enqueue_scripts', 'learn_press_add_on_admin_script' );

function learn_press_get_premium_add_ons( $addons ) {
	$add                          = end( $addons );
	$addons['this-is-new-add-on'] = $add;
	return $addons;
}
//add_filter( 'learn_press_all_add_ons', 'learn_press_get_premium_add_ons' );