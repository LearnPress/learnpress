<?php
/**
 * Data online for Themes page.
 *
 * @var array $themes Themes data.
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( ! empty( $themes ) ) : ?>

	<div class="lp-themes-grid">

		<?php foreach ( $themes as $theme ) : ?>

			<div
				class="lp-theme-card<?php echo ! empty( $theme['featured'] ) ? ' lp-theme-card--featured' : ''; ?>"
				data-category="<?php echo esc_attr( strtolower( $theme['category'] ?? '' ) ); ?>"
			>

				<div class="lp-theme-card__thumbnail">

					<?php
					$fallback_image = LP_PLUGIN_URL . 'assets/images/no-image.png';
					$theme_image    = ! empty( $theme['image'] ) ? $theme['image'] : $fallback_image;
					?>

					<img
						src="<?php echo esc_url( $theme_image ); ?>"
						data-fallback-src="<?php echo esc_url( $fallback_image ); ?>"
						alt="<?php echo esc_attr( $theme['name'] ?? $theme['title'] ?? '' ); ?>"
						loading="lazy"
					/>

					<?php if ( ! empty( $theme['badge'] ) ) : ?>

						<span class="lp-theme-card__badge">
							<?php echo esc_html( $theme['badge'] ); ?>
						</span>

					<?php endif; ?>

				</div>

				<div class="lp-theme-card__content">

					<h3 class="lp-theme-card__title">
						<?php echo esc_html( $theme['title'] ?? '' ); ?>
					</h3>

					<p class="lp-theme-card__description">
						<?php echo esc_html( $theme['description'] ?? '' ); ?>
					</p>

					<div class="lp-theme-card__footer">

						<div class="lp-theme-card__price">

							<?php if ( 'Free' === ( $theme['category'] ?? '' ) ) : ?>

								<strong>
									<?php esc_html_e( 'Free', 'learnpress' ); ?>
								</strong>

							<?php else : ?>

								<strong>
									$<?php echo esc_html( number_format_i18n( $theme['price'] ?? 0 ) ); ?>
								</strong>

								<?php if ( ! empty( $theme['old_price'] ) ) : ?>

									<del>
										$<?php echo esc_html( number_format_i18n( $theme['old_price'] ) ); ?>
									</del>

								<?php endif; ?>

							<?php endif; ?>

						</div>

						<div class="lp-theme-card__meta">

							<div class="lp-theme-card__rating">

								<span class="lp-theme-card__star lp-icon-star" aria-hidden="true"></span>

								<span>
									<?php echo esc_html( $theme['rating'] ?? 0 ); ?>
								</span>

								<?php if ( ! empty( $theme['reviews'] ) ) : ?>

									<span>
										(<?php echo esc_html( number_format_i18n( $theme['reviews'] ) ); ?>)
									</span>

								<?php endif; ?>

							</div>

							<div class="lp-theme-card__sold">

								<span class="lp-theme-card__cart lp-icon-shopping-cart" aria-hidden="true"></span>

								<span>
									<?php echo esc_html( number_format_i18n( $theme['sold'] ?? 0 ) ); ?>
								</span>

							</div>

						</div>

					</div>

					<div class="lp-theme-card__actions">

						<a
							class="lp-theme-card__buy"
							href="<?php echo esc_url( $theme['buy_url'] ?? '#' ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php echo esc_html( $theme['buy_text'] ?? __( 'Get Theme Now', 'learnpress' ) ); ?>
						</a>

						<a
							class="lp-theme-card__demo"
							href="<?php echo esc_url( $theme['demo_url'] ?? '#' ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php echo esc_html( $theme['demo_text'] ?? __( 'Demo', 'learnpress' ) ); ?>
						</a>

					</div>

				</div>

			</div>

		<?php endforeach; ?>

	</div>

<?php endif; ?>
