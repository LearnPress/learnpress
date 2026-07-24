/**
 * Client-side CSV export ( Blob + BOM ).
 *
 * RFC-4180 escaping plus a CSV-injection guard: values starting with
 * = + - @ get a leading apostrophe so Excel never executes them.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import { lpStatsState } from './state.js';

const sanitizeSegment = ( segment ) => {
	const clean = String( segment ?? '' )
		.toLowerCase()
		.replace( /[^a-z0-9-]+/g, '-' )
		.replace( /^-+|-+$/g, '' );

	return clean || 'data';
};

/**
 * `learnpress-{tab}-{table}-{filtertype}.csv`, all segments sanitized.
 *
 * @param {string} tab
 * @param {string} table
 * @return {string} Filename.
 */
export const buildCsvFilename = ( tab, table ) => {
	const { filtertype } = lpStatsState.get();

	return `learnpress-${ sanitizeSegment( tab ) }-${ sanitizeSegment(
		table
	) }-${ sanitizeSegment( filtertype ) }.csv`;
};

const escapeCell = ( value ) => {
	let str = null == value ? '' : String( value );

	if ( /^[=+\-@]/.test( str ) ) {
		str = `'${ str }`;
	}

	if ( /[",\n\r]/.test( str ) ) {
		str = `"${ str.replace( /"/g, '""' ) }"`;
	}

	return str;
};

/**
 * Build and download a CSV from a data-table handle.
 *
 * @param {string} filename Full filename (see buildCsvFilename).
 * @param {Array}  columns  Column definitions ({ key, label, csv? }).
 * @param {Array}  rows     Row objects.
 */
export const exportCsv = ( filename, columns = [], rows = [] ) => {
	if ( ! columns.length ) {
		return;
	}

	const lines = [
		columns.map( ( column ) => escapeCell( column.label ) ).join( ',' ),
	];

	rows.forEach( ( row ) => {
		lines.push(
			columns
				.map( ( column ) => {
					const value =
						'function' === typeof column.csv
							? column.csv( row )
							: row[ column.key ];

					return escapeCell( value );
				} )
				.join( ',' )
		);
	} );

	// BOM keeps Excel reading UTF-8 (Vietnamese titles etc.).
	const blob = new Blob( [ '\u{FEFF}' + lines.join( '\r\n' ) ], {
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
};
