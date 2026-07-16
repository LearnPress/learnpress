<?php
/**
 * Shared statistics filter bar: WC-style date-range dropdown + instructor/category
 * scope + CSV export.
 *
 * The dropdown toggle opens a popover with a Presets/Custom tablist and a
 * "Compare to" radio group. Deep-linked state ( filtertype / date / compare )
 * is restored server-side so the bar is correct before JS boots; everything
 * after that flows through lpStatsState in filter-bar.js. Scope selects are
 * populated by JS from the filter-options endpoint.
 *
 * @since 4.4.2
 * @version 2.0.0
 */

use LearnPress\Statistics\PeriodResolver;

defined( 'ABSPATH' ) || exit();

$lp_stats_filtertype = sanitize_text_field( wp_unslash( $_GET['filtertype'] ?? 'today' ) );
$lp_stats_date       = sanitize_text_field( wp_unslash( $_GET['date'] ?? '' ) );
$lp_stats_compare    = PeriodResolver::sanitize_compare( sanitize_text_field( wp_unslash( $_GET['compare'] ?? '' ) ) );
$lp_stats_range      = PeriodResolver::resolve( $lp_stats_filtertype, $lp_stats_date );
$lp_stats_is_custom  = 'custom' === $lp_stats_range->preset;
$lp_stats_custom     = $lp_stats_is_custom ? explode( '+', (string) $lp_stats_range->time ) : array( '', '' );
?>
<div class="btn-group btn-group-filter lp-statistics-filter-bar">
	<div class="lp-stats-daterange">
		<button type="button" class="lp-stats-daterange__toggle" aria-expanded="false" aria-haspopup="dialog" aria-controls="lp-stats-daterange-panel">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<span class="lp-stats-daterange__label"><?php echo esc_html( $lp_stats_range->label ); ?></span>
			<span class="dashicons dashicons-arrow-down-alt2 lp-stats-daterange__caret" aria-hidden="true"></span>
		</button>

		<div class="lp-stats-daterange__panel" id="lp-stats-daterange-panel" role="dialog" aria-label="<?php esc_attr_e( 'Select a date range', 'learnpress' ); ?>" hidden>
			<div class="lp-stats-daterange__tabs" role="tablist">
				<button type="button" class="lp-stats-daterange__tab<?php echo $lp_stats_is_custom ? '' : ' active'; ?>" data-tab="presets" role="tab" id="lp-stats-daterange-tab-presets" aria-controls="lp-stats-daterange-tabpanel-presets" aria-selected="<?php echo $lp_stats_is_custom ? 'false' : 'true'; ?>">
					<?php esc_html_e( 'Presets', 'learnpress' ); ?>
				</button>
				<button type="button" class="lp-stats-daterange__tab<?php echo $lp_stats_is_custom ? ' active' : ''; ?>" data-tab="custom" role="tab" id="lp-stats-daterange-tab-custom" aria-controls="lp-stats-daterange-tabpanel-custom" aria-selected="<?php echo $lp_stats_is_custom ? 'true' : 'false'; ?>">
					<?php esc_html_e( 'Custom', 'learnpress' ); ?>
				</button>
			</div>

			<div class="lp-stats-daterange__tabpanel" data-tabpanel="presets" role="tabpanel" id="lp-stats-daterange-tabpanel-presets" aria-labelledby="lp-stats-daterange-tab-presets" <?php echo $lp_stats_is_custom ? 'hidden' : ''; ?>>
				<ul class="lp-stats-daterange__presets">
					<?php foreach ( PeriodResolver::UI_PRESETS as $lp_stats_preset ) : ?>
						<li>
							<label class="lp-stats-daterange__preset">
								<input type="radio" name="lp-stats-preset" value="<?php echo esc_attr( $lp_stats_preset ); ?>" <?php checked( $lp_stats_range->preset, $lp_stats_preset ); ?> />
								<span><?php echo esc_html( PeriodResolver::preset_name( $lp_stats_preset ) ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="lp-stats-daterange__tabpanel" data-tabpanel="custom" role="tabpanel" id="lp-stats-daterange-tabpanel-custom" aria-labelledby="lp-stats-daterange-tab-custom" <?php echo $lp_stats_is_custom ? '' : 'hidden'; ?>>
				<div class="lp-stats-daterange__custom-fields">
					<label>
						<span><?php esc_html_e( 'From', 'learnpress' ); ?></span>
						<input type="date" class="lp-stats-daterange__from" value="<?php echo esc_attr( $lp_stats_custom[0] ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'To', 'learnpress' ); ?></span>
						<input type="date" class="lp-stats-daterange__to" value="<?php echo esc_attr( $lp_stats_custom[1] ?? '' ); ?>" />
					</label>
				</div>
				<button type="button" class="button button-primary lp-stats-daterange__update"><?php esc_html_e( 'Update', 'learnpress' ); ?></button>
			</div>

			<fieldset class="lp-stats-daterange__compare">
				<legend><?php esc_html_e( 'Compare to', 'learnpress' ); ?></legend>
				<label>
					<input type="radio" name="lp-stats-compare" value="previous_period" <?php checked( $lp_stats_compare, 'previous_period' ); ?> />
					<span><?php esc_html_e( 'Previous period', 'learnpress' ); ?></span>
				</label>
				<label>
					<input type="radio" name="lp-stats-compare" value="previous_year" <?php checked( $lp_stats_compare, 'previous_year' ); ?> />
					<span><?php esc_html_e( 'Previous year', 'learnpress' ); ?></span>
				</label>
			</fieldset>
		</div>
	</div>

	<div class="lp-stats-filter-scope">
		<select class="lp-stats-filter-instructor" aria-label="<?php esc_attr_e( 'Filter by instructor', 'learnpress' ); ?>">
			<option value="0"><?php esc_html_e( 'All instructors', 'learnpress' ); ?></option>
		</select>
		<select class="lp-stats-filter-category" aria-label="<?php esc_attr_e( 'Filter by category', 'learnpress' ); ?>">
			<option value="0"><?php esc_html_e( 'All categories', 'learnpress' ); ?></option>
		</select>
		<button type="button" class="lp-stats-export-csv button"><?php esc_html_e( 'Export CSV', 'learnpress' ); ?></button>
	</div>
</div>
