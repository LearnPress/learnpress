<?php
/**
 * Admin View: Help Center page.
 *
 * @package LearnPress/Admin/Views
 *
 * @var array  $quick_links
 * @var array  $whats_new
 * @var array  $articles
 * @var array  $banner_ad
 * @var string $tick_icon
 */

use LearnPress\TemplateHooks\Admin\AdminHelpCenterDataTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap lp-submenu-page learn-press-help-center">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'LearnPress Help Center', 'learnpress' ); ?></h1>
	<p class="lp-help-center-subtitle">
		<?php esc_html_e( 'Find documentation, tutorials, troubleshooting guides, and support resources for LearnPress.', 'learnpress' ); ?>
	</p>

	<?php if ( $quick_links ) : ?>
		<div class="lp-help-center-grid">
			<?php foreach ( $quick_links as $item ) : ?>
				<div class="lp-help-center-card">
					<span class="lp-help-center-card__icon">
						<?php echo $item['icon_svg'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG file content via LP_WP_Filesystem::get_icon_svg(). ?>
					</span>

					<div class="lp-help-center-card__content">
						<h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $item['description'] ?? '' ); ?></p>
					</div>

					<a class="button lp-help-center-card__button" href="<?php echo esc_url( $item['url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $item['button'] ?? '' ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	echo TemplateAJAX::load_content_via_ajax(
		[
			'id_url' => 'data-help-center',
		],
		[
			'class'  => AdminHelpCenterDataTemplate::class,
			'method' => 'html_data_online',
		]
	)
	?>
</div>
