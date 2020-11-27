<?php
/**
 * Admin View: Displaying loop single plugin.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit();

$action_links = LP_Plugins_Helper::get_add_on_action_link( $add_on, $file );
?>

<li class="plugin-card" id="learn-press-plugin-<?php echo esc_attr( $add_on['slug'] ); ?>">
	<div class="plugin-card-top">
			<span class="plugin-icon">
				<?php if ( ! is_array( $add_on['icons'] ) && $add_on['icons'] ) : ?>
					<a href="<?php echo esc_url( $add_on['permarklink'] ); ?>">
						<img src="<?php echo esc_url( $add_on['icons'] ); ?>">
					</a>
				<?php else : ?>
					<img src="<?php echo LP_Plugins_Helper::get_add_on_icon( $add_on['icons'] ); ?>">
				<?php endif; ?>
			</span>

		<div class="name column-name">
			<h3 class="item-title"><?php echo esc_html( $add_on['name'] ); ?></h3>
		</div>

		<div class="action-links">
			<?php
			if ( $action_links ) {
				echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
			}
			?>
		</div>
		<?php $short_desc = preg_replace( '!\s+!', ' ', trim( strip_tags( $add_on['short_description'] ) ) ); ?>
		<div class="desc column-description" title="<?php echo esc_attr( $short_desc ); ?>">
			<p><?php echo strip_tags( $short_desc ); ?></p>
			<p class="authors"><?php printf( '<cite>By %s</cite>', 'learnpress', $add_on['author'] ); ?></p>
		</div>
	</div>

	<div class="plugin-card-bottom">
		<div class="plugin-version">
			<?php echo esc_html__( 'Version: ', 'learnpress' ) . ( isset( $add_on['version'] ) ? $add_on['version'] : '2.0' ); ?>
		</div>
		<div class="column-compatibility">
			<?php
			if ( ! empty( $add_on['tested'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['tested'] ) ), $add_on['tested'], '>' ) ) {
				echo '<span class="compatibility-untested">' . esc_html__( 'Untested with your version of WordPress', 'learnpress' ) . '</span>';
			} elseif ( ! empty( $plugin['requires'] ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $add_on['requires'] ) ), $add_on['requires'], '<' ) ) {
				echo '<span class="compatibility-incompatible">' . wp_kses( __( '<strong>Incompatible</strong> with your version of WordPress', 'learnpress' ), array( 'strong' => array() ) ) . '</span>';
			} else {
				echo '<span class="compatibility-compatible">' . wp_kses( __( '<strong>Compatible</strong> with your version of WordPress', 'learnpress' ), array( 'strong' => array() ) ) . '</span>';
			}
			?>
		</div>
	</div>
</li>
