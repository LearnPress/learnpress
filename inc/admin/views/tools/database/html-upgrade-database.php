<?php
/**
 * Template tool upgrade database.
 *
 * @template html-upgrade-database
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die();
include_once LP_PLUGIN_PATH . 'inc/updates/learnpress-upgrade-4.php';

$db_upgrade = LP_Upgrade_4::get_instance();
$group_steps = $db_upgrade->group_steps;
?>

<div class="card" id="lp-tool-upgrade-db">
	<h2><?php _e( 'Upgrade Database', 'learnpress' ); ?></h2>
	<p class="tools-button">
		<a class="button lp-btn-upgrade-db"
		   href="javascript:;">
			<?php esc_html_e( 'Upgrade now', 'learnpress' ); ?>
		</a>
		<span class="spinner"></span>
		<?php wp_nonce_field( 'lp-nonce', 'lp-nonce' ); ?>
	</p>
	<div style="max-height: 500px; overflow: auto" class="lp-wrapper-status-upgrade">
		<?php
		foreach ( $group_steps as $group_step ) {
			echo "<div class='lp-group-step'>";
			echo "<h3>" . $group_step->label . "</h3>";
			foreach ( $group_step->steps as $step ) {
				echo '<div class="lp-item-step">';
				echo '<div class="lp-item-step-left"><input type="checkbox" name="lp_steps_upgrade_db[]" value="' . $step->name . '"  /></div>';
				echo '<div class="lp-item-step-right">';
				echo '<label for="">' . $step->label . '</label>';
				echo '<div class="description">' . $step->description . '</div>';
				echo '<span class="progress-bar"></span>';
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
		<div class="lp-group-steps-done">Upgrade Done!</div>
	</div>
	<div class="terms-update">
		<h2>Terms of Service update</h2>
		<div>
			<p>Content policy here</p>
			<input type="checkbox" name="agree-term">
			<span>
				<?php esc_html_e( 'I accept the new Terms of Service', 'learnpress' ) ?>
			</span>
		</div>
	</div>
</div>
