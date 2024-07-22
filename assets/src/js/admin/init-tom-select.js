import { AdminUtilsFunctions, Api, Utils } from './utils-admin.js';

// Process data from api and add to option tom-select
/**
 *
 * @param {*} response
 * @param {*} tomSelectEl
 * @param {*} dataType
 * @param {*} callBack
 * @param     customOptions
 * @return
 */
const handleResponse = ( response, tomSelectEl, dataStruct, fetchAPI, customOptions = {}, callBack ) => {
	if ( ! response || ! tomSelectEl || ! callBack ) {
		return;
	}

	//Function format render data
	const getTextOption = ( data ) => {
		let text = dataStruct.keyGetValue.text;
		for ( const [ key, value ] of Object.entries( dataStruct.keyGetValue.key_render ) ) {
			text = text.replace( new RegExp( `{{${ value }}}`, 'g' ), data[ value ] );
		}
		return text;
	};

	// Get default item tom-select
	const defaultIds = dataStruct.currentIds || 0;
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
				if ( tomSelectEl.hasAttribute( 'multiple' ) ) {
					return `<li data-id="${ data.value }"><div class="item">${ data.text } multiple</div>
					<input type="hidden" name="${ tomSelectEl.getAttribute( 'name' ) }" value="${ data.value }">
					</li>`;
				}
				return `<li data-id="${ data.value }">
				<div class="item">${ data.text }</div>
				</li>`;
			},
		},
		onChange: ( data ) => {
			if ( tomSelectEl.hasAttribute( 'multiple' ) ) {
				tomSelectEl.value = data.join( ',' );
			} else {
				tomSelectEl.value = data;
			}
		},
		...customOptions,
		options,
	};

	if ( null != tomSelectEl.tomSelectInstance ) {
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

// Init Tom-select
const initTomSelect = ( tomSelectEl, customOptions = {}, customParams = {} ) => {
	if ( ! tomSelectEl ) {
		return;
	}

	const dataStruct = tomSelectEl?.dataset?.struct ? JSON.parse( tomSelectEl.dataset.struct ) : '';

	if ( ! dataStruct ) {
		return;
	}

	const elInput = document.querySelector( 'input[name="' + tomSelectEl.getAttribute( 'name' ) + '"]' );
	if ( elInput ) {
		elInput.remove();
	}

	const dataSendApi = dataStruct.dataSendApi;
	const urlApi = dataStruct.urlApi;

	const settingTomSelect = {
		...dataStruct.setting,
		...customOptions,
	};

	const fetchFunction = ( keySearch = '', customParams, callback ) => {
		const url = urlApi;
		const dataSend = { ...dataSendApi, ...customParams };
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

	let createSelectUser;
	const createSelectUserHtml = () => {
		createSelectUser = document.createElement( 'select' );
		createSelectUser.setAttribute( 'name', 'author' );
		createSelectUser.setAttribute( 'class', 'lp-tom-select' );
		const elInputSearch = elSearchPost.querySelector( 'input[name="author"]' );
		createSelectUser.style.display = 'none';
		if ( elInputSearch ) {
			elInputSearch.insertAdjacentElement( 'afterend', createSelectUser );
		}

		// Remove input hide default of WP.
		const elInputAuthor = elPostFilter.querySelector( 'input[name="author"]' );
		if ( elInputAuthor ) {
			elInputAuthor.remove();
		}
	};

	const tomSearchUser = () => {
		let defaultId;
		const selectAuthor = document.querySelector( `select[name="author"]` );
		if ( ! selectAuthor ) {
			return;
		}

		const authorIdFilter = lpDataAdmin.urlParams.author;
		if ( authorIdFilter ) {
			defaultId = authorIdFilter;
		}

		const customOptions = {
			items: defaultId,
			placeholder: 'Chose user',
		};

		initTomSelect( selectAuthor, customOptions, { current_ids: defaultId } );
	};

	createSelectUserHtml();
	// tomSearchUser();
};

// Init Tom-select author in course
const selectAuthorCourse = () => {
	const selectAuthorCourseEl = document.querySelector(
		'select#post_author',
	);

	if ( ! selectAuthorCourseEl ) {
		return;
	}

	const roleSearch = 'administrator,lp_teacher';
	const authorInputEl = document.querySelector( 'input[name="post_author"]' );
	const defaultId = authorInputEl?.value ? authorInputEl.value : '';
	const customParams = { role_in: roleSearch, current_ids: defaultId };
	const customOptions = {
		onItemAdd: ( data, item ) => {
			authorInputEl.setAttribute( 'value', data );
		},
		onItemRemove: ( data, item ) => {
			authorInputEl.setAttribute( 'value', defaultId );
		},
	};

	initTomSelect( selectAuthorCourseEl, customOptions, customParams, );
};

//  Init Tom-select author co-instructor course
const selectCoInstructor = () => {
	const selectCoInstructorEl = document.querySelector( '[name="_lp_co_teacher[]"' );
	const postAuthorEl = document.querySelector( '[name="post_author"]' );

	if ( ! selectCoInstructorEl ) {
		return;
	}

	const userId = postAuthorEl?.value || 0;
	let defaultIds = selectCoInstructorEl.dataset?.saved || '';
	if ( defaultIds.length ) {
		defaultIds = JSON.parse( defaultIds );
	}

	const roleSearch = 'administrator,lp_teacher';

	const dataSend = { role_in: roleSearch, id_not_in: userId, current_ids: defaultIds.toString() };

	const customOptions = {
		items: defaultIds,
		onChange: ( data ) => {
			if ( data.length < 1 ) {
				selectCoInstructorEl.value = '';
			}
		},
	};

	initTomSelect( selectCoInstructorEl, customOptions, dataSend, );
};

const defaultInitTomSelect = ( registered = [] ) => {
	const tomSelectEls = Array.prototype.slice.call( document.querySelectorAll( '.lp-tom-select' ) );

	if ( tomSelectEls.length ) {
		tomSelectEls.map( ( tomSelectEl ) => {
			if ( registered.length ) {
				if ( registered.includes( tomSelectEl ) ) {
					return;
				}
			}
			initTomSelect( tomSelectEl );
		} );
	}
};

export {
	initTomSelect,
	selectAuthorCourse,
	searchUserOnListPost,
	selectCoInstructor,
	defaultInitTomSelect,
};
