/**
 * Orders tab module.
 *
 * Fetches the `dashboard` payload and renders KPIs, completed-orders chart,
 * top sold courses, recent exceptions, popups and CSV export.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { LP_STATS_FILTER_CHANGED, LP_STATS_EXPORT_CSV } from './state.js';
import { lpStatsFetch, getStatsI18n } from './api.js';
import { renderKpi } from './kpi.js';
import { renderLineChart } from './chart.js';
import { renderDataTable } from './data-table.js';
import { lpStatsReportModal } from './report-modal.js';
import { exportCsv, buildCsvFilename } from './csv.js';

const sprintfLite = ( template, value ) =>
	String( template ).replace( /%[ds]/, String( value ) ).replace( /%%/g, '%' );

export class LpStatsTabOrders {
	static selectors = {
		elContainer: '.lp-stats-tab-orders',
		elChartCanvas: '#orders-chart-content',
		elTableTopSold: '.lp-stats-table-top-sold-courses',
		elTableExceptions: '.lp-stats-table-order-exceptions',
		elBtnViewAllTopSold: '.lp-stats-view-all-top-sold',
		elBtnViewAllExceptions: '.lp-stats-view-all-exceptions',
		elPaymentHealthRow: '.lp-stats-payment-health__row',
		elSkeleton: '.lp-skeleton-animation',
	};

	static kpiCards = {
		net_sales: '.lp-kpi-net-sales',
		completed_orders: '.lp-kpi-completed-orders',
		processing: '.lp-kpi-processing',
		pending: '.lp-kpi-pending',
		cancelled_failed: '.lp-kpi-cancelled-failed',
		paid_courses_sold: '.lp-kpi-paid-courses-sold',
	};

	static orderStatusAllowlist = [
		'completed',
		'processing',
		'pending',
		'cancelled',
		'failed',
	];

	constructor() {
		this.elContainer = null;
		this.isRequesting = false;
		this.pendingReload = false;
		this.tables = {};
		this.orderStatusFilter = '';
	}

	init() {
		this.elContainer = document.querySelector(
			LpStatsTabOrders.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		this.orderStatusFilter = this.readOrderStatus();
		this.events();
		this.loadData();
	}

	events() {
		if ( LpStatsTabOrders._loadedEvents ) {
			return;
		}
		LpStatsTabOrders._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsTabOrders.selectors.elBtnViewAllTopSold,
				class: this,
				callBack: this.viewAllTopSold.name,
			},
			{
				selector: LpStatsTabOrders.selectors.elBtnViewAllExceptions,
				class: this,
				callBack: this.viewAllExceptions.name,
			},
		] );

		document.addEventListener( LP_STATS_FILTER_CHANGED, () => this.loadData() );
		document.addEventListener( LP_STATS_EXPORT_CSV, () => this.exportTables() );
	}

	readOrderStatus() {
		const status = new URL( window.location.href ).searchParams.get(
			'order_status'
		);

		return LpStatsTabOrders.orderStatusAllowlist.includes( status )
			? status
			: '';
	}

	toggleSkeletons( show ) {
		this.elContainer
			.querySelectorAll( LpStatsTabOrders.selectors.elSkeleton )
			.forEach( ( el ) => {
				el.style.display = show ? 'block' : 'none';
			} );
	}

	loadData() {
		if ( this.isRequesting ) {
			this.pendingReload = true;
			return;
		}
		this.isRequesting = true;

		lpStatsFetch(
			'order-statistics',
			{},
			{
				before: () => this.toggleSkeletons( true ),
				success: ( response ) => this.render( response.data ),
				error: ( err ) => {
					console.error( 'LP Statistics orders:', err );
					this.render( null );
				},
				completed: () => {
					this.toggleSkeletons( false );
					this.isRequesting = false;
					if ( this.pendingReload ) {
						this.pendingReload = false;
						this.loadData();
					}
				},
			}
		);
	}

	render( data ) {
		if ( ! data?.dashboard ) {
			console.error( 'LP Statistics orders: dashboard payload missing.' );
			data = { chart_data: {}, dashboard: {} };
		}

		const dashboard = data.dashboard || {};

		this.renderKpis( dashboard.kpis || {} );
		this.renderChart( data.chart_data || {} );
		this.renderPaymentHealth( dashboard.order_health || {} );
		this.renderTables( dashboard );
		this.highlightStatus();
	}

	renderKpis( kpis ) {
		Object.entries( LpStatsTabOrders.kpiCards ).forEach( ( [ key, selector ] ) => {
			const elCard = this.elContainer.querySelector( selector );
			const payload = { ...( kpis[ key ] || {} ) };

			if ( 'completed_orders' === key && payload.aov_formatted ) {
				payload.subline = sprintfLite(
					getStatsI18n( 'aov', 'Avg. order value: %s' ),
					payload.aov_formatted
				);
			}
			if ( 'processing' === key ) {
				payload.subline = getStatsI18n(
					'needsFulfillmentReview',
					'Needs fulfillment review'
				);
			}
			if ( 'pending' === key ) {
				payload.subline = getStatsI18n( 'awaitingPayment', 'Awaiting payment' );
			}
			if ( 'cancelled_failed' === key && null != payload.rate_pct ) {
				payload.subline = sprintfLite(
					getStatsI18n( 'exceptionRate', '%s%% of all orders' ),
					payload.rate_pct
				);
			}

			renderKpi( elCard, payload );
		} );
	}

	renderPaymentHealth( orderHealth ) {
		this.elContainer
			.querySelectorAll( LpStatsTabOrders.selectors.elPaymentHealthRow )
			.forEach( ( elRow ) => {
				const status = elRow.dataset.status;
				const elCount = elRow.querySelector(
					'.lp-stats-payment-health__count'
				);

				if ( elCount ) {
					elCount.textContent = String( orderHealth[ status ] ?? 0 );
				}
			} );
	}

	renderChart( chartData ) {
		renderLineChart(
			LpStatsTabOrders.selectors.elChartCanvas,
			{
				labels: chartData.labels || [],
				datasets: [
					{
						label: chartData.line_label || getStatsI18n( 'orders', 'Orders' ),
						data: chartData.data || [],
						yAxisID: 'y',
					},
				],
				xLabel: chartData.x_label || '',
				granularity: chartData.granularity || '',
			},
			{ yCurrency: false }
		);
	}

	statusLabel( slug ) {
		const labels = {
			healthy: getStatsI18n( 'healthy', 'Healthy' ),
			watch_completion: getStatsI18n(
				'watchCompletion',
				'Watch completion'
			),
			high_failed_quizzes: getStatsI18n(
				'highFailedQuizzes',
				'High failed quizzes'
			),
		};

		return labels[ slug ] || String( slug || '' );
	}

	statusBadge( slug ) {
		if ( 'high_failed_quizzes' === slug ) {
			return 'red';
		}
		if ( 'watch_completion' === slug ) {
			return 'yellow';
		}

		return 'green';
	}

	severityLabel( severity ) {
		const labels = {
			high: getStatsI18n( 'high', 'High' ),
			medium: getStatsI18n( 'medium', 'Medium' ),
			low: getStatsI18n( 'low', 'Low' ),
		};

		return labels[ severity ] || String( severity || '' );
	}

	severityBadge( severity ) {
		if ( 'high' === severity ) {
			return 'red';
		}
		if ( 'medium' === severity ) {
			return 'yellow';
		}

		return 'grey';
	}

	topSoldColumns() {
		return [
			{ key: 'name', label: getStatsI18n( 'course', 'Course' ) },
			{
				key: 'revenue_formatted',
				label: getStatsI18n( 'revenue', 'Revenue' ),
				csv: ( row ) => row.revenue,
			},
			{ key: 'orders', label: getStatsI18n( 'orders', 'Orders' ) },
			{
				key: 'aov_formatted',
				label: getStatsI18n( 'aovShort', 'AOV' ),
				csv: ( row ) => row.aov,
			},
			{
				key: 'status_label',
				label: getStatsI18n( 'status', 'Status' ),
				format: ( value ) => this.statusLabel( value ),
				badge: ( row ) => this.statusBadge( row.status_label ),
			},
		];
	}

	exceptionColumns() {
		return [
			{
				key: 'order_id',
				label: getStatsI18n( 'orderId', 'Order ID' ),
				format: ( value, row ) => {
					if ( ! row.edit_link ) {
						return value;
					}

					const link = document.createElement( 'a' );
					link.href = row.edit_link;
					link.textContent = `#${ value }`;
					return link;
				},
				csv: ( row ) => row.order_id,
			},
			{ key: 'student', label: getStatsI18n( 'student', 'Student' ) },
			{ key: 'course', label: getStatsI18n( 'course', 'Course' ) },
			{ key: 'issue', label: getStatsI18n( 'issue', 'Issue' ) },
			{ key: 'date', label: getStatsI18n( 'date', 'Date' ) },
			{
				key: 'severity',
				label: getStatsI18n( 'severity', 'Severity' ),
				format: ( value ) => this.severityLabel( value ),
				badge: ( row ) => this.severityBadge( row.severity ),
			},
		];
	}

	filterExceptions( rows = [] ) {
		if ( ! [ 'cancelled', 'failed' ].includes( this.orderStatusFilter ) ) {
			return rows;
		}

		return rows.filter( ( row ) => row.status === this.orderStatusFilter );
	}

	renderTables( dashboard ) {
		this.tables[ 'top-sold-courses' ] = renderDataTable(
			this.elContainer.querySelector( LpStatsTabOrders.selectors.elTableTopSold ),
			this.topSoldColumns(),
			dashboard.top_sold_courses || []
		);

		const exceptionRows = this.filterExceptions( dashboard.exceptions || [] );
		const exceptionHandle = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabOrders.selectors.elTableExceptions
			),
			this.exceptionColumns(),
			exceptionRows,
			{
				emptyText: getStatsI18n(
					'noOrderExceptions',
					'No failed or cancelled orders in this period.'
				),
			}
		);

		this.tables.exceptions = {
			...exceptionHandle,
			rows: exceptionRows,
			allRows: dashboard.exceptions || [],
		};
	}

	highlightStatus() {
		Object.values( LpStatsTabOrders.kpiCards ).forEach( ( selector ) => {
			const elCard = this.elContainer.querySelector( selector );
			if ( elCard ) {
				elCard.classList.remove( 'is-highlighted' );
			}
		} );

		const statusMap = {
			completed: 'completed_orders',
			processing: 'processing',
			pending: 'pending',
			cancelled: 'cancelled_failed',
			failed: 'cancelled_failed',
		};
		const kpiKey = statusMap[ this.orderStatusFilter ];
		const selector = kpiKey ? LpStatsTabOrders.kpiCards[ kpiKey ] : '';
		const elCard = selector ? this.elContainer.querySelector( selector ) : null;

		if ( elCard ) {
			elCard.classList.add( 'is-highlighted' );
		}
	}

	viewAllTopSold( args ) {
		const btn = args.target.closest(
			LpStatsTabOrders.selectors.elBtnViewAllTopSold
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		lpStatsReportModal.open( {
			report: 'top_sold_courses',
			title: getStatsI18n( 'topSoldCourses', 'Top sold courses' ),
			tableId: 'top-sold-courses',
		} );
	}

	viewAllExceptions( args ) {
		const btn = args.target.closest(
			LpStatsTabOrders.selectors.elBtnViewAllExceptions
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		// The cancelled/failed deep-link is pushed to the server so pagination
		// totals match the rows shown ( no more client-side filterExceptions ).
		lpStatsReportModal.open( {
			report: 'exceptions',
			title: getStatsI18n( 'orderExceptions', 'Recent order exceptions' ),
			tableId: 'exceptions',
			orderStatus: [ 'cancelled', 'failed' ].includes( this.orderStatusFilter )
				? this.orderStatusFilter
				: '',
		} );
	}

	exportTables() {
		Object.entries( this.tables ).forEach( ( [ tableId, handle ] ) => {
			if ( handle && handle.rows.length ) {
				exportCsv(
					buildCsvFilename( 'orders', tableId ),
					handle.columns,
					handle.rows
				);
			}
		} );
	}
}

export const lpStatsTabOrders = new LpStatsTabOrders();
