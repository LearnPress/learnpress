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

			if ( get_plugin_data( $plugin['path'] ) ) {
				$plugin_data    = get_plugin_data( $plugin['path'] );
				$plugin_names[] = $plugin_data['Name'];
			}
		}
		?>
		<div class="notice notice-warning">
			<p>
			<?php
			echo sprintf(
				__( 'There are some add-ons had gone outdated and might conflict with <strong>LearnPress</strong> that need to be deactivated. Please upgrade them to the newest version to ensure stability and performance of your site.', 'learnpress' ),
				LEARNPRESS_VERSION
			);
			?>
				</p>
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
			if ( false !== ( $at = array_search( $base_name, $active_plugins ) ) ) { // phpcs:ignore
				unset( $active_plugins[ $at ] );
				$invalid_plugins[] = array(
					'slug' => $base_name,
					'path' => $file,
				);

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
					sprintf(
						__( 'LearnPress plugin slug should be <strong>%1$s</strong> to make sure it works properly. Currently, it is <strong>%2$s</strong>. Please correct it\'s name and active again. <a href="%3$s">Back</a>', 'learnpress' ),
						'learnpress/learnpress.php',
						$invalid_slug,
						admin_url( 'plugins.php' )
					)
				);
			}

			set_transient( 'lp-deactivated-addons', $invalid_plugins );

			wp_redirect( remove_query_arg( 'activate' ) );
			exit();
		}
	}
}

new LP_Backward_Addons();
