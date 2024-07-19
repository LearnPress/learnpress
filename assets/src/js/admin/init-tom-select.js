import { AdminUtilsFunctions } from './utils-admin.js';

// Process data from api and add to option tom-select
/**
 *
 * @param {*} response
 * @param {*} tomSelectEl
 * @param {*} dataType
 * @param {*} callBack
 * @param     customOptions
 * @param     customParams
 * @return
 */

const handleResponse = ( response, tomSelectEl, dataType = 'users', customOptions = {}, customParams = {}, callBack ) => {
	if ( ! response || ! tomSelectEl || ! callBack ) {
		return;
	}

	// Get default item tom-select
	let defaultIds = tomSelectEl.dataset.saved || 0;
	if ( defaultIds.length ) {
		defaultIds = JSON.parse( defaultIds );
	}

	const plugins = tomSelectEl.dataset.unremoved ? {} : { remove_button: { title: 'Remove item' }	};

	let options = [];
	const fetchFunction = response.data.users ? AdminUtilsFunctions.fetchUsers : AdminUtilsFunctions.fetchCourses;

	// Format response data set option tom-select
	if ( response.data[ dataType ].length > 0 ) {
		options = response.data[ dataType ].map( ( item ) => ( {
			value: item.ID,
			text: dataType === 'users'
				? `${ item.display_name } (#${ item.ID }) - ${ item.user_email }`
				: `${ item.post_title } (#${ item.ID })`,
		} ) );
	}

	// Setting option tom-select
	const settingOption = {
		items: defaultIds,
		render: {
			item( data, escape ) {
				return `<li data-id="${ data.value }"><div class="item">${ data.text }</div></li>`;
			},
		},
		plugins,
		...customOptions,
		options,
	};

	// Set params api
	const params = {
		current_ids: defaultIds,
		...customParams,
	};

	if ( null != tomSelectEl.tomSelectInstance ) {
		return options;
	}

	tomSelectEl.tomSelectInstance = AdminUtilsFunctions.buildTomSelect(
		tomSelectEl,
		settingOption,
		fetchFunction,
		params,
		callBack
	);

	return options;
};

// Init Tom-select
const initTomSelect = ( tomSelectEl, customOptions = {}, customParams = {} ) => {
	const dataType = tomSelectEl.dataset.type || 'users';

	const fetchFunction = dataType === 'users' ? AdminUtilsFunctions.fetchUsers : AdminUtilsFunctions.fetchCourses;

	const callBackApi = {
		success: ( response ) => {
			handleResponse( response, tomSelectEl, dataType, customOptions, customParams, callBackApi );
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
		const elInputSearch = elSearchPost.querySelector( 'input[name="s"]' );
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
	tomSearchUser();
};

// Init Tom-select author in course
const selectAuthorCourse = () => {
	const selectAuthorCourseEl = document.querySelector(
		'select#_lp_course_author',
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
