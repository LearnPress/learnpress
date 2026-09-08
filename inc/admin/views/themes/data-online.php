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

					<?php if ( ! empty( $theme['image'] ) ) : ?>

						<img
							src="<?php echo esc_url( $theme['image'] ); ?>"
							alt="<?php echo esc_attr( $theme['name'] ?? '' ); ?>"
							loading="lazy"
						/>

					<?php endif; ?>

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

								<span class="lp-theme-card__star" aria-hidden="true">
									<svg
										width="16"
										height="16"
										viewBox="0 0 16 16"
										fill="none"
										xmlns="http://www.w3.org/2000/svg"
									>
										<path
											d="M3.46411 15.2732L4.66733 10.1178L0.666626 6.65174L5.93653 6.19568L8.00044 1.33331L10.0644 6.19471L15.3333 6.65077L11.3336 10.1168L12.5368 15.2722L8.00044 12.5359L3.46411 15.2732Z"
											fill="#FFB608"
										/>
									</svg>
								</span>

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

								<span class="lp-theme-card__cart" aria-hidden="true">
									<svg
										xmlns="http://www.w3.org/2000/svg"
										width="16"
										height="16"
										viewBox="0 0 16 16"
										fill="none"
									>
										<path
											fill-rule="evenodd"
											clip-rule="evenodd"
											d="M4.66663 14C4.66663 13.2636 5.26358 12.6667 5.99996 12.6667C6.73634 12.6667 7.33329 13.2636 7.33329 14C7.33329 14.7364 6.73634 15.3334 5.99996 15.3334C5.26358 15.3334 4.66663 14.7364 4.66663 14Z"
											fill="#FFB608"
										/>
										<path
											fill-rule="evenodd"
											clip-rule="evenodd"
											d="M12 14C12 13.2636 12.597 12.6667 13.3333 12.6667C14.0697 12.6667 14.6667 13.2636 14.6667 14C14.6667 14.7364 14.0697 15.3334 13.3333 15.3334C12.597 15.3334 12 14.7364 12 14Z"
											fill="#FFB608"
										/>
										<path
											fill-rule="evenodd"
											clip-rule="evenodd"
											d="M0 0.666667C0 0.298477 0.298477 0 0.666667 0H3.33333C3.65108 0 3.92467 0.224257 3.98704 0.535829L4.54695 3.33333H15.3333C15.532 3.33333 15.7203 3.42195 15.847 3.57504C15.9736 3.72812 16.0254 3.92972 15.9882 4.12489L14.9206 9.72322C14.8291 10.1836 14.5787 10.5972 14.213 10.8915C13.8492 11.1844 13.3944 11.3406 12.9276 11.3333H6.4591C5.99225 11.3406 5.53747 11.1844 5.17365 10.8915C4.80817 10.5973 4.55776 10.1839 4.46622 9.72378L3.35253 4.15947C3.34801 4.14106 3.34425 4.12236 3.3413 4.10338L2.78688 1.33333H0.666667C0.298477 1.33333 0 1.03486 0 0.666667ZM4.81382 4.66667L5.77389 9.46346C5.80437 9.61692 5.88786 9.75478 6.00974 9.85289C6.13162 9.951 6.28413 10.0031 6.44056 10.0001L6.45333 10H12.9333L12.9461 10.0001C13.1025 10.0031 13.255 9.951 13.3769 9.85289C13.4982 9.75523 13.5815 9.6182 13.6123 9.4656L14.5275 4.66667H4.81382Z"
											fill="#FFB608"
										/>
									</svg>
								</span>

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