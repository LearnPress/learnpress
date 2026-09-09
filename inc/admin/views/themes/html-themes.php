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
		<div class="lp-themes-toolbar">
			<div class="lp-themes-filter">

				<button
					type="button"
					class="lp-themes-filter__item active"
					data-category="all"
					aria-pressed="true"
				>
					<?php esc_html_e( 'All Themes', 'learnpress' ); ?> <span class="lp-themes-filter__count"></span>
				</button>

				<button
					type="button"
					class="lp-themes-filter__item"
					data-category="paid"
					aria-pressed="false"
				>
					<?php esc_html_e( 'Paid Themes', 'learnpress' ); ?> <span class="lp-themes-filter__count"></span>
				</button>

				<button
					type="button"
					class="lp-themes-filter__item"
					data-category="free"
					aria-pressed="false"
				>
					<?php esc_html_e( 'Free Themes', 'learnpress' ); ?> <span class="lp-themes-filter__count"></span>
				</button>

			</div>
			<label class="lp-themes-search">
				<span class="screen-reader-text"><?php esc_html_e( 'Search themes', 'learnpress' ); ?></span>
				<span class="lp-themes-search__icon lp-icon-search" aria-hidden="true"></span>
				<input type="search" class="lp-themes-search__input" placeholder="<?php esc_attr_e( 'Search themes…', 'learnpress' ); ?>" />
			</label>
		</div>

		<p class="lp-themes-empty" role="status" hidden><?php esc_html_e( 'No themes found. Try another search or category.', 'learnpress' ); ?></p>

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
