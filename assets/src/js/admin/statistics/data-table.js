/**
 * Data table renderer — createElement/textContent only, no innerHTML.
 *
 * Emits the plugin's shared table markup ( .lp-table-wrap > table.lp-list-table,
 * per TableListTemplate ) so the dashboard widgets match every other LearnPress
 * table. The extra lp-stats-table class carries the stats-only behaviours
 * ( row hover/highlight, clickable performance rows, empty state ).
 *
 * Column definition:
 * { key, label,
 *   format?: ( value, row ) => string|Node  // Node for links etc.
 *   badge?:  ( row ) => 'green'|'yellow'|'red'|''  // wraps the cell value
 *   csv?:    ( row ) => string  // plain value for CSV export (Nodes can't export)
 * }
 *
 * @since 4.4.2
 * @version 1.1.0
 */

import { getStatsI18n } from './api.js';

/**
 * @param {Element} elContainer Container emptied and refilled.
 * @param {Array}   columns     Column definitions.
 * @param {Array}   rows        Row objects keyed by column key.
 * @param {Object}  options     { emptyText?: string }.
 * @return {Object} { columns, rows } handle for csv/modal reuse.
 */
export const renderDataTable = (
	elContainer,
	columns = [],
	rows = [],
	options = {}
) => {
	if ( ! elContainer ) {
		return { columns, rows };
	}

	elContainer.textContent = '';

	const wrap = document.createElement( 'div' );
	wrap.className = 'lp-table-wrap';

	const table = document.createElement( 'table' );
	table.className = 'lp-list-table lp-stats-table';

	const thead = document.createElement( 'thead' );
	const headRow = document.createElement( 'tr' );
	columns.forEach( ( column ) => {
		const th = document.createElement( 'th' );
		th.textContent = column.label ?? '';
		headRow.appendChild( th );
	} );
	thead.appendChild( headRow );
	table.appendChild( thead );

	const tbody = document.createElement( 'tbody' );

	if ( ! rows.length ) {
		const tr = document.createElement( 'tr' );
		const td = document.createElement( 'td' );
		td.colSpan = columns.length || 1;
		td.className = 'lp-stats-table__empty';
		td.textContent =
			options.emptyText || getStatsI18n( 'noData', 'No data for this period.' );
		tr.appendChild( td );
		tbody.appendChild( tr );
	} else {
		rows.forEach( ( row ) => {
			const tr = document.createElement( 'tr' );
			columns.forEach( ( column ) => {
				const td = document.createElement( 'td' );
				const raw = row[ column.key ];
				const output =
					'function' === typeof column.format
						? column.format( raw, row )
						: raw;

				let cellNode;
				if ( output instanceof Node ) {
					cellNode = output;
				} else {
					cellNode = document.createTextNode(
						null == output ? '' : String( output )
					);
				}

				const badgeColor =
					'function' === typeof column.badge ? column.badge( row ) : '';
				if ( badgeColor ) {
					const badge = document.createElement( 'span' );
					badge.className = `lp-badge lp-badge--${ badgeColor }`;
					badge.appendChild( cellNode );
					td.appendChild( badge );
				} else {
					td.appendChild( cellNode );
				}

				tr.appendChild( td );
			} );
			tbody.appendChild( tr );
		} );
	}

	table.appendChild( tbody );
	wrap.appendChild( table );
	elContainer.appendChild( wrap );

	return { columns, rows };
};
