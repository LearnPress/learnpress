/**
 * Report popup controller — SweetAlert2 shell over a server-rendered table.
 *
 * The table is built in PHP ( AdminStatisticsReportTable ) via TableListTemplate
 * and delivered through TemplateAJAX: open() injects the popup body, points the
 * .lp-target at the requested report + current filters, and triggers loadAJAX to
 * fetch it. Pagination is handled by loadAJAX.js ( .page-numbers ). Search
 * re-queries the server ( debounced, resets to page 1 ); export asks the server
 * for the full CSV and downloads it.
 *
 * @since 4.4.2
 * @version 3.0.0
 */

import SweetAlert from 'sweetalert2';
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { lpStatsState } from './state.js';
import { getStatsI18n } from './api.js';

export class LpStatsReportModal {
	static selectors = {
		template: '#lp-tmpl-stats-report-modal',
		elContainer: '.lp-stats-report-modal',
		elSearch: '.lp-stats-report-modal__search',
		elExport: '.lp-stats-report-modal__export',
		elTarget: '.lp-target',
	};

	constructor() {
		this.title = '';
		this.tableId = '';
	}

	init() {
		this.events();
	}

	events() {
		if ( LpStatsReportModal._loadedEvents ) {
			return;
		}
		LpStatsReportModal._loadedEvents = this;

		// Debounced ONCE here — never create a debounce inside a handler.
		this.debouncedSearch = lpUtils.debounce( () => this.applySearch(), 400 );

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsReportModal.selectors.elExport,
				class: this,
				callBack: this.exportCsv.name,
			},
		] );

		lpUtils.eventHandlers( 'input', [
			{
				selector: LpStatsReportModal.selectors.elSearch,
				class: this,
				callBack: this.onSearchInput.name,
			},
		] );
	}

	/**
	 * @return {Object|null} window.lpAJAXG when it exposes the API we need.
	 */
	getAjaxHandle() {
		const handle = window.lpAJAXG;
		if (
			! handle ||
			'function' !== typeof handle.getDataSetCurrent ||
			'function' !== typeof handle.setDataSetCurrent ||
			'function' !== typeof handle.fetchAJAX ||
			'function' !== typeof handle.showHideLoading
		) {
			return null;
		}

		return handle;
	}

	getModalPopup() {
		return SweetAlert.getPopup ? SweetAlert.getPopup() : null;
	}

	getModalContent() {
		const popup = this.getModalPopup();
		if ( ! popup ) {
			return null;
		}

		return popup.querySelector( LpStatsReportModal.selectors.elContainer );
	}

	getTarget() {
		const content = this.getModalContent();
		return content
			? content.querySelector( LpStatsReportModal.selectors.elTarget )
			: null;
	}

	getModalHtml() {
		const template = document.querySelector(
			LpStatsReportModal.selectors.template
		);

		return template ? template.innerHTML : '';
	}

	isOpen() {
		return !! this.getModalContent();
	}

	/**
	 * @param {Object} report { report, title, tableId?, orderStatus? }
	 *   - report:      server report slug ( e.g. 'top_courses' ).
	 *   - orderStatus: cancelled/failed deep-link for the exceptions report.
	 */
	open( report = {} ) {
		const modalHtml = this.getModalHtml();
		if ( ! modalHtml || ! report.report ) {
			return;
		}

		this.init();

		this.title = report.title || '';
		this.tableId = report.tableId || report.report;

		SweetAlert.fire( {
			title: this.title,
			html: modalHtml,
			// Large by default; the custom class lets the SCSS push it (near) full size.
			width: '100%',
			customClass: { popup: 'lp-stats-report-popup' },
			showConfirmButton: false,
			showCloseButton: true,
			didOpen: () => this.loadReport( report ),
		} );
	}

	close() {
		SweetAlert.close();
	}

	/**
	 * Seed the .lp-target with report + current filters and fetch page 1.
	 *
	 * @param {Object} report
	 */
	loadReport( report ) {
		const target = this.getTarget();
		const handle = this.getAjaxHandle();
		if ( ! target || ! handle ) {
			if ( target ) {
				target.innerHTML = getStatsI18n( 'loadError', 'Request failed.' );
			}
			return;
		}

		const dataSend = handle.getDataSetCurrent( target );
		dataSend.args = {
			...( dataSend.args || {} ),
			...lpStatsState.get(),
			report: report.report,
			search: '',
			paged: 1,
			// Report-specific args ( e.g. instructor_id ) win over the global filters.
			...( report.args || {} ),
		};
		if ( report.orderStatus ) {
			dataSend.args.order_status = report.orderStatus;
		}
		handle.setDataSetCurrent( target, dataSend );

		this.reloadTarget( target, dataSend );
	}

	onSearchInput() {
		this.debouncedSearch();
	}

	applySearch() {
		const content = this.getModalContent();
		const target = this.getTarget();
		const handle = this.getAjaxHandle();
		if ( ! content || ! target || ! handle ) {
			return;
		}

		const elSearch = content.querySelector(
			LpStatsReportModal.selectors.elSearch
		);
		const dataSend = handle.getDataSetCurrent( target );
		dataSend.args = dataSend.args || {};
		dataSend.args.search = ( elSearch?.value || '' ).trim();
		dataSend.args.paged = 1;
		handle.setDataSetCurrent( target, dataSend );

		this.reloadTarget( target, dataSend );
	}

	/**
	 * Loading indicator + AJAX fetch, swapping the target's innerHTML.
	 *
	 * @param {Element} target
	 * @param {Object}  dataSend
	 */
	reloadTarget( target, dataSend ) {
		const handle = this.getAjaxHandle();
		if ( ! handle ) {
			return;
		}

		handle.showHideLoading( target, 1 );

		handle.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { status, message, data } = response;
				if ( 'success' === status ) {
					target.innerHTML = data.content || '';
				} else {
					target.innerHTML =
						message || getStatsI18n( 'loadError', 'Request failed.' );
				}
			},
			error: ( err ) => {
				// eslint-disable-next-line no-console
				console.error( 'LP Statistics report:', err );
			},
			completed: () => handle.showHideLoading( target, 0 ),
		} );
	}

	/**
	 * Ask the server for the full ( capped ) CSV and download it.
	 */
	exportCsv( args ) {
		const content = this.getModalContent();
		const target = this.getTarget();
		const handle = this.getAjaxHandle();
		if ( ! content || ! target || ! handle ) {
			return;
		}

		const btn = args?.target?.closest( LpStatsReportModal.selectors.elExport );
		if ( ! btn || btn.classList.contains( 'loading' ) ) {
			return;
		}

		// Clone the current request but hit the CSV callback.
		const current = handle.getDataSetCurrent( target );
		const dataSend = {
			...current,
			args: { ...( current.args || {} ) },
			callback: { ...( current.callback || {} ), method: 'render_report_csv' },
		};

		lpUtils.lpSetLoadingEl( btn, 1 );

		handle.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { status, data } = response;
				if ( 'success' === status && data && data.csv ) {
					this.download(
						data.filename || 'learnpress-report.csv',
						data.csv
					);
				}
			},
			error: ( err ) => {
				// eslint-disable-next-line no-console
				console.error( 'LP Statistics export:', err );
			},
			completed: () => lpUtils.lpSetLoadingEl( btn, 0 ),
		} );
	}

	/**
	 * @param {string} filename
	 * @param {string} csv
	 */
	download( filename, csv ) {
		// BOM keeps Excel reading UTF-8 (Vietnamese titles etc.).
		const blob = new Blob( [ '\u{FEFF}' + csv ], {
			type: 'text/csv;charset=utf-8;',
		} );
		const url = URL.createObjectURL( blob );
		const link = document.createElement( 'a' );
		link.href = url;
		link.download = filename;
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
		URL.revokeObjectURL( url );
	}
}

export const lpStatsReportModal = new LpStatsReportModal();
