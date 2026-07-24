/**
 * KPI card renderer — pure DOM fill, no fetch.
 *
 * Payload shape comes from PeriodHelper::kpi_payload() on the server:
 * { value, prev_value, change_pct } plus optional client extras
 * { formatted, subline }. change_pct null → delta hidden (no wrong deltas).
 *
 * @since 4.4.2
 * @version 1.0.0
 */

/**
 * @param {Element} elCard  The .lp-kpi-card root.
 * @param {Object}  payload KPI payload.
 */
export const renderKpi = ( elCard, payload = {} ) => {
	if ( ! elCard ) {
		return;
	}

	const elValue = elCard.querySelector( '.lp-kpi-value' );
	const elDelta = elCard.querySelector( '.lp-kpi-delta' );
	const elSubline = elCard.querySelector( '.lp-kpi-subline' );

	if ( elValue ) {
		const value = payload.formatted ?? payload.value;
		elValue.textContent = null == value || '' === value ? '–' : String( value );
	}

	if ( elDelta ) {
		elDelta.classList.remove( 'is-up', 'is-down' );

		if ( 'number' === typeof payload.change_pct ) {
			const isUp = payload.change_pct >= 0;
			elDelta.classList.add( isUp ? 'is-up' : 'is-down' );
			elDelta.textContent = `${ isUp ? '▲' : '▼' } ${ Math.abs(
				payload.change_pct
			) }%`;
		} else {
			elDelta.textContent = '';
		}
	}

	if ( elSubline ) {
		elSubline.textContent = payload.subline ?? '';
	}
};
