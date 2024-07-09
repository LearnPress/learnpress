import TomSelect from 'tom-select';
import { lpFetchAPI } from '../../utils.js';
import Api from '../../api.js';

export default function search_user() {
	const buildTomSelect = ( elTomSelect, options, fetchAPI ) => {
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
			load( keySearch, callback ) {
				fetchAPI( keySearch, callback );
			},
		};

		options = { ...optionDefault, ...options };

		return new TomSelect( elTomSelect, options );
	};

	const fetchOrderUsers = ( keySearch = '', callback, elTomSelect ) => {
		const url = Api.admin.apiSearchUsers;
		let dataUser;

		const tomOptions = {
			maxItems: null,
			maxOptions: 10,
			render: {
				item( data, escape ) {
					return (
						`<li data-id="${ data.value }">` +
						`<div class="item" data-ts-item="">${ data.text }</div>` +
						`<input type="hidden" name="order-customer[]" value="${ data.value }">` +
						'</li>'
					);
				},
			},
		};

		const listUserEl = document.querySelector( '#list-users' );
		if ( listUserEl && listUserEl.dataset.userId ) {
			dataUser = JSON.parse( listUserEl.dataset.userId );
			tomOptions.items = dataUser;
		}

		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( { search: keySearch } ),
		};

		lpFetchAPI( url, params, {
			success: ( response ) => {
				const options = response.data.map( ( item ) => {
					return {
						value: item.ID,
						text: `${ item.display_name } (#${ item.ID }) - ${ item.user_email }`,
					};
				} );

				tomOptions.options = options;

				// Set data users default first to Tom Select.
				if ( keySearch === '' ) {
					if ( listUserEl ) {
						const elTomSelectUser = buildTomSelect(
							listUserEl,
							tomOptions,
							fetchOrderUsers,
						);
					}
				}

				if ( 'function' === typeof callback ) {
					if ( callback.name === 'setupOptions' ) {
						elTomSelect.setupOptions( options );
					} else {
						callback( options );
					}
				}
			},
		} );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		const listUserEl = document.querySelector( '#list-users' );

		if ( listUserEl ) {
			fetchOrderUsers();
		}
	} );
}
