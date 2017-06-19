<?php
/**
 * Admin view for settings page display in admin under menu Settings -> LearnPress
 *
 * @author  ThimPress
 * @package Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Setting page
 */
function learn_press_settings_page() {

	$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

	$tabs = learn_press_settings_tabs_array();

	if ( !$current_tab && $tabs ) {
		$keys        = array_keys( $tabs );
		$current_tab = reset( $keys );
	}

	// ensure all settings relevant to rewrite rules effect immediately
	flush_rewrite_rules();

	if ( !empty( $_GET['settings-updated'] ) ) : ?>
		<div id="message" class="updated notice is-dismissible">
			<p><?php _e( 'LearnPress settings updated.', 'learnpress' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wrap no-subtabs" id="learn-press-admin-settings">
		<div id="learn-press-updating-message" class="error hide-if-js">
			<p><?php esc_html_e( 'Settings changed. Updating...', 'learnpress' ); ?></p>
		</div>
		<div id="learn-press-updated-message" class="updated hide-if-js">
			<p><?php esc_html_e( 'Settings updated. Redirecting...', 'learnpress' ); ?></p>
		</div>
		<form method="<?php echo esc_attr( apply_filters( 'learn_press_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
			<div id="icon-themes" class="icon32"><br></div>
			<h2 class="nav-tab-wrapper">
				<?php if ( $tabs ) foreach ( $tabs as $tab => $name ) { ?>
					<?php $class = ( $tab == $current_tab ) ? ' nav-tab-active' : ''; ?>
					<a class="nav-tab <?php echo $class; ?>" href="?page=learn-press-settings&tab=<?php echo $tab; ?>"><?php echo $name; ?></a>
				<?php } ?>
				<?php do_action( 'learn_press_settings_tabs' ); ?>
			</h2>
			<?php do_action( 'learn_press_sections_' . $current_tab ); ?>
			<div class="learn-press-settings-wrap">
				<?php do_action( 'learn_press_settings_' . $current_tab ); ?>
				<p>
					<button class="button button-primary"><?php _e( 'Save settings', 'learnpress' ); ?></button>
					<a class="button" href="<?php echo wp_nonce_url( add_query_arg( 'reset', 'yes' ), 'learn-press-reset-settings' ); ?>" id="learn-press-reset-settings" data-text="<?php esc_attr_e( 'Do you want to restore all settings to default?', 'learnpress' ); ?>"><?php _e( 'Reset', 'learnpress' ); ?></a>
				</p>
				<?php wp_nonce_field( 'learn_press_settings', 'learn_press_settings_nonce' ); ?>
			</div>
		</form>
	</div>
	<?php
}

function learn_press_admin_update_settings() {

	$tabs        = learn_press_settings_tabs_array();
	$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

	if ( !$current_tab && $tabs ) {
		$keys        = array_keys( $tabs );
		$current_tab = reset( $keys );
	}

	$class_name = apply_filters( 'learn_press_settings_class_' . $current_tab, 'LP_Settings_' . $tabs[$current_tab] );
	if ( !class_exists( $class_name ) ) {
		$class_file = apply_filters( 'learn_press_settings_file_' . $current_tab, LP()->plugin_path( 'inc/admin/settings/class-lp-settings-' . $current_tab . '.php' ) );
		if ( !file_exists( $class_file ) ) {
			return false;
		}

		include_once $class_file;
		if ( !class_exists( $class_name ) ) {

		}
	}

	if ( learn_press_get_request( 'reset' ) == 'yes' && wp_verify_nonce( learn_press_get_request( '_wpnonce' ), 'learn-press-reset-settings' ) ) {
		global $wpdb;
		$sql = "
			DELETE FROM {$wpdb->options} WHERE option_name LIKE %s
		";
		$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( 'learn_press_' ) . '%' ) );
		wp_redirect( remove_query_arg( array( 'reset', '_wpnonce' ) ) );
		exit();
	}

	if ( !empty( $_POST ) ) {
		//	 Check if our nonce is set.
		if ( !isset( $_POST['learn_press_settings_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( !wp_verify_nonce( $_POST['learn_press_settings_nonce'], 'learn_press_settings' ) ) {
			return;
		}

		do_action( 'learn_press_settings_save_' . $current_tab );

		$section = !empty( $_REQUEST['section'] ) ? '&section=' . $_REQUEST['section'] : '';
		LP_Admin_Notice::add( '<p><strong>' . __( 'Settings saved', 'learnpress' ) . '</strong></p>' );

		wp_redirect( admin_url( 'admin.php?page=learn-press-settings&tab=' . $current_tab . $section . '&settings-updated=true' ) );
		exit();
	}
}