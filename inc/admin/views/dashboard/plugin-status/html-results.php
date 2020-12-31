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
				printf( '<span><strong>%s</strong></span>: %s', __( 'Downloaded', 'learnpress' ),
					number_format( $plugin_data->downloaded ) );
			}
			?>
			<?php printf( '<span><strong>%s</strong></span>: %s', __( 'Active Installed', 'learnpress' ),
				number_format( $plugin_data->active_installs ) ) ?>
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
<p>
	<?php printf( '<strong>%s</strong>: %s', __( 'Published', 'learnpress' ),
		date_i18n( get_option( 'date_format' ), strtotime( $plugin_data->added ) ) ) ?>
</p>
<p>
	<?php printf( '<strong>%s</strong>: %s', __( 'Updated', 'learnpress' ),
		date_i18n( get_option( 'date_format' ), strtotime( $plugin_data->last_updated ) ) ) ?>
</p>
<p>
	<?php printf( '<strong>%s</strong>: %s', __( 'Current Version', 'learnpress' ), $plugin_data->version ) ?>
</p>

<!-- Show list blog posts form Thimpress -->
<div class="list_post_thimpress">
	<h3 class="lp-overview__heading"><?php echo esc_html( 'News &amp; Updates', 'learnpress' ); ?></h3>
	<div class="lp-place-holder">
		<?php learn_press_admin_view( 'placeholder-animation' ); ?>
	</div>
	<div class="show_content_post_thimpress"></div>
	<?php wp_nonce_field( 'lp-get-blog-post-thimpess', 'lp-nonce' ); ?>
	<ul class="lp-footer">
		<li class="lp_blog"><a href="https://thimpress.com/blog/" target="_blank">Blog <span class="screen-reader-text">(opens in a new window)</span><span
					aria-hidden="true" class="dashicons dashicons-external"></span></a></li>
		<li class="lp_help"><a href="https://thimpress.com/ticket-center/" target="_blank">Help <span
					class="screen-reader-text">(opens in a new window)</span><span aria-hidden="true"
																				   class="dashicons dashicons-external"></span></a>
		</li>
	</ul>
</div>


