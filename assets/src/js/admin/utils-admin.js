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
			plugins: {
				remove_button: {
					title: 'Remove this item',
				},
			},
			onInitialize() {

			},
		};

		if ( fetchAPI ) {
			optionDefault.load = ( keySearch, callbackTom ) => {
				const selectedOptions = Array.from( elTomSelect.selectedOptions );
				const selectedValues = selectedOptions.map( ( option ) => option.value );
				dataSend.id_not_in = selectedValues.join( ',' );

				fetchAPI(
					keySearch,
					dataSend,
					AdminUtilsFunctions.callBackTomSelectSearchAPI( callbackTom, callBackHandleData )
				);
			};
		}

		options = { ...optionDefault, ...options };
		if ( options?.options?.length > 20 ) {
			const chunkSize = 20;
			const length = options.options.length;
			let i = 0;
			const optionsSlice = options.options.slice( i, chunkSize );
			const chunkedOptions = { ...options };
			chunkedOptions.options = optionsSlice;

			const tomSelect = new TomSelect( elTomSelect, chunkedOptions );
			i += chunkSize;

			const interval = setInterval( () => {
				if ( i > ( length - 1 ) ) {
					clearInterval( interval );
				}

				let optionsSlice = { ...options };
				optionsSlice = options.options.slice( i, i + chunkSize );
				i += chunkSize;
				tomSelect.addOptions( optionsSlice );
				tomSelect.setValue( options.items );
			}, 200 );

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

