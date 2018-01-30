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
	}

	/**
	 * Hooked to plugins_loaded in highest priority and check if an addon
	 * is not valid with new structure present in LP 3 then remove it
	 * from activated plugins array
	 */
	public function deactivate_old_plugins() {

		$valid_plugins  = wp_get_active_and_valid_plugins();
		$active_plugins = get_option( 'active_plugins' );
		$invalid        = true;

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
				$invalid = false;
			}
		}

		if ( !$invalid ) {
			// Re-update
			update_option( 'active_plugins', $active_plugins );

			// Redirect to ensure no addons is invalid loaded
			wp_redirect( add_query_arg( '', '' ) );
			exit();
		}
	}
}

new LP_Backward_Addons();