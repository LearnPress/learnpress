/**
 * Statistics dashboard shared filter state.
 *
 * The singleton is the ONLY mutation path: modules call lpStatsState.set()
 * and every tab module re-renders on the 'lp-stats:filter-changed' event.
 * Tab-specific deep-link params (e.g. order_status) are read by their own
 * tab module — only the four global filters live here.
 *
 * On every set() the five filters are written back to the URL (replaceState,
 * no history spam) so the current view is always copy/paste shareable; other
 * query params (page, tab, order_status, …) are preserved untouched.
 *
 * @since 4.4.2
 * @version 1.2.0
 */

export const LP_STATS_FILTER_CHANGED = 'lp-stats:filter-changed';
export const LP_STATS_EXPORT_CSV = 'lp-stats:export-csv';
// Server-resolved range echoed by a stats payload ( data.range ). Carries the
// authoritative toggle label so the filter bar can reconcile its optimistic one.
export const LP_STATS_RANGE_RESOLVED = 'lp-stats:range-resolved';

const COMPARE_DEFAULT = 'previous_period';

export class LpStatsState {
	constructor() {
		const params = new URL( window.location.href ).searchParams;

		this.filters = {
			filtertype: params.get( 'filtertype' ) || 'today',
			date: params.get( 'date' ) || '',
			compare:
				'previous_year' === params.get( 'compare' )
					? 'previous_year'
					: COMPARE_DEFAULT,
			instructor_id: parseInt( params.get( 'instructor_id' ), 10 ) || 0,
			category_id: parseInt( params.get( 'category_id' ), 10 ) || 0,
		};
	}

	get() {
		return { ...this.filters };
	}

	set( partial = {} ) {
		this.filters = { ...this.filters, ...partial };

		this.syncUrl();

		document.dispatchEvent(
			new CustomEvent( LP_STATS_FILTER_CHANGED, { detail: this.get() } )
		);
	}

	/**
	 * Reflect the current filters in the URL without pushing a history entry.
	 * Defaults (today / empty date / id 0) are dropped to keep URLs clean;
	 * unrelated params (page, tab, order_status, …) are left as-is.
	 */
	syncUrl() {
		if ( ! window.history || typeof window.history.replaceState !== 'function' ) {
			return;
		}

		const url = new URL( window.location.href );
		const params = url.searchParams;
		const { filtertype, date, compare, instructor_id: instructorId, category_id: categoryId } = this.filters;

		this.writeParam( params, 'filtertype', filtertype && filtertype !== 'today' ? filtertype : '' );
		this.writeParam( params, 'date', date );
		this.writeParam( params, 'compare', compare && compare !== COMPARE_DEFAULT ? compare : '' );
		this.writeParam( params, 'instructor_id', instructorId > 0 ? String( instructorId ) : '' );
		this.writeParam( params, 'category_id', categoryId > 0 ? String( categoryId ) : '' );

		window.history.replaceState( null, '', url.toString() );
	}

	/**
	 * Set the param when a truthy value is given, otherwise remove it.
	 */
	writeParam( params, key, value ) {
		if ( value ) {
			params.set( key, value );
		} else {
			params.delete( key );
		}
	}
}

export const lpStatsState = new LpStatsState();
