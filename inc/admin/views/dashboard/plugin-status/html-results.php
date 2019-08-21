<?php
/**
 * Template for displaying LP information.
 */
defined( 'ABSPATH' ) || exit();
if ( ! isset( $plugin_data ) || is_wp_error( $plugin_data ) ) {
	return;
}
?>
<div class="rss-widget">
    <ul>
        <li>
            <a href="<?php echo esc_url( $plugin_data->homepage ) ?>" class="rsswidget"
               target="_blank"><?php echo esc_html( $plugin_data->name ) ?></a>
        </li>
        <li>
			<?php
			if ( ! empty( $plugin_data->downloaded ) ) {
				printf( '<span><strong>%s</strong></span>: %s', __( 'Downloaded', 'learnpress' ), number_format( $plugin_data->downloaded ) );
			}
			?>
			<?php printf( '<span><strong>%s</strong></span>: %s', __( 'Active Installed', 'learnpress' ), number_format( $plugin_data->active_installs ) ) ?>
        </li>
    </ul>
</div>
<div class="rss-widget">
    <ul>
        <li>
            <div class="rssSummary">
				<?php echo esc_html( $plugin_data->short_description ) ?>
            </div>
        </li>
    </ul>
</div>
<p><?php printf( '<strong>%s</strong>: %s', __( 'Published', 'learnpress' ), date_i18n( get_option( 'date_format' ), strtotime( $plugin_data->added ) ) ) ?></p>
<p><?php printf( '<strong>%s</strong>: %s', __( 'Updated', 'learnpress' ), date_i18n( get_option( 'date_format' ), strtotime( $plugin_data->last_updated ) ) ) ?></p>
<p><?php printf( '<strong>%s</strong>: %s', __( 'Current Version', 'learnpress' ), $plugin_data->version ) ?></p>
