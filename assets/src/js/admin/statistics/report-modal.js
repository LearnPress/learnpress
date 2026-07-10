/**
 * Report popup controller — SweetAlert2, house pattern per ViewStudentsModal.
 *
 * open() receives a data-table handle and fires a SweetAlert popup whose body
 * comes from the lp-tmpl-stats-report-modal template. Search filters
 * client-side into a NEW array (source rows never mutated, reopening always
 * shows full data); export sends the currently filtered rows. Close button,
 * overlay click and Escape are SweetAlert2 defaults.
 *
 * @since 4.4.2
 * @version 1.1.0
 */

import SweetAlert from 'sweetalert2';
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { renderDataTable } from './data-table.js';
import { exportCsv, buildCsvFilename } from './csv.js';

export class LpStatsReportModal {
	static selectors = {
		template: '#lp-tmpl-stats-report-modal',
		elContainer: '.lp-stats-report-modal',
		elSearch: '.lp-stats-report-modal__search',
		elCount: '.lp-stats-report-modal__count',
		elExport: '.lp-stats-report-modal__export',
		elTable: '.lp-stats-report-modal__table',
	};

	constructor() {
		this.title = '';
		this.tableId = '';
		this.columns = [];
		this.allRows = [];
		this.filteredRows = [];
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
		this.debouncedSearch = lpUtils.debounce( () => this.applySearch(), 300 );

		lpUtils.eventHandlers( 'click', [
			{
				selector: LpStatsReportModal.selectors.elExport,
				class: this,
				callBack: this.exportFiltered.name,
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

	getModalHtml() {
		const template = document.querySelector(
			LpStatsReportModal.selectors.template
		);

		return template ? template.innerHTML : '';
	}

	/**
	 * @param {Object} report { title, tableId?, columns, rows } — a data-table handle.
	 */
	open( report = {} ) {
		const modalHtml = this.getModalHtml();
		if ( ! modalHtml ) {
			return;
		}

		this.init();

		this.title = report.title || '';
		this.tableId = report.tableId || this.title;
		this.columns = report.columns || [];
		this.allRows = report.rows || [];
		this.filteredRows = [ ...this.allRows ];

		SweetAlert.fire( {
			title: this.title,
			html: modalHtml,
			width: '80%',
			showConfirmButton: false,
			showCloseButton: true,
			didOpen: () => {
				this.renderRows();
			},
		} );
	}

	close() {
		SweetAlert.close();
	}

	isOpen() {
		return !! this.getModalContent();
	}

	onSearchInput() {
		this.debouncedSearch();
	}

	applySearch() {
		const elContent = this.getModalContent();
		if ( ! elContent ) {
			return;
		}

		const elSearch = elContent.querySelector(
			LpStatsReportModal.selectors.elSearch
		);
		const term = ( elSearch?.value || '' ).trim().toLowerCase();

		if ( ! term ) {
			this.filteredRows = [ ...this.allRows ];
		} else {
			this.filteredRows = this.allRows.filter( ( row ) =>
				this.columns.some( ( column ) => {
					const value = row[ column.key ];
					return (
						( 'string' === typeof value ||
							'number' === typeof value ) &&
						String( value ).toLowerCase().includes( term )
					);
				} )
			);
		}

		this.renderRows();
	}

	renderRows() {
		const elContent = this.getModalContent();
		if ( ! elContent ) {
			return;
		}

		renderDataTable(
			elContent.querySelector( LpStatsReportModal.selectors.elTable ),
			this.columns,
			this.filteredRows
		);

		const elCount = elContent.querySelector(
			LpStatsReportModal.selectors.elCount
		);
		if ( elCount ) {
			elCount.textContent = `${ this.filteredRows.length } / ${ this.allRows.length }`;
		}
	}

	exportFiltered() {
		if ( ! this.isOpen() || ! this.columns.length ) {
			return;
		}

		exportCsv(
			buildCsvFilename( 'report', this.tableId ),
			this.columns,
			this.filteredRows
		);
	}
}

export const lpStatsReportModal = new LpStatsReportModal();
