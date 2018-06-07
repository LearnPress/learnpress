<?php
/**
 * Admin View: Displaying loop single related theme.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit();
?>

<li class="plugin-card plugin-card-learnpress" id="learn-press-theme-<?php echo $theme['id']; ?>">
    <div class="plugin-card-top">
        <div class="image-thumbnail">
            <a href="<?php echo esc_url( $theme['url'] ); ?>">
                <img src="<?php echo esc_url( $theme['previews']['landscape_preview']['landscape_url'] ); ?>"
                     alt="<?php echo esc_attr( $theme['name'] ); ?>">
            </a>
        </div>

        <div class="theme-content">
            <h2 class="theme-title">
                <a class="item-title" href="<?php echo esc_url( $theme['url'] ); ?>">
					<?php echo wp_kses_post( $theme['name'] ); ?>
                </a>
            </h2>
            <div class="theme-detail">
                <div class="theme-price">
					<?php echo $theme['price_cents'] / 100 . __( '$', 'learnpress' ); ?>
                </div>
                <div class="number-sale">
					<?php echo $theme['number_of_sales'] . __( ' sales', 'learnpress' ); ?>
                </div>
            </div>

            <div class="theme-description">
				<?php
				$description = preg_replace( '~[\r\n]+~', '', $theme['description'] );
				$description = preg_replace( '~\s+~', ' ', $description );
				//$description = preg_replace( "/<(.*?)>/", '', $description );
				echo wp_kses_post( $description );
				?>
            </div>
            <div class="theme-footer">
				<?php
				$demo          = $theme['attributes'][4];
				$demo['value'] = add_query_arg( $ref, $demo['value'] );
				?>
                <a class="button button-primary"
                   href="<?php echo esc_url( $theme['url'] ); ?>"><?php echo __( 'Get it now', 'learnpress' ) ?></a>
                <a class="button"
                   href="<?php echo esc_url( $demo['value'] ); ?>"><?php _e( 'View Demo', 'learnpress' ); ?></a>
                <div class="theme-rating">
                            <span class="">
                                <?php wp_star_rating( array(
	                                'rating' => $theme['rating']['rating'],
	                                'type'   => 'rating',
	                                'number' => $theme['rating']['count']
                                ) ); ?>
                            </span>
                    <span class="count-rating">(<?php echo $theme['rating']['count']; ?>)</span>
                </div>
            </div>
        </div>
    </div>
</li>