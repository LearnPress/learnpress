/**
 * Library run on Admin
 *
 * @since 4.2.6.9
 * @version 1.0.0
 */
import * as Utils from '../utils.js';
import TomSelect from 'tom-select';
import Api from '../api.js';

const AdminUtilsFunctions = {
	buildTomSelect( elTomSelect, options, fetchAPI, callBackHandleData ) {
		if ( ! elTomSelect ) {
			return;
		}

		const optionDefault = {
			options: [],
			plugins: {
				remove_button: {
					title: 'Remove this item',
				},
			},
			load( keySearch, callbackTom ) {
				fetchAPI(
					keySearch,
					AdminUtilsFunctions.callBackTomSelectSearchAPI( callbackTom, elTomSelect, callBackHandleData )
				);
			},
		};

		options = { ...optionDefault, ...options };

		return new TomSelect( elTomSelect, options );
	},
	callBackTomSelectSearchAPI( callbackTom, elTomSelect, callBackHandleData ) {
		return {
			success: ( response ) => {
				const options = callBackHandleData.success( response );
				callbackTom( options );
			},
		};
	},
	fetchCourses( keySearch = '', callback ) {
		const url = Api.admin.apiSearchCourses;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( { c_search: keySearch } ),
		};

		Utils.lpFetchAPI( url, params, callback );
	},
	fetchUsers( keySearch = '', callback ) {
		const url = Api.admin.apiSearchUsers;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( { search: keySearch } ),
		};

		Utils.lpFetchAPI( url, params, callback );
	},
};

export { Utils, AdminUtilsFunctions, Api };

