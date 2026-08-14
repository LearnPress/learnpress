<?php
/**
 * Data online for help center
 */
?>

<?php if ( ! empty( $whats_new ) ) : ?>
	<div class="lp-help-center-whats-new">
		<div class="lp-help-center-whats-new__main">
			<?php if ( ! empty( $whats_new['badge'] ) ) : ?>
				<span class="lp-help-center-whats-new__badge"><?php echo esc_html( $whats_new['badge'] ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $whats_new['title'] ) ) : ?>
				<h2><?php echo esc_html( $whats_new['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $whats_new['description'] ) ) : ?>
				<p><?php echo esc_html( $whats_new['description'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $whats_new['cta_text'] ) ) : ?>
				<a class="button button-primary lp-help-center-whats-new__cta" href="<?php echo esc_url( $whats_new['cta_url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $whats_new['cta_text'] ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $whats_new['features'] ) ) : ?>
			<ul class="lp-help-center-whats-new__features">
				<?php foreach ( $whats_new['features'] as $feature ) : ?>
					<li>
						<span class="lp-help-center-whats-new__tick">
							<?php echo $tick_icon ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG file content via LP_WP_Filesystem::get_icon_svg(). ?>
						</span>
						<?php echo esc_html( $feature ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="lp-help-center-bottom">
	<?php if ( ! empty( $articles['items'] ) ) : ?>
		<div class="lp-help-center-articles">
			<div class="lp-help-center-articles__header">
				<h2><?php echo esc_html( $articles['title'] ?? __( 'Latest LearnPress Articles', 'learnpress' ) ); ?></h2>
				<?php if ( ! empty( $articles['more_url'] ) ) : ?>
					<a href="<?php echo esc_url( $articles['more_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $articles['more_text'] ?? __( 'More', 'learnpress' ) ); ?>
					</a>
				<?php endif; ?>
			</div>

			<ul class="lp-help-center-articles__list">
				<?php foreach ( $articles['items'] as $article ) : ?>
					<li>
						<a href="<?php echo esc_url( $article['url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="lp-help-center-articles__thumbnail">
								<?php if ( ! empty( $article['image'] ) ) : ?>
									<img src="<?php echo esc_url( $article['image'] ); ?>" alt="<?php echo esc_attr( $article['title'] ?? '' ); ?>" />
								<?php endif; ?>
							</span>

							<span class="lp-help-center-articles__text">
								<strong><?php echo esc_html( $article['title'] ?? '' ); ?></strong>
								<span><?php echo esc_html( $article['subtitle'] ?? '' ); ?></span>
							</span>

							<span class="dashicons dashicons-arrow-right-alt2"></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $banner_ad ) ) : ?>
	<div class="lp-help-center-banner-ad">
		<?php if ( ! empty( $banner_ad['image'] ) && ! empty( $banner_ad['url'] ) ) : ?>
			<a href="<?php echo esc_url( $banner_ad['url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( $banner_ad['image'] ); ?>" alt="<?php esc_attr_e( 'LearnPress', 'learnpress' ); ?>" />
			</a>
		<?php elseif ( ! empty( $banner_ad['image'] ) ) : ?>
			<img src="<?php echo esc_url( $banner_ad['image'] ); ?>" alt="<?php esc_attr_e( 'LearnPress', 'learnpress' ); ?>" />
		<?php else : ?>
			<div class="lp-help-center-banner-ad__placeholder">
				<span class="dashicons dashicons-format-image"></span>
				<p><?php esc_html_e( 'No banner image set', 'learnpress' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>

