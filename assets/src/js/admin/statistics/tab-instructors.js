/**
 * Instructors tab module.
 *
 * Fetches the `dashboard` payload and renders KPIs, the operations widget,
 * instructor performance + course watchlist tables, per-instructor report
 * popup, and CSV export.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { lpStatsState, LP_STATS_FILTER_CHANGED, LP_STATS_EXPORT_CSV } from './state.js';
import { lpStatsFetch, getStatsConfig, getStatsI18n } from './api.js';
import { renderKpi } from './kpi.js';
import { renderLineChart } from './chart.js';
import { renderDataTable } from './data-table.js';
import { lpStatsReportModal } from './report-modal.js';
import { exportCsv, buildCsvFilename } from './csv.js';

const sprintfLite = ( template, value ) =>
	String( template ).replace( /%[ds]/, String( value ) ).replace( /%%/g, '%' );

export class LpStatsTabInstructors {
	static selectors = {
		elContainer: '.lp-stats-tab-instructors',
		elChartCanvas: '#instructor-chart-content',
		elTablePerformance: '.lp-stats-table-instructor-performance',
		elTableWatchlist: '.lp-stats-table-instructor-watchlist',
		elBtnViewAllInstructors: '.lp-stats-view-all-instructors',
		elPerformanceRow: '.lp-stats-instructor-performance-row',
		elOperationsRow: '.lp-stats-operations__row',
		elSkeleton: '.lp-skeleton-animation',
	};

	static kpiCards = {
		active_instructors: '.lp-kpi-active-instructors',
		instructor_revenue: '.lp-kpi-instructor-revenue',
		courses_managed: '.lp-kpi-courses-managed',
		students_reached: '.lp-kpi-students-reached',
		avg_completion: '.lp-kpi-avg-completion',
		needs_review: '.lp-kpi-needs-review',
	};

	constructor() {
		this.elContainer = null;
		this.isRequesting = false;
		this.pendingReload = false;
		this.tables = {};
	}

	init() {
		this.elContainer = document.querySelector(
			LpStatsTabInstructors.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		this.events();
		this.loadData();
	}

	events() {
		if ( LpStatsTabInstructors._loadedEvents ) {
			return;
		}
		LpStatsTabInstructors._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsTabInstructors.selectors.elBtnViewAllInstructors,
				class: this,
				callBack: this.viewAllInstructors.name,
			},
			{
				selector: LpStatsTabInstructors.selectors.elPerformanceRow,
				class: this,
				callBack: this.openInstructorReport.name,
			},
		] );

		document.addEventListener( LP_STATS_FILTER_CHANGED, () => this.loadData() );
		document.addEventListener( LP_STATS_EXPORT_CSV, () => this.exportTables() );
	}

	toggleSkeletons( show ) {
		this.elContainer
			.querySelectorAll( LpStatsTabInstructors.selectors.elSkeleton )
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
			'instructor-statistics',
			{},
			{
				before: () => this.toggleSkeletons( true ),
				success: ( response ) => this.render( response.data ),
				error: ( err ) => {
					console.error( 'LP Statistics instructors:', err );
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
			console.error( 'LP Statistics instructors: dashboard payload missing.' );
			data = { chart_data: {}, dashboard: {} };
		}

		const dashboard = data.dashboard || {};

		this.renderKpis( dashboard.kpis || {} );
		this.renderChart( data.chart_data || {} );
		this.renderOperations( dashboard.operations || {} );
		this.renderTables( dashboard );
	}

	renderKpis( kpis ) {
		Object.entries( LpStatsTabInstructors.kpiCards ).forEach(
			( [ key, selector ] ) => {
				const elCard = this.elContainer.querySelector( selector );
				const payload = { ...( kpis[ key ] || {} ) };

				if ( 'active_instructors' === key ) {
					payload.subline = sprintfLite(
						getStatsI18n( 'ofTotalInstructors', '%d total' ),
						payload.total ?? 0
					);
				}
				if ( 'instructor_revenue' === key && null != payload.contribution_pct ) {
					payload.subline = sprintfLite(
						getStatsI18n( 'ofNetSales', '%s%% of net sales' ),
						payload.contribution_pct
					);
				}
				if ( 'avg_completion' === key && 'number' === typeof payload.value ) {
					payload.formatted = `${ payload.value }%`;
				}

				renderKpi( elCard, payload );
			}
		);
	}

	renderChart( chartData ) {
		renderLineChart(
			LpStatsTabInstructors.selectors.elChartCanvas,
			{
				labels: chartData.labels || [],
				datasets: [
					{
						label: getStatsI18n( 'revenue', 'Revenue' ),
						data: chartData.revenue || [],
						yAxisID: 'y',
					},
					{
						label: getStatsI18n( 'enrollments', 'Enrollments' ),
						data: chartData.enrollments || [],
						yAxisID: 'y1',
					},
				],
				xLabel: chartData.x_label || '',
				granularity: chartData.granularity || '',
			}
		);
	}

	renderOperations( operations ) {
		this.elContainer
			.querySelectorAll( LpStatsTabInstructors.selectors.elOperationsRow )
			.forEach( ( elRow ) => {
				const op = elRow.dataset.op;
				const elValue = elRow.querySelector( '.lp-stats-operations__value' );
				const elName = elRow.querySelector( '.lp-stats-operations__name' );
				const data = operations[ op ];

				if ( elName ) {
					elName.textContent = '';
				}

				if ( null == data ) {
					if ( elValue ) {
						elValue.textContent = '–';
					}
					return;
				}

				// Scalar operations (counts) vs highlight objects ({ name, value }).
				if ( 'object' === typeof data ) {
					if ( elValue ) {
						elValue.textContent =
							data.value_formatted ??
							( 'number' === typeof data.value && op === 'top_completion'
								? `${ data.value }%`
								: String( data.value ?? '' ) );
					}
					if ( elName ) {
						elName.textContent = data.name || '';
					}
				} else if ( elValue ) {
					elValue.textContent = String( data );
				}
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

	riskLabel( slug ) {
		const labels = {
			high: getStatsI18n( 'riskHigh', 'High' ),
			medium: getStatsI18n( 'riskMedium', 'Medium' ),
			healthy: getStatsI18n( 'riskHealthy', 'Healthy' ),
		};

		return labels[ slug ] || String( slug || '' );
	}

	actionLabel( slug ) {
		const { watchlistActions = {} } = getStatsConfig().i18n || {};
		return watchlistActions[ slug ] || String( slug || '' );
	}

	performanceColumns() {
		return [
			{ key: 'name', label: getStatsI18n( 'instructor', 'Instructor' ) },
			{ key: 'courses', label: getStatsI18n( 'courses', 'Courses' ) },
			{ key: 'students', label: getStatsI18n( 'students', 'Students' ) },
			{
				key: 'revenue_formatted',
				label: getStatsI18n( 'revenue', 'Revenue' ),
				csv: ( row ) => row.revenue,
			},
			{
				key: 'avg_completion',
				label: getStatsI18n( 'completion', 'Completion' ),
				format: ( value ) => ( null == value ? '–' : `${ value }%` ),
				badge: ( row ) => this.completionBadge( row.avg_completion ),
			},
		];
	}

	watchlistColumns() {
		return [
			{ key: 'name', label: getStatsI18n( 'course', 'Course' ) },
			{ key: 'instructor', label: getStatsI18n( 'instructor', 'Instructor' ) },
			{
				key: 'completion_rate',
				label: getStatsI18n( 'completion', 'Completion' ),
				format: ( value ) => ( null == value ? '–' : `${ value }%` ),
			},
			{
				key: 'risk',
				label: getStatsI18n( 'risk', 'Risk' ),
				// Emoji lives in CSS pseudo-content on .lp-risk--{slug}; text stays clean.
				format: ( value ) => {
					const span = document.createElement( 'span' );
					span.className = `lp-badge lp-risk lp-risk--${ value }`;
					span.textContent = this.riskLabel( value );
					return span;
				},
				csv: ( row ) => this.riskLabel( row.risk ),
			},
			{
				key: 'action',
				label: getStatsI18n( 'actionRequired', 'Action required' ),
				format: ( value ) => this.actionLabel( value ),
				csv: ( row ) => this.actionLabel( row.action ),
			},
		];
	}

	renderTables( dashboard ) {
		const performanceRows = dashboard.performance || [];
		this.tables.performance = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabInstructors.selectors.elTablePerformance
			),
			this.performanceColumns(),
			performanceRows,
			{ emptyText: getStatsI18n( 'noInstructorData', 'No instructor data in this period.' ) }
		);
		this.decoratePerformanceRows( performanceRows );

		this.tables.watchlist = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabInstructors.selectors.elTableWatchlist
			),
			this.watchlistColumns(),
			dashboard.watchlist || []
		);
	}

	decoratePerformanceRows( rows = [] ) {
		const scopedInstructor = lpStatsState.get().instructor_id;
		const tableRows = this.elContainer.querySelectorAll(
			`${ LpStatsTabInstructors.selectors.elTablePerformance } tbody tr`
		);

		rows.forEach( ( row, index ) => {
			const tableRow = tableRows[ index ];
			if ( ! tableRow || ! row.instructor_id ) {
				return;
			}

			tableRow.classList.add( 'lp-stats-instructor-performance-row' );
			tableRow.dataset.instructorId = String( row.instructor_id );
			tableRow.dataset.instructorName = row.name || '';

			if ( scopedInstructor && scopedInstructor === row.instructor_id ) {
				tableRow.classList.add( 'is-highlighted' );
			}
		} );
	}

	openInstructorReport( args ) {
		const row = args.target.closest(
			LpStatsTabInstructors.selectors.elPerformanceRow
		);
		if ( ! row || ! this.elContainer.contains( row ) ) {
			return;
		}

		const instructorId = parseInt( row.dataset.instructorId, 10 ) || 0;
		if ( instructorId <= 0 ) {
			return;
		}
		const instructorName =
			row.dataset.instructorName || getStatsI18n( 'instructorReport', 'Instructor report' );

		lpStatsReportModal.open( {
			report: 'instructor_report',
			title: instructorName,
			tableId: `instructor-${ instructorId }`,
			args: { instructor_id: instructorId },
		} );
	}

	viewAllInstructors( args ) {
		const btn = args.target.closest(
			LpStatsTabInstructors.selectors.elBtnViewAllInstructors
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		lpStatsReportModal.open( {
			report: 'instructor_performance',
			title: getStatsI18n(
				'instructorPerformance',
				'Instructor performance'
			),
			tableId: 'instructor-performance',
		} );
	}

	exportTables() {
		Object.entries( this.tables ).forEach( ( [ tableId, handle ] ) => {
			if ( handle && handle.rows.length ) {
				exportCsv(
					buildCsvFilename( 'instructors', tableId ),
					handle.columns,
					handle.rows
				);
			}
		} );
	}
}

export const lpStatsTabInstructors = new LpStatsTabInstructors();
