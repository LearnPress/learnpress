<?php
/**
 * Template for the statistics report popup body ( SweetAlert2 ).
 *
 * The table itself is server-rendered by AdminStatisticsReportTable through
 * TemplateAJAX: the JS modal injects this body, sets the .lp-target args
 * ( report + current filters + search ) and triggers the load. Pagination is
 * handled by loadAJAX.js ( .page-numbers ); search + CSV export are wired by
 * the report-modal JS module.
 *
 * @since 4.4.2
 * @version 2.0.0
 */

use LearnPress\TemplateHooks\Admin\AdminStatisticsReportTable;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit();

$lp_report_args = array(
	'id_url'                  => 'lp-stats-report',
	'report'                  => '',
	'paged'                   => 1,
	'search'                  => '',
	'enableScrollToView'      => false,
	'enableUpdateParamsUrl'   => false,
	'html_no_load_ajax_first' => sprintf(
		'<div class="lp-stats-report-modal__loading">%s</div>',
		esc_html__( 'Loading…', 'learnpress' )
	),
);

$lp_report_callback = array(
	'class'  => AdminStatisticsReportTable::class,
	'method' => 'render_report_table',
);
?>
<script type="text/html" id="lp-tmpl-stats-report-modal">
	<div class="lp-stats-report-modal">
		<div class="lp-stats-report-modal__toolbar">
			<input type="search" class="lp-stats-report-modal__search" placeholder="<?php esc_attr_e( 'Search…', 'learnpress' ); ?>">
			<button type="button" class="lp-stats-report-modal__export button"><?php esc_html_e( 'Export CSV', 'learnpress' ); ?></button>
		</div>
		<div class="lp-stats-report-modal__body">
			<?php echo TemplateAJAX::load_content_via_ajax( $lp_report_args, $lp_report_callback ); ?>
		</div>
	</div>
</script>
