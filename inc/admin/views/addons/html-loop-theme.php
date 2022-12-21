<?php
/**
 * Admin View: Displaying loop single related theme.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit();
?>

<li class="plugin-card-learnpress" id="learn-press-theme-<?php echo esc_attr( $theme['id'] ); ?>">
	<div class="plugin-card-top">
		<div class="image-thumbnail">
			<a href="<?php echo esc_url_raw( $theme['url'] ); ?>">
				<img src="<?php echo esc_url_raw( $theme['previews']['landscape_preview']['landscape_url'] ); ?>" alt="<?php echo esc_attr( $theme['name'] ); ?>">
			</a>
		</div>

		<div class="theme-content">
			<h2 class="theme-title">
				<a class="item-title" href="<?php echo esc_url_raw( $theme['url'] ); ?>">
					<?php echo wp_kses_post( $theme['name'] ); ?>
				</a>
			</h2>
			<div class="theme-detail">
				<div class="theme-price">
					<?php echo sprintf( '$%s', $theme['price_cents'] / 100 ); ?>
				</div>
				<div class="number-sale">
					<?php echo sprintf( '%s %s', $theme['number_of_sales'], esc_html__( ' sales', 'learnpress' ) ); ?>
				</div>
			</div>

			<div class="theme-description">
				<?php
				$description = preg_replace( '~[\r\n]+~', '', $theme['description'] );
				$description = preg_replace( '~\s+~', ' ', $description );
				echo wp_kses_post( $description );
				?>
			</div>
			<div class="theme-footer">
				<?php $demo_url = isset( $theme['attributes'][4] ) ? $theme['attributes'][4]['value'] : $theme['url']; ?>
				<a class="button button-primary" href="<?php echo esc_url_raw( $theme['url'] ); ?>"><?php echo esc_html__( 'Get it now', 'learnpress' ); ?></a>
				<a class="button" href="<?php echo esc_url_raw( $demo_url ); ?>"><?php esc_html_e( 'View Demo', 'learnpress' ); ?></a>
				<div class="theme-rating">
					<span>
						<?php
						wp_star_rating(
							array(
								'rating' => $theme['rating']['rating'],
								'type'   => 'rating',
								'number' => $theme['rating']['count'],
							)
						);
						?>
					</span>
					<span class="count-rating">(<?php echo esc_html( $theme['rating']['count'] ); ?>)</span>
				</div>
			</div>
		</div>
	</div>
</li>
