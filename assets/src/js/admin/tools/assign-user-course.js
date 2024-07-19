/**
 * Assign user to course
 *
 * @since 4.2.5.6
 * @version 1.0.1
 */
import * as AdminUtils from '../utils-admin.js';

export default function assignUserCourse() {
	let elFormAssignUserCourse;
	let elFormUnAssignUserCourse;
	let elUserUnAssign, elCourseUnAssign, elUserAssign, elCourseAssign;
	const limitHandle = 5;

	const getAllElements = () => {
		elFormAssignUserCourse = document.querySelector( '#lp-assign-user-course-form' );
		elFormUnAssignUserCourse = document.querySelector( '#lp-unassign-user-course-form' );

		if ( elFormAssignUserCourse ) {
			elUserUnAssign = elFormUnAssignUserCourse.querySelector( '[name=user_ids]' );
			elCourseUnAssign = elFormUnAssignUserCourse.querySelector( '[name=course_ids]' );
		}

		if ( elFormUnAssignUserCourse ) {
			elUserAssign = elFormAssignUserCourse.querySelector( '[name=user_ids]' );
			elCourseAssign = elFormAssignUserCourse.querySelector( '[name=course_ids]' );
		}
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

	const fetchAPIAssignCourse = ( packages, data, page, totalPage ) => {
		const url = AdminUtils.Api.admin.apiAssignUserCourse;
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

		AdminUtils.Utils.lpFetchAPI( url, params, {
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
					if ( ! elUserAssign.tomselect || ! elCourseAssign.tomselect ) {
						return;
					}

					elUserAssign.tomselect.clear();
					elCourseAssign.tomselect.clear();
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
		const url = AdminUtils.Api.admin.apiUnAssignUserCourse;
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

		AdminUtils.Utils.lpFetchAPI( url, params, {
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
					if ( ! elUserUnAssign.tomselect || ! elCourseUnAssign.tomselect ) {
						return;
					}

					elUserUnAssign.tomselect.clear();
					elCourseUnAssign.tomselect.clear();
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

		events();
	} );
}
