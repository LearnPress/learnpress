<?php
/**
 * Template for displaying a plugin info in loop
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit();

$action_links = LP_Plugins_Helper::get_add_on_action_link( $add_on, $file );
?>
<li class="plugin-card" id="learn-press-plugin-<?php echo $add_on['slug']; ?>">
    <div class="plugin-card-top">
                <span class="plugin-icon"><img
                            src="<?php echo LP_Plugins_Helper::get_add_on_icon( $add_on['icons'] ); ?>"></span>

        <div class="name column-name">
            <h3 class="item-title"><?php echo $add_on['name']; ?></h3>
        </div>
        <div class="action-links">
			<?php
			if ( $action_links ) {
				echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
			}
			?>
        </div>
        <div class="desc column-description">
            <p><?php echo strip_tags( $add_on['short_description'] ); ?></p>
            <p class="authors"><?php printf( __( '<cite>By %s</cite>', 'learnpress' ), $add_on['author'] ); ?></p>
        </div>
    </div>
    <div class="plugin-card-bottom">
        <div class="plugin-version">
			<?php echo sprintf( __( 'Version: %s', 'learnpress' ), $add_on['version'] ) ?>
        </div>
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