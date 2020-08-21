<?php

/**
 * Class LP_Backward_Addons
 *
 * @since 3.0.0
 */
class LP_Backward_Addons {

	/**
	 * LP_Backward_Addons constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'deactivate_old_addons' ), - 100 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_notices() {
		$invalid_plugins = get_transient( 'lp-deactivated-addons' );
		delete_transient( 'lp-deactivated-addons' );

		if ( ! $invalid_plugins ) {
			return;
		}

		$plugin_names = array();

		foreach ( $invalid_plugins as $plugin ) {
			if ( ! file_exists( $plugin['path'] ) ) {
				continue;
			}

			if ( $plugin_data = get_plugin_data( $plugin['path'] ) ) {
				$plugin_names[] = $plugin_data['Name'];
			}
		}
		?>
        <div class="notice notice-warning">
            <p><?php echo sprintf(
					__( 'There are some add-ons had gone outdated and might conflict with <strong>LearnPress</strong> that need to be deactivated. Please upgrade them to the newest version to ensure stability and performance of your site.', 'learnpress' ),
					LEARNPRESS_VERSION
				); ?></p>
            <p><?php echo '<strong>' . join( '</strong>, <strong>', $plugin_names ) . '</strong>'; ?>.</p>
        </div>
		<?php
	}

	/**
	 * Hooked to plugins_loaded in highest priority and check if an addon
	 * is not valid with new structure present in LP 3 then remove it
	 * from activated plugins array
	 */
	public function deactivate_old_addons() {

		$valid_plugins   = wp_get_active_and_valid_plugins();
		$active_plugins  = get_option( 'active_plugins' );
		$invalid_plugins = array();
		$invalid_slug    = false;

		foreach ( $valid_plugins as $file ) {

			// Ensure plugin name is started with learnpress-
			$base_name = plugin_basename( $file );

			if ( strpos( $base_name, 'learnpress-' ) !== 0 ) {
				continue;
			}

			$path = dirname( $file );

			// LP 3 addons usually have a file load.php in inc/incs folder
			if ( file_exists( "$path/inc/load.php" ) || file_exists( "$path/incs/load.php" ) ) {
				continue;
			}

			// Remove addon from activated plugins
			if ( false !== ( $at = array_search( $base_name, $active_plugins ) ) ) {
				unset( $active_plugins[ $at ] );
				$invalid_plugins[] = array( 'slug' => $base_name, 'path' => $file );

				if ( preg_match( '!learnpress-(.*)/learnpress.php!', $base_name ) ) {
					$invalid_slug = $base_name;
					break;
				}
			}
		}

		if ( sizeof( $invalid_plugins ) ) {
			// Re-update
			update_option( 'active_plugins', $active_plugins );

			if ( $invalid_slug ) {
				wp_die(
					sprintf('%s <strong>%s</strong> %s <strong>%s</strong>. %s <a href="%s">Back</a>',
						__( 'LearnPress plugin slug should be', 'learnpress'), 'learnpress/learnpress.php',  __('to make sure it works properly. Currently, it is', 'learnpress'), $invalid_slug, __('Please correct it\'s name and active again', 'learnpress'), admin_url( 'plugins.php' ) )
				);
			}

			set_transient( 'lp-deactivated-addons', $invalid_plugins );

			//die();
			// Redirect to ensure no addons is invalid loaded
			wp_redirect( remove_query_arg( 'activate' ) );
			exit();
		}

	}
}

new LP_Backward_Addons();