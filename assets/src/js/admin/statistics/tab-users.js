/**
 * Users tab module.
 *
 * Fetches the `dashboard` payload and renders KPIs, registered-users chart,
 * 5-step funnel (incl. failed), Top Students and Top Courses by Students
 * tables, popups and CSV export.
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

export class LpStatsTabUsers {
	static selectors = {
		elContainer: '.lp-stats-tab-users',
		elChartCanvas: '#user-chart-content',
		elFunnelStep: '.lp-stats-funnel__step',
		elTableTopStudents: '.lp-stats-table-top-students',
		elTableCoursesByStudents: '.lp-stats-table-courses-by-students',
		elBtnViewAllStudents: '.lp-stats-view-all-students',
		elBtnViewAllCourses: '.lp-stats-view-all-courses-by-students',
		elSkeleton: '.lp-skeleton-animation',
	};

	static kpiCards = {
		users_activated: '.lp-kpi-users-activated',
		students: '.lp-kpi-students',
		instructors: '.lp-kpi-instructors',
		not_started: '.lp-kpi-not-started',
		in_progress: '.lp-kpi-in-progress',
		finished: '.lp-kpi-finished',
	};

	constructor() {
		this.elContainer = null;
		this.isRequesting = false;
		this.pendingReload = false;
		this.tables = {};
	}

	init() {
		this.elContainer = document.querySelector(
			LpStatsTabUsers.selectors.elContainer
		);
		if ( ! this.elContainer ) {
			return;
		}

		this.events();
		this.loadData();
	}

	events() {
		if ( LpStatsTabUsers._loadedEvents ) {
			return;
		}
		LpStatsTabUsers._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsTabUsers.selectors.elBtnViewAllStudents,
				class: this,
				callBack: this.viewAllStudents.name,
			},
			{
				selector: LpStatsTabUsers.selectors.elBtnViewAllCourses,
				class: this,
				callBack: this.viewAllCoursesByStudents.name,
			},
		] );

		document.addEventListener( LP_STATS_FILTER_CHANGED, () => this.loadData() );
		document.addEventListener( LP_STATS_EXPORT_CSV, () => this.exportTables() );
	}

	toggleSkeletons( show ) {
		this.elContainer
			.querySelectorAll( LpStatsTabUsers.selectors.elSkeleton )
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
			'user-statistics',
			{},
			{
				before: () => this.toggleSkeletons( true ),
				success: ( response ) => this.render( response.data ),
				error: ( err ) => {
					console.error( 'LP Statistics users:', err );
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
			console.error( 'LP Statistics users: dashboard payload missing.' );
			data = { chart_data: {}, dashboard: {} };
		}

		const dashboard = data.dashboard || {};

		this.renderKpis( dashboard.kpis || {} );
		this.renderChart( data.chart_data || {} );
		this.renderFunnel( dashboard.funnel || {} );
		this.renderTables( dashboard );
	}

	renderKpis( kpis ) {
		Object.entries( LpStatsTabUsers.kpiCards ).forEach( ( [ key, selector ] ) => {
			const elCard = this.elContainer.querySelector( selector );
			const payload = { ...( kpis[ key ] || {} ) };

			if ( 'users_activated' === key ) {
				payload.subline = sprintfLite(
					getStatsI18n( 'newThisPeriod', '+%d this period' ),
					payload.new_in_period ?? 0
				);
			}
			if ( 'students' === key ) {
				payload.subline = sprintfLite(
					getStatsI18n( 'activeInPeriod', '%d active in this period' ),
					payload.active_in_period ?? 0
				);
			}
			if ( 'instructors' === key ) {
				payload.subline = `${ payload.active_in_period ?? 0 }/${
					payload.value ?? 0
				} ${ getStatsI18n( 'activeThisPeriod', 'active this period' ) }`;
			}
			if ( 'not_started' === key ) {
				payload.subline = getStatsI18n( 'afterEnrollment', 'After enrollment' );
			}
			if ( 'in_progress' === key ) {
				payload.subline = getStatsI18n( 'currentLearners', 'Current learners' );
			}
			if ( 'finished' === key && null != payload.completion_rate ) {
				payload.subline = sprintfLite(
					getStatsI18n( 'completionRateSub', '%s%% completion rate' ),
					payload.completion_rate
				);
			}

			renderKpi( elCard, payload );
		} );
	}

	renderChart( chartData ) {
		renderLineChart(
			LpStatsTabUsers.selectors.elChartCanvas,
			{
				labels: chartData.labels || [],
				datasets: [
					{
						label:
							chartData.line_label ||
							getStatsI18n( 'registeredUsers', 'Registered users' ),
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

	renderFunnel( funnel ) {
		const steps = [ 'registered', 'enrolled', 'started', 'completed', 'failed' ];
		let previous = null;

		steps.forEach( ( step ) => {
			const elStep = this.elContainer.querySelector(
				`${ LpStatsTabUsers.selectors.elFunnelStep }[data-step="${ step }"]`
			);
			if ( ! elStep ) {
				return;
			}

			const count = Number( funnel[ step ] ?? 0 );
			// 'failed' is an annotation on 'started', not the next narrowing step.
			let base = previous;
			if ( 'failed' === step ) {
				base = Number( funnel.started ?? 0 );
			} else if ( null === previous ) {
				base = count;
			}
			const width = base > 0 ? Math.min( 100, ( count / base ) * 100 ) : 0;

			elStep.querySelector( '.lp-stats-funnel__count' ).textContent =
				String( count );
			elStep.querySelector( '.lp-stats-funnel__bar' ).style.width = `${ width }%`;

			if ( 'failed' !== step ) {
				previous = count;
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

	studentStatusLabel( slug ) {
		const labels = {
			active: getStatsI18n( 'statusActive', 'Active' ),
			at_risk: getStatsI18n( 'statusAtRisk', 'At risk' ),
			idle: getStatsI18n( 'statusIdle', 'Idle' ),
		};

		return labels[ slug ] || String( slug || '' );
	}

	studentStatusBadge( slug ) {
		const badges = {
			active: 'green',
			at_risk: 'yellow',
			idle: 'grey',
		};

		return badges[ slug ] || '';
	}

	formatLastActive( value ) {
		if ( ! value ) {
			return '—';
		}

		const date = new Date( String( value ).replace( ' ', 'T' ) );
		if ( isNaN( date.getTime() ) ) {
			return '—';
		}

		try {
			const days = Math.round( ( date.getTime() - Date.now() ) / 86400000 );
			return new Intl.RelativeTimeFormat( undefined, { numeric: 'auto' } ).format(
				days,
				'day'
			);
		} catch {
			return date.toLocaleDateString();
		}
	}

	/**
	 * @param {boolean} withScore avg_score column only when the payload carries scores.
	 */
	topStudentsColumns( withScore = true ) {
		const columns = [
			{ key: 'name', label: getStatsI18n( 'student', 'Student' ) },
			{ key: 'enrolled', label: getStatsI18n( 'enrolled', 'Enrolled' ) },
			{ key: 'completed', label: getStatsI18n( 'completedLabel', 'Completed' ) },
		];

		if ( withScore ) {
			columns.push( {
				key: 'avg_score',
				label: getStatsI18n( 'avgScore', 'Quiz pass rate' ),
				format: ( value ) => ( null == value ? '—' : `${ value }%` ),
			} );
		}

		columns.push(
			{
				key: 'last_active',
				label: getStatsI18n( 'lastActive', 'Last active' ),
				format: ( value ) => this.formatLastActive( value ),
				csv: ( row ) => row.last_active || '',
			},
			{
				key: 'status',
				label: getStatsI18n( 'status', 'Status' ),
				format: ( value ) => this.studentStatusLabel( value ),
				badge: ( row ) => this.studentStatusBadge( row.status ),
				csv: ( row ) => row.status,
			}
		);

		return columns;
	}

	coursesByStudentsColumns() {
		return [
			{ key: 'name', label: getStatsI18n( 'course', 'Course' ) },
			{ key: 'enrolled', label: getStatsI18n( 'enrolled', 'Enrolled' ) },
			{ key: 'started', label: getStatsI18n( 'startedLabel', 'Started' ) },
			{ key: 'completed', label: getStatsI18n( 'completedLabel', 'Completed' ) },
			{
				key: 'completion_rate',
				label: getStatsI18n( 'completion', 'Completion' ),
				format: ( value ) => ( null == value ? '—' : `${ value }%` ),
				badge: ( row ) => this.completionBadge( row.completion_rate ),
			},
			{ key: 'active_7d', label: getStatsI18n( 'activeLast7dShort', 'Active 7d' ) },
		];
	}

	/**
	 * avg_score is null when quiz data is unavailable — hide the whole column.
	 *
	 * @param {Array} rows
	 */
	hasScores( rows = [] ) {
		return rows.some( ( row ) => null != row.avg_score );
	}

	renderTables( dashboard ) {
		const students = dashboard.top_students || [];
		this.tables[ 'top-students' ] = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabUsers.selectors.elTableTopStudents
			),
			this.topStudentsColumns( this.hasScores( students ) ),
			students
		);

		this.tables[ 'courses-by-students' ] = renderDataTable(
			this.elContainer.querySelector(
				LpStatsTabUsers.selectors.elTableCoursesByStudents
			),
			this.coursesByStudentsColumns(),
			dashboard.top_courses_by_students || []
		);
	}

	viewAllStudents( args ) {
		const btn = args.target.closest(
			LpStatsTabUsers.selectors.elBtnViewAllStudents
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		lpStatsReportModal.open( {
			report: 'top_students',
			title: getStatsI18n( 'topStudents', 'Top students' ),
			tableId: 'top-students',
		} );
	}

	viewAllCoursesByStudents( args ) {
		const btn = args.target.closest(
			LpStatsTabUsers.selectors.elBtnViewAllCourses
		);
		if ( ! btn || ! this.elContainer.contains( btn ) ) {
			return;
		}

		lpStatsReportModal.open( {
			report: 'courses_by_students',
			title: getStatsI18n(
				'topCoursesByStudents',
				'Top courses by students'
			),
			tableId: 'courses-by-students',
		} );
	}

	exportTables() {
		Object.entries( this.tables ).forEach( ( [ tableId, handle ] ) => {
			if ( handle && handle.rows.length ) {
				exportCsv(
					buildCsvFilename( 'users', tableId ),
					handle.columns,
					handle.rows
				);
			}
		} );
	}
}

export const lpStatsTabUsers = new LpStatsTabUsers();
