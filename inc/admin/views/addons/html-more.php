<?php
/**
 * Template for displaying all LearnPress's add-ons available but haven't installed.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit();

$wp_plugins = LP_Plugins_Helper::get_plugins( 'free' );
$tp_plugins = LP_Plugins_Helper::get_plugins( 'premium' );

if ( ! $wp_plugins && ! $tp_plugins ) {
	_e( 'There is no available add-ons.', 'learnpress' );

	return;
}

if ( $wp_plugins ) {
	?>
    <h2><?php printf( __( 'Free add-ons (<span>%d</span>)', 'learnpress' ), sizeof( $wp_plugins ) ); ?></h2>
    <ul class="addons-browse widefat">
		<?php
		foreach ( $wp_plugins as $file => $add_on ) {
			include learn_press_get_admin_view( 'addons/html-loop-plugin' );
		} ?>
    </ul>
	<?php
}


if ( $tp_plugins ) {
	?>
    <h2><?php printf( __( 'Premium add-ons (<span>%d</span>)', 'learnpress' ), sizeof( $tp_plugins ) ); ?></h2>
    <ul class="addons-browse widefat premium">
		<?php
		foreach ( $tp_plugins as $file => $add_on ) {
			$action_links = learn_press_get_add_on_action_link( $add_on, $file );
			?>
            <li class="plugin-card" id="learn-press-plugin-<?php echo $add_on['slug']; ?>">
                <div class="plugin-card-top">
                    <a href="<?php echo esc_url( $add_on['permarklink'] ); ?>">
                        <span class="plugin-icon"><img src="<?php echo esc_url( $add_on['icons'] ); ?>"></span>
                    </a>

                    <div class="name column-name">
                        <h3><?php echo $add_on['name']; ?></h3>
                    </div>
                    <div class="action-links">
                        <ul class="plugin-action-buttons">
                            <li>
                                <a class="button"
                                   href="<?php echo esc_url( $add_on['permarklink'] ); ?>"><?php echo __( 'Buy Now', 'learnpress' ) ?></a>
                            </li>
                            <li>
                            <span class="price">
                                <?php
                                if ( ! empty( $add_on['sale'] ) && absint( $add_on['regular_price'] ) != 0 ) {
	                                ?>
                                    <del>
                                        <span class="amount">
                                            <span class="currencySymbol">$</span><?php echo esc_html( $add_on['regular_price'] ); ?>
                                        </span>
                                    </del>
	                                <?php
                                }
                                ?>
                                <ins>
                                    <span class="amount">
                                        <span class="currencySymbol">$</span><?php echo esc_html( $add_on['price'] ); ?>
                                    </span>
                                </ins>

                            </span>
                            </li>
                        </ul>
                    </div>
                    <div class="desc column-description">
                        <p><?php echo $add_on['short_description']; ?></p>

                        <p class="authors"><?php printf( __( '<cite>By %s</cite>', 'learnpress' ), $add_on['author'] ); ?></p>
                    </div>
                </div>
            </li>
		<?php } ?>
    </ul>
	<?php
}

