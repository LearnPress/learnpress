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
					__( 'There are some addons too old and maybe crash with <strong>LearnPress</strong> %s have to deactivated. Please upgrade them to newest version to ensure your system work properly. They are listed below:', 'learnpress' ),
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
			}
		}

		if ( sizeof( $invalid_plugins ) ) {
			// Re-update
			update_option( 'active_plugins', $active_plugins );

			set_transient( 'lp-deactivated-addons', $invalid_plugins );

			//die();
			// Redirect to ensure no addons is invalid loaded
			wp_redirect( remove_query_arg( 'activate' ) );
			exit();
		}

	}
}

new LP_Backward_Addons();