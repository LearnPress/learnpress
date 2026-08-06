/**
 * Overview tab module — fetches the `dashboard` payload and renders
 * KPIs, dual-line chart, funnel, tables, order health and health checks.
 *
 * Listens to lp-stats:filter-changed; never mutates state itself.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { LP_STATS_FILTER_CHANGED, LP_STATS_EXPORT_CSV } from './state.js';
import { lpStatsFetch, getStatsConfig, getStatsI18n } from './api.js';
import { renderKpi } from './kpi.js';
import { renderLineChart } from './chart.js';
import { renderDataTable } from './data-table.js';
import { lpStatsReportModal } from './report-modal.js';
import { exportCsv, buildCsvFilename } from './csv.js';

const sprintfLite = ( template, value ) =>
	String( template ).replace( /%[ds]/, String( value ) ).replace( /%%/g, '%' );

export class LpStatsTabOverview {
	static selectors = {
		elContainer: '.lp-stats-tab-overview',
		elChartCanvas: '#net-sales-chart-content',
		elFunnelStep: '.lp-stats-funnel__step',
		elTableTopCourses: '.lp-stats-table-top-courses',
		elTableInstructors: '.lp-stats-table-instructors',
		elBtnViewAllCourses: '.lp-stats-view-all-courses',
		elOrderHealthBox: '.lp-stats-order-health .lp-stats-health-box',
		elHealthCheckCount: '.lp-stats-health-check__count',
		elSkeleton: '.lp-skeleton-animation',
	};

	static kpiCards = {
		net_sales: '.lp-kpi-net-sales',
		completed_orders: '.lp-kpi-completed-orders',
		enrollments: '.lp-kpi-enrollments',
		completion_rate: '.lp-kpi-completion-rate',
		active_learners: '.lp-kpi-active-learners',
		failed_orders: '.lp-kpi-failed-orders',
	};

	constructor() {
		this.elContainer = null;
		this.isRequesting = false;
		this.pendingReload = false;
		this.tables = {};
	}

	init() {
		this.elContainer = document.querySelector(
			LpStatsTabOverview.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		this.events();
		this.loadData();
	}

	events() {
		if ( LpStatsTabOverview._loadedEvents ) {
			return;
		}
		LpStatsTabOverview._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsTabOverview.selectors.elBtnViewAllCourses,
				class: this,
				callBack: this.viewAllCourses.name,
			},
		] );

		document.addEventListener( LP_STATS_FILTER_CHANGED, () => this.loadData() );
		document.addEventListener( LP_STATS_EXPORT_CSV, () => this.exportTables() );
	}

	toggleSkeletons( show ) {
		this.elContainer
			.querySelectorAll( LpStatsTabOverview.selectors.elSkeleton )
			.forEach( ( el ) => {
				el.style.display = show ? 'block' : 'none';
			} );
	}

	loadData() {
		if ( this.isRequesting ) {
			// Latest filter wins: re-run once the in-flight request finishes.
			this.pendingReload = true;
			return;
		}
		this.isRequesting = true;

		lpStatsFetch(
			'overviews-statistics',
			{},
			{
				before: () => this.toggleSkeletons( true ),
				success: ( response ) => this.render( response.data?.dashboard ),
				error: ( err ) => {
					console.error( 'LP Statistics overview:', err );
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

	/**
	 * @param {Object|null} dashboard `dashboard` key of the response; null/missing
	 *                                renders empty states, never throws.
	 */
	render( dashboard ) {
		if ( ! dashboard ) {
			console.error( 'LP Statistics overview: dashboard payload missing.' );
			dashboard = {};
		}

		this.renderKpis( dashboard.kpis || {} );
		this.renderChart( dashboard.chart || {} );
		this.renderFunnel( dashboard.funnel || {} );
		this.renderTables( dashboard );
		this.renderOrderHealth( dashboard.order_health || {} );
		this.renderHealthChecks( dashboard.health_checks || {} );
	}

	renderKpis( kpis ) {
		const i18n = ( key, fallback ) => getStatsI18n( key, fallback );

		Object.entries( LpStatsTabOverview.kpiCards ).forEach(
			( [ key, selector ] ) => {
				const elCard = this.elContainer.querySelector( selector );
				const payload = { ...( kpis[ key ] || {} ) };

				if ( 'completed_orders' === key && payload.aov_formatted ) {
					payload.subline = sprintfLite(
						i18n( 'aov', 'Avg. order value: %s' ),
						payload.aov_formatted
					);
				}
				if ( 'completion_rate' === key ) {
					if ( 'number' === typeof payload.value ) {
						payload.formatted = `${ payload.value }%`;
					}
					payload.subline = sprintfLite(
						i18n( 'belowTarget', '%d below completion target' ),
						payload.courses_below_target ?? 0
					);
				}
				if ( 'failed_orders' === key && null != payload.fail_rate_pct ) {
					payload.subline = sprintfLite(
						i18n( 'failRate', '%s%% of all orders' ),
						payload.fail_rate_pct
					);
				}

				renderKpi( elCard, payload );
			}
		);
	}

	renderChart( chart ) {
		renderLineChart( LpStatsTabOverview.selectors.elChartCanvas, {
			labels: chart.labels || [],
			datasets: [
				{
					label: getStatsI18n( 'revenue', 'Revenue' ),
					data: chart.revenue || [],
					yAxisID: 'y',
				},
				{
					label: getStatsI18n( 'enrollments', 'Enrollments' ),
					data: chart.enrollments || [],
					yAxisID: 'y1',
				},
			],
			xLabel: chart.x_label || '',
			granularity: chart.granularity || '',
		} );
	}

	renderFunnel( funnel ) {
		const steps = [ 'registered', 'enrolled', 'started', 'completed' ];
		let previous = null;

		steps.forEach( ( step ) => {
			const elStep = this.elContainer.querySelector(
				`${ LpStatsTabOverview.selectors.elFunnelStep }[data-step="${ step }"]`
			);
			if ( ! elStep ) {
				return;
			}

			const count = Number( funnel[ step ] ?? 0 );
			const base = null === previous ? count : previous;
			const width = base > 0 ? Math.min( 100, ( count / base ) * 100 ) : 0;

			elStep.querySelector( '.lp-stats-funnel__count' ).textContent =
				String( count );
			elStep.querySelector( '.lp-stats-funnel__bar' ).style.width = `${ width }%`;
			previous = count;
		} );
	}

	completionBadge( rate ) {
		if ( null == rate ) {
			return '';
		}
		const { completionBadge = {} } = getStatsConfig();
		const green = completionBadge.green ?? 60;
		const yellow = completionBadge.yellow ?? 40;

		if ( rate >= green ) {
			return 'green';
		}
		return rate >= yellow ? 'yellow' : 'red';
	}

	topCoursesColumns() {
		return [
			{ key: 'course_name', label: getStatsI18n( 'course', 'Course' ) },
			{
				key: 'revenue_formatted',
				label: getStatsI18n( 'revenue', 'Revenue' ),
				csv: ( row ) => row.revenue,
			},
			{ key: 'order_count', label: getStatsI18n( 'orders', 'Orders' ) },
			{ key: 'enrolled', label: getStatsI18n( 'enrolled', 'Enrolled' ) },
			{
				key: 'completion_rate',
				label: getStatsI18n( 'completion', 'Completion' ),
				format: ( value ) => ( null == value ? '–' : `${ value }%` ),
				badge: ( row ) => this.completionBadge( row.completion_rate ),
			},
		];
	}

	instructorColumns() {
		return [
			{ key: 'instructor_name', label: getStatsI18n( 'instructor', 'Instructor' ) },
			{ key: 'course_count', label: getStatsI18n( 'courses', 'Courses' ) },
			{
				key: 'revenue_formatted',
				label: getStatsI18n( 'revenue', 'Revenue' ),
				csv: ( row ) => row.revenue,
			},
			{ key: 'enrolled', label: getStatsI18n( 'enrolled', 'Enrolled' ) },
			{
				key: 'completion_rate',
				label: getStatsI18n( 'completion', 'Completion' ),
				format: ( value ) => ( null == value ? '–' : `${ value }%` ),
				badge: ( row ) => this.completionBadge( row.completion_rate ),
			},
		];
	}

	renderTables( dashboard ) {
		this.tables[ 'top-courses' ] = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabOverview.selectors.elTableTopCourses
			),
			this.topCoursesColumns(),
			dashboard.top_courses || []
		);

		this.tables.instructors = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabOverview.selectors.elTableInstructors
			),
			this.instructorColumns(),
			dashboard.instructor_summary || []
		);
	}

	renderOrderHealth( orderHealth ) {
		this.elContainer
			.querySelectorAll( LpStatsTabOverview.selectors.elOrderHealthBox )
			.forEach( ( elBox ) => {
				const status = elBox.dataset.status;
				const elCount = elBox.querySelector( '.lp-stats-health-box__count' );
				if ( elCount ) {
					elCount.textContent = String( orderHealth[ status ] ?? 0 );
				}
			} );
	}

	renderHealthChecks( healthChecks ) {
		this.elContainer
			.querySelectorAll( LpStatsTabOverview.selectors.elHealthCheckCount )
			.forEach( ( elCount ) => {
				const check = elCount.dataset.check;
				elCount.textContent = String( healthChecks[ check ] ?? 0 );
			} );
	}

	viewAllCourses( args ) {
		const btn = args.target.closest(
			LpStatsTabOverview.selectors.elBtnViewAllCourses
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		lpStatsReportModal.open( {
			report: 'top_courses',
			title: getStatsI18n( 'topCourses', 'Top courses' ),
			tableId: 'top-courses',
		} );
	}

	exportTables() {
		Object.entries( this.tables ).forEach( ( [ tableId, handle ] ) => {
			if ( handle && handle.rows.length ) {
				exportCsv(
					buildCsvFilename( 'overview', tableId ),
					handle.columns,
					handle.rows
				);
			}
		} );
	}
}

export const lpStatsTabOverview = new LpStatsTabOverview();
