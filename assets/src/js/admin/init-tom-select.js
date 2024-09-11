import { AdminUtilsFunctions, Api, Utils } from './utils-admin.js';

/**
 * Handle data response from API for tom-select
 *
 * @param {*} response
 * @param {*} tomSelectEl
 * @param     dataStruct
 * @param     fetchAPI
 * @param     customOptions
 * @param {*} callBack
 */
const handleResponse = ( response, tomSelectEl, dataStruct, fetchAPI, customOptions = {}, callBack ) => {
	if ( ! response || ! tomSelectEl || ! dataStruct || ! fetchAPI || ! callBack ) {
		return;
	}

	//Function format render data
	const getTextOption = ( data ) => {
		if ( ! dataStruct.keyGetValue?.text || ! dataStruct.keyGetValue.key_render ) {
			return;
		}

		let text = dataStruct.keyGetValue.text;
		for ( const [ key, value ] of Object.entries( dataStruct.keyGetValue.key_render ) ) {
			text = text.replace( new RegExp( `{{${ value }}}`, 'g' ), data[ value ] );
		}
		return text;
	};

	// Get default item tom-select
	const defaultIds = tomSelectEl.dataset?.saved ? JSON.parse( tomSelectEl.dataset.saved ) : 0;
	let options = [];

	// Format response data set option tom-select
	if ( response.data[ dataStruct.dataType ].length > 0 ) {
		options = response.data[ dataStruct.dataType ].map( ( item ) => ( {
			value: item[ dataStruct.keyGetValue.value ],
			text: getTextOption( item ),
		} ) );
	}

	// Setting option tom-select
	const settingOption = {
		items: defaultIds,
		render: {
			item( data, escape ) {
				return `` +
					`<li data-id="${ data.value }">
						<div class="item">${ data.text }</div>
					</li>`;
			},
		},
		onChange: ( data ) => {
			if ( data.length < 1 ) {
				tomSelectEl.value = '';
			}
		},
		...customOptions,
		options,
	};

	if ( null != tomSelectEl.tomSelectInstance ) {
		tomSelectEl.tomSelectInstance.addOptions( options );
		return options;
	}

	tomSelectEl.tomSelectInstance = AdminUtilsFunctions.buildTomSelect(
		tomSelectEl,
		settingOption,
		fetchAPI,
		{},
		callBack
	);

	return options;
};

//Init Tom-select with available options
const initTomSelectWithOption = ( tomSelectEl, settingTomSelect = {} ) => {
	if ( ! tomSelectEl ) {
		return null;
	}

	if ( null != tomSelectEl.tomSelectInstance ) {
		return null;
	}

	tomSelectEl.tomSelectInstance = AdminUtilsFunctions.buildTomSelect( tomSelectEl, settingTomSelect );
};

// Init Tom-select
const initTomSelect = ( tomSelectEl, customOptions = {}, customParams = {} ) => {
	if ( ! tomSelectEl ) {
		return;
	}

	if ( tomSelectEl.classList.contains( 'loaded' ) ) {
		return;
	} else {
		tomSelectEl.classList.add( 'loaded' );
	}

	const defaultIds = tomSelectEl.dataset?.saved ? JSON.parse( tomSelectEl.dataset.saved ) : 0;
	const dataStruct = tomSelectEl?.dataset?.struct ? JSON.parse( tomSelectEl.dataset.struct ) : '';

	if ( ! dataStruct ) {
		initTomSelectWithOption( tomSelectEl );
		return;
	}

	const getParentElByTagName = ( tag, el ) => {
		const newEl = el.parentElement;

		if ( newEl.tagName.toLowerCase() === tag ) {
			return newEl;
		}

		if ( newEl.tagName.toLowerCase() === 'html' ) {
			return false;
		}

		return getParentElByTagName( tag, newEl );
	};

	const formParent = getParentElByTagName( 'form', tomSelectEl );

	if ( formParent ) {
		const elInput = formParent.querySelector( 'input[name="' + tomSelectEl.getAttribute( 'name' ) + '"]' );
		if ( elInput ) {
			elInput.remove();
		}
	}

	const dataSendApi = dataStruct.dataSendApi ?? '';
	const urlApi = dataStruct.urlApi ?? '';

	const settingTomSelect = {
		...dataStruct.setting,
		...customOptions,
	};

	if ( ! urlApi ) {
		initTomSelectWithOption( tomSelectEl, settingTomSelect );
		return;
	}

	const fetchFunction = ( keySearch = '', customParams, callback ) => {
		const url = urlApi;
		const dataSend = { current_ids: defaultIds, ...dataSendApi, ...customParams };
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
	};

	const callBackApi = {
		success: ( response ) => {
			handleResponse( response, tomSelectEl, dataStruct, fetchFunction, settingTomSelect, callBackApi );
		},
	};

	fetchFunction( '', customParams, callBackApi );
};

// Init Tom-select user in admin
const searchUserOnListPost = () => {
	if ( lpDataAdmin.show_search_author_field === '0' ) {
		return;
	}

	const elPostFilter = document.querySelector( '#posts-filter' );
	if ( ! elPostFilter ) {
		return;
	}

	let elSearchPost = elPostFilter.querySelector( '.search-box' );
	if ( ! elSearchPost ) {
		elPostFilter.insertAdjacentHTML( 'afterbegin', lpDataAdmin.show_search_author_field );
		elSearchPost = elPostFilter.querySelector( '.search-box' );
	}

	if ( ! elSearchPost ) {
		return;
	}

	const createSelectUserHtml = () => {
		let defaultId = '';
		const authorIdFilter = lpDataAdmin.urlParams.author;
		if ( authorIdFilter ) {
			defaultId = JSON.stringify( authorIdFilter );
		}
		const dataStruct = {
			urlApi: Api.admin.apiSearchUsers,
			dataType: 'users',
			keyGetValue: {
				value: 'ID',
				text: '{{display_name}}(#{{ID}}) - {{user_email}}',
				key_render: {
					display_name: 'display_name',
					user_email: 'user_email',
					ID: 'ID',
				},
			},
			setting: {
				placeholder: 'Choose user',
			},
		};

		const dataStructJson = JSON.stringify( dataStruct );

		const htmlSelectUser = `` +
			`<select data-struct='${ dataStructJson }' style='display:none;' data-saved='${ defaultId }'
					id="author" name="author" class="select lp-tom-select">` +
			`</select>`;

		const elInputSearch = elSearchPost.querySelector( 'input[name="s"]' );
		if ( elInputSearch ) {
			elInputSearch.insertAdjacentHTML( 'afterend', htmlSelectUser );
		}

		// Remove input hide default of WP.
		const elInputAuthor = elPostFilter.querySelector( 'input[name="author"]' );
		if ( elInputAuthor ) {
			elInputAuthor.remove();
		}
	};
	createSelectUserHtml();
};

const initElsTomSelect = () => {
	const tomSelectEls = document.querySelectorAll( 'select.lp-tom-select:not(.loaded)' );

	if ( tomSelectEls.length ) {
		tomSelectEls.forEach( ( tomSelectEl ) => {
			// Not build elements tom-select in Widget left classic of WordPress.
			if ( tomSelectEl.closest( '.widget-liquid-left' ) ) {
				return;
			}
			initTomSelect( tomSelectEl );
		} );
	}
};

export {
	initTomSelect,
	searchUserOnListPost,
	initElsTomSelect,
};
