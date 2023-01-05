<?php
/**
 * Template show list addons of LearnPress
 *
 * @version 1.0.0
 * @since 4.2.1
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $addons ) ) {
	return;
}

include_once ABSPATH . 'wp-admin/includes/plugin.php';

$total_addon_free          = 0;
$total_addon_paid          = 0;
$total_addon_installed     = 0;
$total_addon_not_installed = 0;
$total_addon_activated     = 0;
$total_addon_update        = 0;
$plugins_installed         = get_plugins();
$plugins_activated         = get_option( 'active_plugins', '' );
$active_tab                = ! empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'all';
?>
<div class="lp-addons-wrapper">
	<div id="lp-addons">
		<?php
		foreach ( $addons as $slug => $addon ) :
			$addon->slug     = $slug;
			$is_installed    = false;
			$is_activated    = false;
			$is_updated      = false;
			$is_free         = $addon->is_free;
			$addon_base      = $addon->basename;
			$version_latest  = $addon->version;
			$version_current = 0;
			$classes_status  = [];
			// Addon is free or paid.
			if ( 1 == $addon->is_free ) {
				$total_addon_free ++;
			} else {
				$total_addon_paid ++;
			}
			// Addon is installed
			if ( isset( $plugins_installed[ $addon_base ] ) ) {
				$is_installed     = true;
				$classes_status[] = 'installed';
				$version_current  = $plugins_installed[ $addon_base ]['Version'];
				$total_addon_installed ++;
			} else {
				$classes_status[] = 'not_installed';
				$total_addon_not_installed ++;
			}
			// Addon is activated
			if ( in_array( $addon_base, $plugins_activated ) ) {
				$is_activated     = true;
				$classes_status[] = 'activated';
				$total_addon_activated ++;
			}
			// Addon is has update
			if ( $is_installed && version_compare( $version_current, $version_latest, '<' ) ) {
				$total_addon_update ++;
				$classes_status[] = 'update';
				$is_updated       = true;
			}
			// Addon is paid on Thimpress
			if ( 0 == $is_free ) {
				$classes_status[] = 'purchase';
			} else { // Addon is free
				$classes_status[] = 'free';
			}
			// Show addons of tab.
			if ( ! in_array( $active_tab, $classes_status ) && $active_tab != 'all' ) {
				$classes_status[] = 'hide';
			}
			?>
			<div class="lp-addon-item <?php echo implode( ' ', $classes_status ); ?>"
				data-slug="<?php echo $slug; ?>">
				<div class="lp-addon-item__content">
					<img src="<?php echo $addon->image; ?>" alt="<?php echo $addon->name; ?>"/>
					<h3>
						<a href="<?php echo $addon->link; ?>" target="_blank" rel="noopener">
							<?php echo $addon->name; ?>
						</a>
					</h3>
					<?php
					if ( $version_current ) {
						echo "<h4>Version <span class='addon-version-current'>$version_current</span></h4>";
					} else {
						echo "<h4>Version $version_latest</h4>";
					}
					echo sprintf(
						'<p>%s On %s</p>',
						$addon->is_free ? __( 'Free', 'learnpress' ) : __( 'Paid', 'learnpress' ),
						$addon->is_org ? __( 'WP.org', 'learnpress' ) : __( 'Thimpress', 'learnpress' )
					);
					?>
					<p title="<?php echo $addon->description; ?>"><?php echo $addon->description; ?></p>
				</div>
				<div class="lp-addon-item__actions">
					<div class="lp-addon-item__actions__left">
							<button class="btn-addon-action" data-action="setting">Settings</button>
							<button class="btn-addon-action" data-action="update"
							title="<?php echo sprintf( '%s %s require LP version %s', $addon->name, $version_latest, $addon->require_lp ); ?>">
								<span class="dashicons dashicons-update"></span><span class="text">Update</span>
							</button>
							<button class="btn-addon-action" data-action="install">
								<span class="dashicons dashicons-update"></span><span class="text">Install</span>
							</button>
							<button class="btn-addon-action" data-action="purchase">Install</button>
					</div>
					<div class="lp-addon-item__actions__right">
						<button class="btn-addon-action" data-action="deactivate">
							<span class="dashicons dashicons-update"></span><span class="text">Deactivate</span>
						</button>
						<button class="btn-addon-action" data-action="activate">
							<span class="dashicons dashicons-update"></span><span class="text">Activate</span>
						</button>
					</div>
				</div>
				<div class="lp-addon-item__purchase">
					<div class="lp-addon-item__purchase__wrapper">
						<label>
							<input type="text" placeholder="Enter Purchase Code">
						</label>
						<button class="btn-addon-action" data-action="install">
							<span class="dashicons dashicons-update"></span><span class="text">Submit</span>
						</button>
						OR
						<button class="btn-addon-action" data-action="buy" data-link="<?php echo $addon->link; ?>">Buy
							Now
						</button>
						<button class="btn-addon-action" data-action="cancel">Cancel</button>
					</div>
				</div>
			</div>
			<?php
		endforeach;
		?>
	</div>
	<div class="lp-nav-tab-wrapper" style="display: none">
		<?php
		$tabs = array(
			'all'           => sprintf( __( 'All (%d)', 'learnpress' ), count( (array) $addons ) ),
			'installed'     => sprintf( __( 'Installed (%d)', 'learnpress' ), $total_addon_installed ),
			'purchase'      => sprintf( __( 'Paid (%d)', 'learnpress' ), $total_addon_paid ),
			'free'          => sprintf( __( 'Free (%d)', 'learnpress' ), $total_addon_free ),
			'update'        => sprintf( __( 'Update (%d)', 'learnpress' ), $total_addon_update ),
			'not_installed' => sprintf( __( 'Not Installed (%d)', 'learnpress' ), $total_addon_not_installed ),
		);
		foreach ( $tabs as $tab => $name ) {
			?>
			<?php
			$obj_tab = false;

			if ( is_object( $name ) ) {
				$obj_tab = $name;
				$name    = $obj_tab->text;
				$tab     = $obj_tab->id;
			}

			$active_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
			$tab_title    = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $name, $tab );
			?>

			<?php if ( $active_class ) { ?>
				<a class="nav-tab<?php echo esc_attr( $active_class ); ?>"
					data-tab="<?php echo esc_attr( $tab ); ?>" href="#">
					<?php echo esc_html( $tab_title ); ?>
				</a>
			<?php } else { ?>
				<a class="nav-tab"
					data-tab="<?php echo esc_attr( $tab ); ?>"
					href="?page=learn-press-addons&tab=<?php echo esc_attr( $tab ); ?>">
					<?php echo esc_html( $tab_title ); ?>
				</a>
			<?php } ?>
		<?php } ?>
		<div class="lp-search-addons">
			<label>
				<input id="lp-search-addons__input" type="text" placeholder="Search name addon" />
			</label>
		</div>
	</div>
</div>
