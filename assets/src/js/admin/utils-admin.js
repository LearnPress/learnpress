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
		if ( options.options.length > 20 ) {
			const currentIds = dataSend?.current_ids ? dataSend?.current_ids : '';
			const chunkSize = 20;
			const chunkedOptions = [];

			for ( let i = 0; i < options.options.length; i += chunkSize ) {
				chunkedOptions.push( options.options.slice( i, i + chunkSize ) );
			}

			options.options = chunkedOptions[ 0 ];
			const tomSelect = new TomSelect( elTomSelect, options );

			for ( let i = 0; i < chunkedOptions.length; i++ ) {
				setTimeout( () => {
					chunkedOptions[ i ].forEach( ( option ) => {
						tomSelect.addOption( option );
					} );

					if ( i === chunkedOptions.length - 1 && currentIds ) {
						tomSelect.setValue( currentIds.split( ',' ) );
					}
				}, 200 * i );
			}

			return tomSelect;
		}

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

