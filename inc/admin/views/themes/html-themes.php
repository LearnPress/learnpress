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

?>
<script>
(function() {
	'use strict';

	document.addEventListener( 'click', function( event ) {
		if ( ! ( event.target instanceof Element ) ) {
			return;
		}

		const filter = event.target.closest( '.lp-themes-filter__item' );

		if ( ! filter ) {
			return;
		}

		const container = filter.closest( '.learn-press-themes' );

		if ( ! container ) {
			return;
		}

		event.preventDefault();

		const category = (
			filter.getAttribute( 'data-category' ) || 'all'
		).toLowerCase();

		const filters = container.querySelectorAll(
			'.lp-themes-filter__item'
		);

		const cards = container.querySelectorAll(
			'.lp-theme-card'
		);

		filters.forEach( function( item ) {
			const isActive = item === filter;

			item.classList.toggle( 'active', isActive );
			item.setAttribute(
				'aria-pressed',
				isActive ? 'true' : 'false'
			);
		} );

		cards.forEach( function( card ) {
			const cardCategory = (
				card.getAttribute( 'data-category' ) || ''
			).toLowerCase();

			card.hidden =
				'all' !== category &&
				category !== cardCategory;
		} );
	} );
})();
</script>
<?php ob_start(); ?>

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