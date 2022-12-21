<?php
/**
 * Template show list addons of LearnPress
 *
 * @version 1.0.0
 * @since 4.2.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $addons ) ) {
	return;
}

include_once ABSPATH . 'wp-admin/includes/plugin.php';

$total_addon_free      = 0;
$total_addon_paid      = 0;
$total_addon_installed = 0;
$total_addon_activated = 0;
$plugins_installed     = get_plugins();
$plugins_activated     = get_option( 'active_plugins' );
?>
<div id="lp-addons">
	<?php
	foreach ( $addons as $slug => $addon ) {
		$is_installed    = false;
		$is_activated    = false;
		$addon_base      = "$slug/$slug.php";
		$version_latest  = $addon->version;
		$version_current = 0;

		if ( 0 == $addon->is_free ) {
			$total_addon_free ++;
		} else {
			$total_addon_paid ++;
		}

		if ( isset( $plugins_installed[ $addon_base ] ) ) {
			$is_installed    = true;
			$version_current = $plugins_installed[ $addon_base ]['Version'];
			$total_addon_installed ++;
		}

		if ( in_array( $addon_base, $plugins_activated ) ) {
			$is_activated = true;
			$total_addon_activated ++;
		}
		?>
		<div class="lp-addon-item">
			<div class="lp-addon-item__content">
				<img src="<?php echo $addon->image; ?>" alt="<?php echo $addon->name; ?>"/>
				<h3>
					<a href="<?php echo $addon->link; ?>" target="_blank" rel="noopener">
						<?php echo $addon->name; ?>
					</a>
				</h3>
				<?php
				if ( $version_current ) {
					echo "<h4>Version $version_current</h4>";
				} else {
					echo "<h4>Version $version_latest</h4>";
				}
				echo "<h4>Require LP $addon->require_lp</h4>";
				?>
				<p title="<?php echo $addon->description; ?>"><?php echo $addon->description; ?></p>
			</div>
			<div class="lp-addon-item__actions">
				<div class="lp-addon-item__actions__left">
					<?php
					if ( $is_installed ) {
						if ( $is_activated ) {
							echo '<button>Settings</button>';
						}
						if ( version_compare( $version_current, $version_latest, '<' ) ) {
							echo '<button>Update</button>';
						}
					} else {
						echo '<button>Install</button>';
					}
					?>
				</div>
				<div class="lp-addon-item__actions__right">
					<?php
					if ( $is_installed ) {
						if ( $is_activated ) {
							?>
							<input class="screen-reader-text thim-toggle-switch-input"
								   name="thim_switch_thim_content_course_border"
								   checked
								   id="<?php echo $slug; ?>"
								   type="checkbox" value="true">
							<label class="thim-toggle-switch-label" for="<?php echo $slug; ?>">
								<span class="toggle-on"></span>
								<span class="toggle-off"></span>
							</label>
							<?php
						} else {
							?>
							<input class="screen-reader-text thim-toggle-switch-input"
								   name="thim_switch_thim_content_course_border"
								   id="<?php echo $slug; ?>"
								   type="checkbox" value="false">
							<label class="thim-toggle-switch-label" for="<?php echo $slug; ?>">
								<span class="toggle-on"></span>
								<span class="toggle-off"></span>
							</label>
							<?php
						}
					} else {

					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
	?>
</div>
