/**
 * Courses tab module.
 *
 * Fetches the `dashboard` payload and renders KPIs, course performance,
 * published-courses chart, health checks, inventory, popups and CSV export.
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

export class LpStatsTabCourses {
	static selectors = {
		elContainer: '.lp-stats-tab-courses',
		elChartCanvas: '#course-chart-content',
		elTablePerformance: '.lp-stats-table-course-performance',
		elTableInventory: '.lp-stats-table-content-inventory',
		elBtnViewAllPerformance: '.lp-stats-view-all-performance',
		elPerformanceRow: '.lp-stats-course-performance-row',
		elHealthCheckCount: '.lp-stats-health-check__count',
		elSkeleton: '.lp-skeleton-animation',
	};

	static kpiCards = {
		published: '.lp-kpi-published',
		pending_review: '.lp-kpi-pending-review',
		future: '.lp-kpi-future',
		enrollments: '.lp-kpi-enrollments',
		avg_completion: '.lp-kpi-avg-completion',
		courses_without_enrollment: '.lp-kpi-courses-without-enrollment',
	};

	constructor() {
		this.elContainer = null;
		this.isRequesting = false;
		this.pendingReload = false;
		this.tables = {};
	}

	init() {
		this.elContainer = document.querySelector(
			LpStatsTabCourses.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		this.events();
		this.loadData();
	}

	events() {
		if ( LpStatsTabCourses._loadedEvents ) {
			return;
		}
		LpStatsTabCourses._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsTabCourses.selectors.elBtnViewAllPerformance,
				class: this,
				callBack: this.viewAllPerformance.name,
			},
			{
				selector: LpStatsTabCourses.selectors.elPerformanceRow,
				class: this,
				callBack: this.openCourseEdit.name,
			},
		] );

		document.addEventListener( LP_STATS_FILTER_CHANGED, () => this.loadData() );
		document.addEventListener( LP_STATS_EXPORT_CSV, () => this.exportTables() );
	}

	toggleSkeletons( show ) {
		this.elContainer
			.querySelectorAll( LpStatsTabCourses.selectors.elSkeleton )
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
			'course-statistics',
			{},
			{
				before: () => this.toggleSkeletons( true ),
				success: ( response ) => this.render( response.data ),
				error: ( err ) => {
					console.error( 'LP Statistics courses:', err );
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
			console.error( 'LP Statistics courses: dashboard payload missing.' );
			data = { chart_data: {}, dashboard: {} };
		}

		const dashboard = data.dashboard || {};

		this.renderKpis( dashboard.kpis || {} );
		this.renderTables( dashboard );
		// Prefer the scoped chart from the dashboard payload so instructor/category
		// changes redraw the chart; fall back to the legacy unscoped series.
		this.renderChart( dashboard.chart || data.chart_data || {} );
		this.renderHealthChecks( dashboard.health_checks || {} );
	}

	renderKpis( kpis ) {
		Object.entries( LpStatsTabCourses.kpiCards ).forEach( ( [ key, selector ] ) => {
			const elCard = this.elContainer.querySelector( selector );
			const payload = { ...( kpis[ key ] || {} ) };

			if ( 'published' === key ) {
				payload.subline = sprintfLite(
					getStatsI18n( 'addedThisPeriod', '%d added this period' ),
					payload.added_in_period ?? 0
				);
			}
			if ( 'pending_review' === key ) {
				payload.subline = getStatsI18n(
					'needsInstructorAction',
					'Needs instructor action'
				);
			}
			if ( 'future' === key ) {
				payload.subline = getStatsI18n(
					'scheduledReleases',
					'Scheduled releases'
				);
			}
			if ( 'avg_completion' === key ) {
				if ( 'number' === typeof payload.value ) {
					payload.formatted = `${ payload.value }%`;
				}
				payload.subline = sprintfLite(
					getStatsI18n( 'targetPercent', 'Target: %s%%' ),
					payload.target ?? getStatsConfig().completionTarget ?? 0
				);
			}

			renderKpi( elCard, payload );

			if ( 'avg_completion' === key ) {
				this.renderCompletionProgress( elCard, payload );
			}
		} );
	}

	renderCompletionProgress( elCard, payload ) {
		const elBar = elCard?.querySelector( '.lp-kpi-progress__bar' );
		if ( ! elBar ) {
			return;
		}

		const value = Number( payload.value || 0 );
		const target = Number( payload.target || getStatsConfig().completionTarget || 0 );
		const width = target > 0 ? Math.min( 100, ( value / target ) * 100 ) : 0;
		elBar.style.width = `${ width }%`;
	}

	renderChart( chartData ) {
		renderLineChart(
			LpStatsTabCourses.selectors.elChartCanvas,
			{
				labels: chartData.labels || [],
				datasets: [
					{
						label:
							chartData.line_label ||
							getStatsI18n( 'publishedCourses', 'Published courses' ),
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

	performanceColumns() {
		return [
			{ key: 'name', label: getStatsI18n( 'course', 'Course' ) },
			{ key: 'instructor', label: getStatsI18n( 'instructor', 'Instructor' ) },
			{
				key: 'revenue_formatted',
				label: getStatsI18n( 'revenue', 'Revenue' ),
				csv: ( row ) => row.revenue,
			},
			{
				key: 'enrollments',
				label: getStatsI18n( 'enrollments', 'Enrollments' ),
			},
			{
				key: 'completion_rate',
				label: getStatsI18n( 'completion', 'Completion' ),
				format: ( value ) => ( null == value ? '-' : `${ value }%` ),
				badge: ( row ) => this.completionBadge( row.completion_rate ),
			},
		];
	}

	inventoryLabel( key ) {
		const labels = {
			courses: getStatsI18n( 'courses', 'Courses' ),
			lessons: getStatsI18n( 'lessons', 'Lessons' ),
			quizzes: getStatsI18n( 'quizzes', 'Quizzes' ),
			assignments: getStatsI18n( 'assignments', 'Assignments' ),
		};

		return labels[ key ] || String( key || '' );
	}

	inventoryStatusLabel( key ) {
		const labels = {
			publish: getStatsI18n( 'published', 'Published' ),
			pending: getStatsI18n( 'pending', 'Pending' ),
			future: getStatsI18n( 'future', 'Future' ),
			draft: getStatsI18n( 'drafts', 'Drafts' ),
			total: getStatsI18n( 'total', 'Total' ),
		};

		return labels[ key ] || String( key || '' );
	}

	inventoryRows( inventory = {} ) {
		return Object.entries( inventory ).map( ( [ type, counts ] ) => ( {
			type,
			label: this.inventoryLabel( type ),
			...( counts || {} ),
		} ) );
	}

	inventoryColumns( rows = [] ) {
		const preferred = [ 'publish', 'pending', 'future', 'draft', 'total' ];
		const statusKeys = preferred.filter( ( key ) =>
			rows.some( ( row ) => Object.prototype.hasOwnProperty.call( row, key ) )
		);

		return [
			{ key: 'label', label: getStatsI18n( 'content', 'Content' ) },
			...statusKeys.map( ( key ) => ( {
				key,
				label: this.inventoryStatusLabel( key ),
				csv: ( row ) => row[ key ],
			} ) ),
		];
	}

	renderTables( dashboard ) {
		const performanceRows = dashboard.performance || [];
		const performanceHandle = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabCourses.selectors.elTablePerformance
			),
			this.performanceColumns(),
			performanceRows,
			{
				emptyText: getStatsI18n(
					'noCoursePerformance',
					'No course performance data in this period.'
				),
			}
		);
		this.tables.performance = performanceHandle;
		this.decoratePerformanceRows( performanceRows );

		const inventoryRows = this.inventoryRows( dashboard.inventory || {} );
		this.tables.inventory = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabCourses.selectors.elTableInventory
			),
			this.inventoryColumns( inventoryRows ),
			inventoryRows
		);
	}

	decoratePerformanceRows( rows = [] ) {
		const tableRows = this.elContainer.querySelectorAll(
			`${ LpStatsTabCourses.selectors.elTablePerformance } tbody tr`
		);

		rows.forEach( ( row, index ) => {
			const tableRow = tableRows[ index ];
			if ( tableRow && row.edit_link ) {
				tableRow.classList.add( 'lp-stats-course-performance-row' );
				tableRow.dataset.editLink = row.edit_link;
			}
		} );
	}

	renderHealthChecks( healthChecks ) {
		this.elContainer
			.querySelectorAll( LpStatsTabCourses.selectors.elHealthCheckCount )
			.forEach( ( elCount ) => {
				const check = elCount.dataset.check;
				elCount.textContent = String( healthChecks[ check ] ?? 0 );
			} );
	}

	openCourseEdit( args ) {
		const row = args.target.closest(
			LpStatsTabCourses.selectors.elPerformanceRow
		);
		if ( ! row || ! this.elContainer.contains( row ) ) {
			return;
		}

		const editLink = row.dataset.editLink || '';
		const adminUrl = getStatsConfig().adminUrl || '';
		if ( editLink && adminUrl && editLink.startsWith( adminUrl ) ) {
			window.location.href = editLink;
		}
	}

	viewAllPerformance( args ) {
		const btn = args.target.closest(
			LpStatsTabCourses.selectors.elBtnViewAllPerformance
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		lpStatsReportModal.open( {
			report: 'course_performance',
			title: getStatsI18n( 'coursePerformance', 'Course performance' ),
			tableId: 'course-performance',
		} );
	}

	exportTables() {
		Object.entries( this.tables ).forEach( ( [ tableId, handle ] ) => {
			if ( handle && handle.rows.length ) {
				exportCsv(
					buildCsvFilename( 'courses', tableId ),
					handle.columns,
					handle.rows
				);
			}
		} );
	}
}

export const lpStatsTabCourses = new LpStatsTabCourses();
