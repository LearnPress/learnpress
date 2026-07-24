/**
 * Line chart renderer wrapping Chart.js — single or dual-axis.
 *
 * Pure renderer: no fetching, no state mutation. Reuses an existing chart
 * instance ( Chart.getChart ) instead of recreating, like the legacy
 * initStatisticChart did.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import Chart from 'chart.js/auto';
import { getStatsConfig, getStatsI18n } from './api.js';

const DEFAULT_COLORS = [ '#2271b1', '#00a32a' ];
const EMPTY_STATE_CLASS = 'lp-stats-chart-empty';

/**
 * Show/hide the empty state next to the canvas.
 *
 * @param {Element} canvas
 * @param {boolean} show
 */
const toggleEmptyState = ( canvas, show ) => {
	const wrapper = canvas.parentElement;
	if ( ! wrapper ) {
		return;
	}

	let elEmpty = wrapper.querySelector( `.${ EMPTY_STATE_CLASS }` );
	if ( show && ! elEmpty ) {
		elEmpty = document.createElement( 'p' );
		elEmpty.className = EMPTY_STATE_CLASS;
		elEmpty.textContent = getStatsI18n( 'noData', 'No data for this period.' );
		wrapper.appendChild( elEmpty );
	}

	if ( elEmpty ) {
		elEmpty.style.display = show ? '' : 'none';
	}
	canvas.style.display = show ? 'none' : 'block';
};

/**
 * One-off locale date formatting. Fine for a handful of dates ( labels, a
 * custom-range caption ); for per-label chart axes build a single formatter
 * and reuse it instead ( see granularityLabelFormatter ).
 *
 * @param {Date}   date
 * @param {Object} options Intl.DateTimeFormat options.
 * @return {string}
 */
export const intlFormat = ( date, options ) =>
	new Intl.DateTimeFormat( undefined, options ).format( date );

/**
 * Do all 'Y-m-d' day labels fall inside a single calendar month?
 * When they cross a month boundary the axis must show the month, otherwise
 * "30, 1, 2" is ambiguous.
 *
 * @param {Array} labels
 * @return {boolean}
 */
const daysWithinOneMonth = ( labels ) => {
	const months = labels.map( ( label ) => String( label ).slice( 0, 7 ) );
	return months.every( ( m ) => m === months[ 0 ] );
};

/**
 * Axis label formatter for a chart payload's `granularity` marker
 * ( PeriodRange->granularity, set server-side by PeriodResolver ):
 *
 * - hour   int 0–23   → "14h"
 * - day    'Y-m-d'    → "Tue 14" ( ≤ 7 points, single month ) / "Jul 14"
 * - month  'mm-YYYY'  → "Jul 26"-style short month + 2-digit year
 *
 * The returned closure captures a single Intl formatter ( built once here, not
 * per label ), so a 90-point chart formats against one instance. Unparsable
 * labels pass through untouched — never throws.
 *
 * @param {string} granularity Marker from the payload.
 * @param {Array}  labels      Full label set ( picks the day format density ).
 * @return {Function|null} ( label ) => string, or null for unknown markers.
 */
export const granularityLabelFormatter = ( granularity, labels = [] ) => {
	switch ( granularity ) {
		case 'hour':
			return ( label ) => `${ label }h`;

		case 'day': {
			// Weekday reads best for a short, single-month range; anything
			// crossing a month shows the month so labels like "Jun 30 / Jul 1"
			// stay unambiguous.
			const options =
				labels.length <= 7 && daysWithinOneMonth( labels )
					? { weekday: 'short', day: 'numeric' }
					: { month: 'short', day: 'numeric' };
			const fmt = new Intl.DateTimeFormat( undefined, options );
			return ( label ) => {
				const date = new Date( `${ label }T00:00:00` );
				return isNaN( date.getTime() ) ? String( label ) : fmt.format( date );
			};
		}

		case 'month': {
			// Labels are 'mm-YYYY'.
			const fmt = new Intl.DateTimeFormat( undefined, { month: 'short', year: '2-digit' } );
			return ( label ) => {
				const parts = String( label ).split( '-' );
				const month = parseInt( parts[ 0 ], 10 );
				if ( 2 === parts.length && month >= 1 && month <= 12 ) {
					return fmt.format( new Date( parseInt( parts[ 1 ], 10 ), month - 1, 1 ) );
				}
				return String( label );
			};
		}

		default:
			// Unknown markers render as-is; Chart.js stringifies them for the axis.
			return null;
	}
};

