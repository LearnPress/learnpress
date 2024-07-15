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
	buildTomSelect( elTomSelect, options, fetchAPI, dataSend, callBackHandleData ) {
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
					dataSend,
					AdminUtilsFunctions.callBackTomSelectSearchAPI( callbackTom, callBackHandleData )
				);
			},
		};

		options = { ...optionDefault, ...options };

		return new TomSelect( elTomSelect, options );
	},
	callBackTomSelectSearchAPI( callbackTom, callBackHandleData ) {
		return {
			success: ( response ) => {
				const options = callBackHandleData.success( response );
				callbackTom( options );
			},
		};
	},
	fetchCourses( keySearch = '', dataSend = {}, callback ) {
		const url = Api.admin.apiSearchCourses;
		dataSend.c_search = keySearch;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( dataSend ),
		};

		Utils.lpFetchAPI( url, params, callback );
	},
	fetchUsers( keySearch = '', dataSend = {}, callback ) {
		const url = Api.admin.apiSearchUsers;
		dataSend.search = keySearch;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( dataSend ),
		};

		Utils.lpFetchAPI( url, params, callback );
	},
};

export { Utils, AdminUtilsFunctions, Api };

