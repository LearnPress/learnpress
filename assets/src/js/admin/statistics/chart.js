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
 * Render (or update) a line chart.
 *
 * @param {string} canvasSelector e.g. '#net-sales-chart-content'.
 * @param {Object} chartData      { labels, datasets: [ { label, data, color, yAxisID } ], xLabel }.
 * @param {Object} config         { yCurrency?: boolean (default true when 2 datasets),
 *                                  formatLabel?: ( label, index ) => string }.
 * @return {Chart|null} Chart instance, or null when canvas missing / no data.
 */
export const renderLineChart = ( canvasSelector, chartData = {}, config = {} ) => {
	const canvas = document.querySelector( canvasSelector );
	if ( ! canvas ) {
		return null;
	}

	const { datasets = [], xLabel = '' } = chartData;
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

	if ( 'function' === typeof config.formatLabel ) {
		labels = labels.map( ( label, index ) => config.formatLabel( label, index ) );
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