/**
 * Render (or update) a line chart.
 *
 * @param {string} canvasSelector e.g. '#net-sales-chart-content'.
 * @param {Object} chartData      { labels, datasets: [ { label, data, color, yAxisID } ], xLabel,
 *                                  granularity? — enables the shared axis label formatter }.
 * @param {Object} config         { yCurrency?: boolean (default true when 2 datasets),
 *                                  formatLabel?: ( label, index ) => string — overrides granularity }.
 * @return {Chart|null} Chart instance, or null when canvas missing / no data.
 */
export const renderLineChart = ( canvasSelector, chartData = {}, config = {} ) => {
	const canvas = document.querySelector( canvasSelector );
	if ( ! canvas ) {
		return null;
	}

	const { datasets = [], xLabel = '', granularity = '' } = chartData;
	let { labels = [] } = chartData;
	const hasData =
		datasets.length > 0 &&
		datasets.some( ( dataset ) => ( dataset.data || [] ).length > 0 );

	if ( ! hasData ) {
		const existing = Chart.getChart( canvas );
		if ( existing ) {
			existing.destroy();
		}
		toggleEmptyState( canvas, true );
		return null;
	}

	toggleEmptyState( canvas, false );

	const formatLabel =
		'function' === typeof config.formatLabel
			? config.formatLabel
			: granularityLabelFormatter( granularity, labels );
	if ( formatLabel ) {
		labels = labels.map( ( label, index ) => formatLabel( label, index ) );
	}

	const isDual = datasets.length > 1;
	const yCurrency = config.yCurrency ?? isDual;
	const currencySymbol = getStatsConfig().currencySymbol || '';

	const chartDatasets = datasets.map( ( dataset, index ) => {
		const color = dataset.color || DEFAULT_COLORS[ index % DEFAULT_COLORS.length ];
		return {
			label: dataset.label || '',
			data: dataset.data || [],
			borderColor: color,
			backgroundColor: color,
			borderWidth: 2,
			yAxisID: dataset.yAxisID || ( isDual && index > 0 ? 'y1' : 'y' ),
		};
	} );

	const scales = {
		y: {
			min: 0,
			position: 'left',
			ticks: yCurrency
				? { callback: ( value ) => currencySymbol + value }
				: {},
		},
		x: {
			title: {
				display: !! xLabel,
				text: xLabel,
				align: 'end',
			},
		},
	};

	if ( isDual ) {
		scales.y1 = {
			min: 0,
			position: 'right',
			grid: { drawOnChartArea: false },
			ticks: { precision: 0 },
		};
	}

	const existing = Chart.getChart( canvas );
	if ( existing ) {
		// Axis set changed (1 ↔ 2 lines) is easier rebuilt than migrated.
		if ( existing.data.datasets.length !== chartDatasets.length ) {
			existing.destroy();
		} else {
			existing.data.labels = labels;
			chartDatasets.forEach( ( dataset, index ) => {
				existing.data.datasets[ index ].data = dataset.data;
				existing.data.datasets[ index ].label = dataset.label;
			} );
			existing.config.options.scales.x.title.text = xLabel;
			existing.update();
			return existing;
		}
	}

	return new Chart( canvas, {
		type: 'line',
		data: { labels, datasets: chartDatasets },
		options: {
			responsive: true,
			maintainAspectRatio: false,
			aspectRatio: 0.8,
			plugins: {
				legend: { display: isDual },
			},
			scales,
		},
	} );
};
