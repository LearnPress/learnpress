import { AdminUtilsFunctions } from './utils-admin.js';

// Init Tom-select
const initTomSelect = ( tomSelectEl, customOptions = {}, customParams = {} ) => {
	let tomSelect;
	let defaultIds = tomSelectEl.dataset.saved || 0;
	const dataType = tomSelectEl.dataset.type || 'users';
	let plugins = {
		remove_button: {
			title: 'Remove this item',
		},
	};

	const removePlugin = tomSelectEl.dataset.unremoved || false;

	if ( defaultIds.length ) {
		defaultIds = JSON.parse( defaultIds );
	}

	if ( removePlugin ) {
		plugins = {};
	}

	switch ( dataType ) {
	case 'courses':
		const callBackCourse = {
			success: ( response ) => {
				let options = [];

				const settingOption = {
					items: defaultIds,
					plugins,
					render: {
						item( data, escape ) {
							return (
								`<li data-id="${ data.value }"><div class="item">${ data.text }</div></li>`
							);
						},
					},
					...customOptions,
				};

				if ( response.data.courses.length > 0 ) {
					options = response.data.courses.map( ( item ) => {
						return {
							value: item.ID,
							text: item.post_title + `(#${ item.ID })`,
						};
					} );
				}

				if ( null != tomSelect ) {
					return options;
				}

				tomSelect = AdminUtilsFunctions.buildTomSelect(
					tomSelectEl,
					{ ...settingOption, options },
					AdminUtilsFunctions.fetchCourses,
					customParams,
					callBackCourse
				);

				return options;
			},
		};

		AdminUtilsFunctions.fetchCourses( '', customParams, callBackCourse );
		break;
	default:
		const callBackUser = {
			success: ( response ) => {
				let options = [];
				const settingOption = {
					items: defaultIds,
					plugins,
					render: {
						item( data, escape ) {
							return (
								`<li data-id="${ data.value }"><div class="item">${ data.text }</div></li>`
							);
						},
					},
					...customOptions,
				};

				if ( response.data.users.length > 0 ) {
					options = response.data.users.map( ( item ) => {
						return {
							value: item.ID,
							text: `${ item.display_name } (#${ item.ID }) - ${ item.user_email }`,
						};
					} );
				}

				if ( null != tomSelect ) {
					return options;
				}

				tomSelect = AdminUtilsFunctions.buildTomSelect(
					tomSelectEl,
					{ ...settingOption, options },
					AdminUtilsFunctions.fetchUsers,
					customParams,
					callBackUser
				);

				return options;
			},
		};

		AdminUtilsFunctions.fetchUsers( '', customParams, callBackUser );
		break;
	}
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
