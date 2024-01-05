/**
 * Assign user to course
 *
 * @since 4.2.5.6
 * @version 1.0.0
 */
import TomSelect from 'tom-select';
import { lpFetchAPI } from '../../utils.js';
import Api from '../../api.js';

export default function assignUserCourse() {
	let elFormAssignUserCourse, elTomSelectCourseAssign, elTomSelectUserAssign;
	let elFormUnAssignUserCourse, elTomSelectCourseUnAssign, elTomSelectUserUnAssign;
	const limitHandle = 5;

	const getAllElements = () => {
		elFormAssignUserCourse = document.querySelector( '#lp-assign-user-course-form' );
		elFormUnAssignUserCourse = document.querySelector( '#lp-unassign-user-course-form' );
	};

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

	const events = () => {
		const elForm = document.querySelector( 'form' );
		if ( ! elForm ) {
			return;
		}

		document.addEventListener( 'submit', ( e ) => {
			const elForm = e.target;
			const formData = new FormData( e.target ); // Create a FormData object from the form

			// get values of form.
			const obj = Object.fromEntries( Array.from( formData.keys(), ( key ) => {
				const val = formData.getAll( key );
				return [ key, val.length > 1 ? val : val.pop() ];
			} ) );

			if ( elForm.id === 'lp-assign-user-course-form' ) {
				e.preventDefault();

				if ( ! confirm( 'Are you sure you want to Assign?' ) ) {
					return;
				}

				const { packages, data, totalPage } = handleDataBeforeSend( obj );
				fetchAPIAssignCourse( packages, data, 1, totalPage );
			} else if ( elForm.id === 'lp-unassign-user-course-form' ) {
				e.preventDefault();

				if ( ! confirm( 'Are you sure you want to Unassign?' ) ) {
					return;
				}

				const { packages, data, totalPage } = handleDataBeforeSend( obj );
				fetchAPIUnAssignCourse( packages, data, 1, totalPage );
			}
		} );
	};

	const handleDataBeforeSend = ( dataRaw ) => {
		// Cut to packages to send, 1 packages has 5 items.
		let arrCourseIds = [];
		let arrUserIds = [];
		if ( typeof dataRaw.course_ids === 'string' ) {
			arrCourseIds.push( dataRaw.course_ids );
		} else if ( typeof dataRaw.course_ids === 'object' ) {
			arrCourseIds = dataRaw.course_ids;
		}

		if ( typeof dataRaw.user_ids === 'string' ) {
			arrUserIds.push( dataRaw.user_ids );
		} else if ( typeof dataRaw.user_ids === 'object' ) {
			arrUserIds = dataRaw.user_ids;
		}

		const packages = [];
		arrCourseIds.map( ( courseId, indexCourse ) => {
			const item = {};

			item.course_id = courseId;
			arrUserIds.map( ( userID, indexUser ) => {
				const newItem = { ...item, user_id: userID };
				packages.push( newItem );
			} );
		} );

		const data = packages.slice( 0, limitHandle );
		const totalPage = Math.ceil( packages.length / limitHandle );

		return { packages, data, totalPage };
	};

	const fetchCourses = ( keySearch = '', callback, elTomSelect ) => {
		const url = Api.admin.apiSearchCourses;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( { c_search: keySearch } ),
		};

		lpFetchAPI( url, params, {
			success: ( response ) => {
				const options = response.data.map( ( item ) => {
					return {
						value: item.ID,
						text: item.post_title + `(#${ item.ID })`,
					};
				} );

				// Set data courses default first to Tom Select.
				if ( keySearch === '' ) {
					const elCourseAssign = elFormAssignUserCourse.querySelector( '[name=course_ids]' );
					if ( elCourseAssign ) {
						elTomSelectCourseAssign = buildTomSelect( elCourseAssign, { options }, fetchCourses );
					}

					const elCourseUnAssign = elFormUnAssignUserCourse.querySelector( '[name=course_ids]' );
					if ( elCourseUnAssign ) {
						elTomSelectCourseUnAssign = buildTomSelect( elCourseUnAssign, { options }, fetchCourses );
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

	const fetchUsers = ( keySearch = '', callback, elTomSelect ) => {
		const url = Api.admin.apiSearchUsers;
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

				// Set data users default first to Tom Select.
				if ( keySearch === '' ) {
					const elUserAssign = elFormAssignUserCourse.querySelector( '[name=user_ids]' );
					if ( elUserAssign ) {
						elTomSelectUserAssign = buildTomSelect( elUserAssign, { options }, fetchUsers );
					}

					const elUserUnAssign = elFormUnAssignUserCourse.querySelector( '[name=user_ids]' );
					if ( elUserUnAssign ) {
						elTomSelectUserUnAssign = buildTomSelect( elUserUnAssign, { options }, fetchUsers );
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

	const fetchAPIAssignCourse = ( packages, data, page, totalPage ) => {
		const url = Api.admin.apiAssignUserCourse;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( { data, page, totalPage } ),
		};
		const elProgress = elFormAssignUserCourse.querySelector( '.percent' );
		const elButtonAssign = elFormAssignUserCourse.querySelector( '.lp-button-assign-course' );
		const elMessage = elFormAssignUserCourse.querySelector( '.message' );
		elButtonAssign.disabled = true;

		lpFetchAPI( url, params, {
			success: ( response ) => {
				const { status, message } = response;
				if ( status === 'success' ) {
					let page = parseInt( response.data.page );
					const begin = page * limitHandle;
					const end = begin + limitHandle;
					data = packages.slice( begin, end );
					elProgress.innerHTML = response.data.percent;
					fetchAPIAssignCourse( packages, data, ++page, totalPage );
				} else if ( status === 'finished' ) {
					elProgress.innerHTML = '';
					elMessage.style.color = 'green';
					elMessage.innerHTML = message;
					setTimeout( () => {
						elMessage.innerHTML = '';
					}, 2000 );
					elButtonAssign.disabled = false;
					// Clear data selected on Tom Select.
					elTomSelectCourseAssign.clear();
					elTomSelectUserAssign.clear();
				} else if ( status === 'error' ) {
					elButtonAssign.disabled = false;
					elMessage.style.color = 'red';
					elMessage.innerHTML = message;
					setTimeout( () => {
						elMessage.innerHTML = '';
					}, 2000 );
				}
			},
			error: ( err ) => {
				elButtonAssign.disabled = false;
				elMessage.innerHTML = err.message;
			},
			completed: () => {

			},
		} );
	};

	const fetchAPIUnAssignCourse = ( packages, data, page, totalPage ) => {
		const url = Api.admin.apiUnAssignUserCourse;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( { data, page, totalPage } ),
		};
		const elProgress = elFormUnAssignUserCourse.querySelector( '.percent' );
		const elButtonAssign = elFormUnAssignUserCourse.querySelector( '.lp-button-unassign-course' );
		const elMessage = elFormUnAssignUserCourse.querySelector( '.message' );
		elButtonAssign.disabled = true;

		lpFetchAPI( url, params, {
			success: ( response ) => {
				const { status, message } = response;
				if ( status === 'success' ) {
					let page = parseInt( response.data.page );
					const begin = page * limitHandle;
					const end = begin + limitHandle;
					data = packages.slice( begin, end );
					elProgress.innerHTML = response.data.percent;
					fetchAPIUnAssignCourse( packages, data, ++page, totalPage );
				} else if ( status === 'finished' ) {
					elProgress.innerHTML = '';
					elMessage.style.color = 'green';
					elMessage.innerHTML = message;
					setTimeout( () => {
						elMessage.innerHTML = '';
					}, 2000 );
					elButtonAssign.disabled = false;
					// Clear data selected on Tom Select.
					elTomSelectCourseUnAssign.clear();
					elTomSelectUserUnAssign.clear();
				} else if ( status === 'error' ) {
					elButtonAssign.disabled = false;
					elMessage.style.color = 'red';
					elMessage.innerHTML = message;
					setTimeout( () => {
						elMessage.innerHTML = '';
					}, 2000 );
				}
			},
			error: ( err ) => {
				elButtonAssign.disabled = false;
				elMessage.innerHTML = err.message;
			},
			completed: () => {

			},
		} );
	};

	// DOMContentLoaded.
	document.addEventListener( 'DOMContentLoaded', () => {
		getAllElements();
		if ( ! elFormAssignUserCourse ) {
			return;
		}

		// Get list courses default first and build Tom Select.
		fetchCourses();
		// Get list users default first and build Tom Select.
		fetchUsers();
		// Events.
		events();
	} );
}
