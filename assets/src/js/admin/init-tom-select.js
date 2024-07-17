import { AdminUtilsFunctions } from './utils-admin.js';

// Init Tom-select user in order
const searchUserOrder = () => {
	const searchUserOrderEl = document.querySelector( '#list-users' );
	let defaultId = '';
	let tomSelect;

	if ( ! searchUserOrderEl ) {
		return;
	}

	if ( searchUserOrderEl.dataset.userId ) {
		defaultId = JSON.parse( searchUserOrderEl.dataset.userId );
	}

	const customOptions = {
		maxItems: null,
		items: defaultId,
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

	const callBackUser = {
		success: ( response ) => {
			let options = [];
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
				searchUserOrderEl,
				{ ...customOptions, options },
				AdminUtilsFunctions.fetchUsers,
				{ current_ids: defaultId.toString() },
				callBackUser
			);

			return options;
		},
	};
	AdminUtilsFunctions.fetchUsers( '', { current_ids: defaultId.toString() }, callBackUser );
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
		let tomSelect, defaultId;
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
			render: {
				item( data, escape ) {
					return (
						`<li data-id="${ data.value }">` +
						`<div class="item" data-ts-item="">${ data.text }</div>` +
						`<input type="hidden" name="author" value="${ data.value }">` +
						'</li>'
					);
				},
			},
		};

		const callBackUser = {
			success: ( response ) => {
				let options = [];
				if ( response.data.users.length > 0 ) {
					options = response.data?.map( ( item ) => {
						return {
							value: item.ID,
							text: `${ item.display_name } (#${ item.ID })`,
						};
					} );
				}
				if ( null != tomSelect ) {
					return options;
				}

				tomSelect = AdminUtilsFunctions.buildTomSelect(
					selectAuthor,
					{ ...customOptions, options },
					AdminUtilsFunctions.fetchUsers,
					{ current_ids: defaultId },
					callBackUser
				);

				return options;
			},
		};
		AdminUtilsFunctions.fetchUsers( '', { current_ids: defaultId }, callBackUser );
	};

	createSelectUserHtml();
	tomSearchUser();
};

// Init Tom-select author in course
const selectAuthorCourse = () => {
	let tomSelect;
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
		items: defaultId,
		plugins: {},
		render: {
			item( data, escape ) {
				return `<li data-id="${ data.value }"><div class="item" data-ts-item="">${ data.text }</div></li>`;
			},
		},
		onItemAdd: ( data, item ) => {
			authorInputEl.setAttribute( 'value', data );
		},

		onItemRemove: ( data, item ) => {
			authorInputEl.setAttribute( 'value', defaultId );
		},
	};

	const callBackUser = {
		success: ( response ) => {
			let options = [];
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
				selectAuthorCourseEl,
				{ ...customOptions, options },
				AdminUtilsFunctions.fetchUsers,
				customParams,
				callBackUser
			);

			return options;
		},
	};

	AdminUtilsFunctions.fetchUsers( '', customParams, callBackUser );
};

//  Init Tom-select author co-instructor course
const selectCoInstructor = () => {
	let tomSelect;
	const selectCoInstructorEl = document.querySelector( '[name="_lp_co_teacher"]' );
	const postAuthorEl = document.querySelector( '[name="post_author"]' );
	if ( ! selectCoInstructorEl ) {
		return;
	}

	const userId = postAuthorEl?.value ? postAuthorEl?.value : '';
	const defaultId = selectCoInstructorEl.dataset?.coInstructors
		? JSON.parse( selectCoInstructorEl.dataset?.coInstructors )
		: '';

	const roleSearch = 'administrator,lp_teacher';

	const dataSend = { role_in: roleSearch, id_not_in: userId, current_ids: defaultId.toString() };

	const customOptions = {
		maxItems: null,
		items: defaultId[ 0 ],
		placeholder: 'Chose user',
		render: {
			item( data, escape ) {
				return (
					`<li data-id="${ data.value }">` +
					`<div class="item" data-ts-item="">${ data.text }</div>` +
					`<input type="hidden" name="_lp_co_teacher[]" value="${ data.value }">` +
					'</li>'
				);
			},
		},
		onChange: ( data ) => {
			if ( data.length < 1 ) {
				selectCoInstructorEl.value = '';
			}
		},
	};

	const callBackUser = {
		success: ( response ) => {
			let options = [];
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
				selectCoInstructorEl,
				{ ...customOptions, options },
				AdminUtilsFunctions.fetchUsers,
				{ dataSend },
				callBackUser
			);

			return options;
		},
	};

	AdminUtilsFunctions.fetchUsers( '', dataSend, callBackUser );
};

export {
	selectAuthorCourse,
	searchUserOnListPost,
	searchUserOrder,
	selectCoInstructor,
};
