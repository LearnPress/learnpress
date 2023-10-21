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

			const params = {
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': lpGlobalSettings.nonce,
				},
				method: 'POST',
				body: JSON.stringify( obj ),
			};
			lpFetchAPI( Api.admin.apiAssignUserCourse, params, {
				success: ( response ) => {
					console.log( response );
				},
			} );
		} );
	};
	const fetchCourses = ( keySearch, callback, elTomSelect ) => {
		const url = Api.admin.apiSearchCourses;
		const params = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpGlobalSettings.nonce,
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

	const createSelectTom = () => {
		const elCourseAssign = elFormAssignUserCourse.querySelector( '[name=course_ids]' );
		if ( ! elCourseAssign ) {
			return;
		}

		const tomSelectCourseAssign = new TomSelect( elCourseAssign, {
			maxItems: 5,
			options: [],
			load( keySearch, callback ) {
				fetchCourses( keySearch, callback );
			},
		} );

		fetchCourses( 'course', tomSelectCourseAssign.setupOptions, tomSelectCourseAssign );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		getAllElements();
		if ( ! elFormAssignUserCourse ) {
			return;
		}
		events();

		createSelectTom();
	} );
}
