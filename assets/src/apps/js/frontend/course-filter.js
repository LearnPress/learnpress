import API from './api';

const classCourseFilter = 'lp-form-course-filter';

// Events
// Submit form filter
document.addEventListener( 'submit', function( e ) {
	const target = e.target;
	if ( ! target.classList.contains( classCourseFilter ) ) {
		return;
	}
	e.preventDefault();

	window.lpCourseFilter.submit( target );
} );

// Click element
document.addEventListener( 'click', function( e ) {
	const target = e.target;
	if ( target.classList.contains( 'course-filter-reset' ) ) {
		e.preventDefault();
		window.lpCourseFilter.reset( target );
	}

	// Show/hide search suggest result
	window.lpCourseFilter.showHideSearchResult( target );

	// Click field
	window.lpCourseFilter.triggerInputChoice( target );
} );

// Search course suggest
document.addEventListener( 'keyup', function( e ) {
	e.preventDefault();
	const target = e.target;

	if ( target.classList.contains( 'lp-course-filter-search' ) ) {
		window.lpCourseFilter.searchSuggestion( target );
	}
} );

let timeOutSearch;
let controller;
let signal;
window.lpCourseFilter = {
	searchSuggestion: ( inputSearch ) => {
		const enable = parseInt( inputSearch.dataset.searchSuggest || 1 );
		if ( 1 !== enable ) {
			return;
		}

		const keyword = inputSearch.value.trim();
		const form = inputSearch.closest( `.${ classCourseFilter }` );
		const elLoading = form.querySelector( '.lp-loading-circle' );

		if ( undefined !== timeOutSearch ) {
			clearTimeout( timeOutSearch );
		}

		if ( keyword && keyword.length > 2 ) {
			elLoading.classList.remove( 'hide' );
			timeOutSearch = setTimeout( function() {
				const callBackDone = ( response ) => {
					const elResult = document.querySelector( '.lp-course-filter-search-result' );
					elResult.innerHTML = response.data.content;
					elLoading.classList.add( 'hide' );
				};
				window.lpCourseFilter.callAPICourseSuggest( keyword, callBackDone );
			}, 500 );
		} else {
			const elResult = form.querySelector( '.lp-course-filter-search-result' );
			elResult.innerHTML = '';
			elLoading.classList.add( 'hide' );
		}
	},
	callAPICourseSuggest: ( keyword, callBackDone, callBackFinally ) => {
		if ( undefined !== controller ) {
			controller.abort();
		}
		controller = new AbortController();
		signal = controller.signal;

		const url = API.apiCourses + '?c_search=' + keyword + '&c_suggest=1';
		let paramsFetch = {
			method: 'GET',
		};
		if ( 0 !== lpGlobalSettings.user_id ) {
			paramsFetch = {
				...paramsFetch,
				headers: {
					'X-WP-Nonce': lpGlobalSettings.nonce,
				},
			};
		}

		fetch( url, { ...paramsFetch, signal } )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				if ( undefined !== callBackDone ) {
					callBackDone( response );
				}
			} ).catch( ( error ) => {
				console.log( error );
			} )
			.finally( () => {
				if ( undefined !== callBackFinally ) {
					callBackFinally();
				}
			} );
	},
	submit: ( form ) => {
		const formData = new FormData( form ); // Create a FormData object from the form
		const elListCourse = document.querySelector( '.learn-press-courses' );
		const skeleton = document.querySelector( '.lp-archive-course-skeleton' );
		const filterCourses = { paged: 1 };
		window.lpCourseList.updateEventTypeBeforeFetch( 'filter' );
		for ( const pair of formData.entries() ) {
			const key = pair[ 0 ];
			const value = formData.getAll( key );
			if ( ! filterCourses.hasOwnProperty( key ) ) {
				filterCourses[ key ] = value;
			}
		}

		if ( lpGlobalSettings.is_course_archive && lpGlobalSettings.lpArchiveLoadAjax && elListCourse && skeleton ) {
			window.lpCourseList.triggerFetchAPI( filterCourses );
		} else {
			const courseUrl = lpGlobalSettings.courses_url || '';
			const url = new URL( courseUrl );
			Object.keys( filterCourses ).forEach( ( arg ) => {
				url.searchParams.set( arg, filterCourses[ arg ] );
			} );

			document.location.href = url.href;
		}
	},
	reset: ( btnReset ) => {
		const form = btnReset.closest( `.${ classCourseFilter }` );
		const btnSubmit = form.querySelector( '.course-filter-submit' );
		const elResult = form.querySelector( '.lp-course-filter-search-result' );
		const elSearch = form.querySelector( '.lp-course-filter-search' );

		form.reset();
		if ( elResult ) {
			elResult.innerHTML = '';
		}
		if ( elSearch ) {
			elSearch.value = '';
		}
		// Uncheck value with case set default from params url.
		for ( let i = 0; i < form.elements.length; i++ ) {
			form.elements[ i ].removeAttribute( 'checked' );
		}
		// If on the page archive course will call btnSubmit click.
		if ( lpGlobalSettings.is_course_archive ) {
			btnSubmit.click();
		}
	},
	showHideSearchResult: ( target ) => {
		const elResult = document.querySelector( '.lp-course-filter-search-result' );
		if ( ! elResult ) {
			return;
		}

		const parent = target.closest( '.lp-course-filter-search-result' );
		if ( ! parent && ! target.classList.contains( 'lp-course-filter-search-result' ) && ! target.classList.contains( 'lp-course-filter-search' ) ) {
			elResult.style.display = 'none';
		} else {
			elResult.style.display = 'block';
		}
	},
	triggerInputChoice: ( target ) => {
		if ( target.tagName === 'INPUT' ) {
			return;
		}

		// Choice field
		let elChoice;

		if ( target.classList.contains( 'lp-course-filter__field' ) ) {
			elChoice = target;
		}

		const parent = target.closest( '.lp-course-filter__field' );
		if ( parent ) {
			elChoice = parent;
		}

		if ( ! elChoice ) {
			return;
		}

		elChoice.querySelector( 'input' ).click();
	},
};
