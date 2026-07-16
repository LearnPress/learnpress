/**
 * Statistics dashboard fetch wrapper + escaping helper.
 *
 * Every statistics request goes through lpStatsFetch so the localized globals
 * (lpDataAdmin for REST root/nonce, lpAdminStatisticSettings for config) are
 * read in exactly one file, and lpFetchAPI's blind spots are normalized here:
 * it never rejects on HTTP error codes, so anything but status 'success'
 * is routed to the error callback.
 *
 * @since 4.4.2
 * @version 1.0.0
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import { lpStatsState, LP_STATS_RANGE_RESOLVED } from './state.js';

export const getStatsConfig = () => window.lpAdminStatisticSettings || {};

export const getStatsI18n = ( key, fallback = '' ) => {
	const { i18n = {} } = getStatsConfig();
	return i18n[ key ] || fallback;
};

/**
 * Fetch a statistics endpoint with the global filter state applied.
 *
 * @param {string} endpoint  Route below the statistics namespace, e.g. 'filter-options'.
 * @param {Object} extraArgs Query args merged over the state (tab-specific params).
 * @param {Object} functions { before, success, error, completed } — success only
 *                           fires on status 'success'; error receives an Error.
 */
export const lpStatsFetch = ( endpoint, extraArgs = {}, functions = {} ) => {
	const lpDataAdmin = window.lpDataAdmin || {};
	const restNamespace = getStatsConfig().restNamespace || 'lp/v1/statistics';
	const url = lpUtils.lpAddQueryArgs(
		`${ lpDataAdmin.lp_rest_url || '/wp-json/' }${ restNamespace }/${ endpoint }`,
		{ ...lpStatsState.get(), ...extraArgs }
	);

	const onError =
		'function' === typeof functions.error
			? functions.error
			: ( err ) => console.error( 'LP Statistics:', err );

	lpUtils.lpFetchAPI(
		url,
		{ headers: { 'X-WP-Nonce': lpDataAdmin.nonce || '' } },
		{
			...functions,
			success: ( response ) => {
				if ( response && 'success' === response.status ) {
					// Broadcast the server-resolved range so the filter bar can
					// reconcile its toggle label ( fixes the past-midnight case ).
					const range = response.data && response.data.range;
					if ( range && range.label ) {
						document.dispatchEvent(
							new CustomEvent( LP_STATS_RANGE_RESOLVED, { detail: range } )
						);
					}
					if ( 'function' === typeof functions.success ) {
						functions.success( response );
					}
				} else {
					onError(
						new Error(
							( response && response.message ) ||
								getStatsI18n( 'loadError', 'Request failed.' )
						)
					);
				}
			},
			error: onError,
		}
	);
};
