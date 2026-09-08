<?php
/**
 * Admin View: Themes page.
 *
 * @package LearnPress/Admin/Views
 */

use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\AdminThemesDataTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit;

ob_start();
?>

	<div class="learn-press-themes">

		<p class="lp-themes-subtitle">
			<?php
			esc_html_e(
				'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.',
				'learnpress'
			);
			?>
		</p>

		<div class="lp-themes-toolbar">
			<div class="lp-themes-filter">

				<button
					type="button"
					class="lp-themes-filter__item active"
					data-category="all"
					aria-pressed="true"
				>
					<?php esc_html_e( 'All Themes', 'learnpress' ); ?>
				</button>

				<button
					type="button"
					class="lp-themes-filter__item"
					data-category="paid"
					aria-pressed="false"
				>
					<?php esc_html_e( 'Paid Themes', 'learnpress' ); ?>
				</button>

				<button
					type="button"
					class="lp-themes-filter__item"
					data-category="free"
					aria-pressed="false"
				>
					<?php esc_html_e( 'Free Themes', 'learnpress' ); ?>
				</button>

			</div>
		</div>

		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo TemplateAJAX::load_content_via_ajax(
			array(
				'id_url' => 'data-themes',
			),
			array(
				'class'  => AdminThemesDataTemplate::class,
				'method' => 'html_data_online',
			)
		);
		?>

	</div>

<?php

$content = ob_get_clean();
echo AdminTemplate::html_on_wp_admin_screen(
	array(
		'content' => $content,
		'title'   => __( 'Recommended Themes for LearnPress', 'learnpress' ),
		'id'      => 'learn-press-themes',
	)
);
