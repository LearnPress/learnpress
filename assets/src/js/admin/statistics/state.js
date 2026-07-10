/**
 * Statistics dashboard shared filter state.
 *
 * The singleton is the ONLY mutation path: modules call lpStatsState.set()
 * and every tab module re-renders on the 'lp-stats:filter-changed' event.
 * Tab-specific deep-link params (e.g. order_status) are read by their own
 * tab module — only the four global filters live here.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

export const LP_STATS_FILTER_CHANGED = 'lp-stats:filter-changed';
export const LP_STATS_EXPORT_CSV = 'lp-stats:export-csv';

export class LpStatsState {
	constructor() {
		const params = new URL( window.location.href ).searchParams;

		this.filters = {
			filtertype: params.get( 'filtertype' ) || 'today',
			date: params.get( 'date' ) || '',
			instructor_id: parseInt( params.get( 'instructor_id' ), 10 ) || 0,
			category_id: parseInt( params.get( 'category_id' ), 10 ) || 0,
		};
	}

	get() {
		return { ...this.filters };
	}

	set( partial = {} ) {
		this.filters = { ...this.filters, ...partial };

		document.dispatchEvent(
			new CustomEvent( LP_STATS_FILTER_CHANGED, { detail: this.get() } )
		);
	}
}

export const lpStatsState = new LpStatsState();
