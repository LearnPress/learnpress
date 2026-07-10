<?php
/**
 * Template for the statistics report popup body ( SweetAlert2 ).
 *
 * JS ( report-modal module ) injects this into SweetAlert.fire() and fills
 * count/table, wires search + export. Title, close button, overlay and
 * Escape handling come from SweetAlert2 itself.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();
?>
<script type="text/html" id="lp-tmpl-stats-report-modal">
	<div class="lp-stats-report-modal">
		<div class="lp-stats-report-modal__toolbar">
			<input type="search" class="lp-stats-report-modal__search" placeholder="<?php esc_attr_e( 'Search…', 'learnpress' ); ?>">
			<span class="lp-stats-report-modal__count"></span>
			<button type="button" class="lp-stats-report-modal__export button"><?php esc_html_e( 'Export CSV', 'learnpress' ); ?></button>
		</div>
		<div class="lp-stats-report-modal__table"></div>
	</div>
</script>
