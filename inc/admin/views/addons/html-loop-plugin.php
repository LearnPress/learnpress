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
		<?php if ( array_key_exists( 'rating', $add_on ) ) { ?>
            <div class="vers column-rating">
				<?php wp_star_rating( array(
					'rating' => $add_on['rating'],
					'type'   => 'percent',
					'number' => $add_on['num_ratings']
				) ); ?>
                <span class="num-ratings">(<?php echo number_format_i18n( $add_on['num_ratings'] ); ?>)</span>
            </div>
		<?php } ?>
		<?php if ( array_key_exists( 'last_updated', $add_on ) ) { ?>
			<?php
			$date_format            = 'M j, Y @ H:i';
			$last_updated_timestamp = strtotime( $add_on['last_updated'] );
			?>
            <div class="column-updated">
                <strong><?php _e( 'Last Updated:', 'learnpress' ); ?></strong> <span
                        title="<?php echo esc_attr( date_i18n( $date_format, $last_updated_timestamp ) ); ?>">
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