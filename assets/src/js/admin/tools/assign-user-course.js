/**
 * Assign user to course
 */
import TomSelect from 'tom-select';
import { lpFetchAPI } from '../../utils.js';
import Api from '../../api.js';

export default function assignUserCourse() {
	let elFormAssignUserCourse;
	const getAllElements = () => {
		elFormAssignUserCourse = document.querySelector( '#lp-assign-user-course-form' );
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
					const begin = page * 5;
					const end = begin + 5;
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

	const events = () => {
		elFormAssignUserCourse.addEventListener( 'submit', ( e ) => {
			console.log( 'submit' );
			e.preventDefault();

			const formData = new FormData( e.target ); // Create a FormData object from the form

			// get values
			const obj = Object.fromEntries( Array.from( formData.keys(), ( key ) => {
				const val = formData.getAll( key );
				return [ key, val.length > 1 ? val : val.pop() ];
			} ) );

			// Cut to packages to send, 1 packages has 5 items.
			let arrCourseIds = [];
			let arrUserIds = [];
			if ( typeof obj.course_ids === 'string' ) {
				arrCourseIds.push( obj.course_ids );
			} else if ( typeof obj.course_ids === 'object' ) {
				arrCourseIds = obj.course_ids;
			}

			if ( typeof obj.user_ids === 'string' ) {
				arrUserIds.push( obj.user_ids );
			} else if ( typeof obj.user_ids === 'object' ) {
				arrUserIds = obj.user_ids;
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

			const limitHandle = 5;
			const data = packages.slice( 0, limitHandle );
			const totalPage = Math.ceil( packages.length / limitHandle );

			fetchAPIAssignCourse( packages, data, 1, totalPage );
		} );
	};

	const fetchCourses = ( keySearch, callback, elTomSelect ) => {
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

	const fetchUsers = ( keySearch, callback, elTomSelect ) => {
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

	const createSelectTom = () => {
		const elCourseAssign = elFormAssignUserCourse.querySelector( '[name=course_ids]' );
		if ( ! elCourseAssign ) {
			return;
		}

		const tomSelectCourseAssign = new TomSelect( elCourseAssign, {
			maxItems: 5,
			options: [],
			plugins: {
				remove_button: {
					title: 'Remove this item',
				},
			},
			load( keySearch, callback ) {
				fetchCourses( keySearch, callback );
			},
		} );

		fetchCourses( '', tomSelectCourseAssign.setupOptions, tomSelectCourseAssign );
	};

	const createUserTomSelect = () => {
		const elUserAssign = elFormAssignUserCourse.querySelector( '[name=user_ids]' );
		if ( ! elUserAssign ) {
			return;
		}

		const tomSelectUserAssign = new TomSelect( elUserAssign, {
			maxItems: 5,
			options: [],
			plugins: {
				remove_button: {
					title: 'Remove this item',
				},
			},
			load( keySearch, callback ) {
				fetchUsers( keySearch, callback );
			},
		} );

		fetchUsers( '', tomSelectUserAssign.setupOptions, tomSelectUserAssign );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		getAllElements();
		if ( ! elFormAssignUserCourse ) {
			return;
		}
		events();

		createSelectTom();
		createUserTomSelect();
	} );
}
