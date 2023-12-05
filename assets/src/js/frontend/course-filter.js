import API from '../api';
import { lpFetchAPI } from '../utils';

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

		let url = API.frontend.apiCourses + '?c_search=' + keyword + '&c_suggest=1';
		if ( lpData.urlParams.hasOwnProperty( 'lang' ) ) {
			url += '&lang=' + lpData.urlParams.lang;
		}

		let paramsFetch = {
			method: 'GET',
		};
		if ( 0 !== parseInt( lpData.user_id ) ) {
			paramsFetch = {
				...paramsFetch,
				headers: {
					'X-WP-Nonce': lpData.nonce,
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
	loadWidgetFilterREST: ( widgetForm ) => {
		const parent = widgetForm.closest( '.learnpress-widget-wrapper' );
		if ( ! parent ) {
			return;
		}

		const widgetData = parent.dataset.widget ? JSON.parse( parent.dataset.widget ) : '';
		const url = API.frontend.apiWidgets;
		const formData = new FormData( widgetForm );
		const filterCourses = { paged: 1 };
		const elLoadingChange = parent.querySelector( '.lp-widget-loading-change' );

		elLoadingChange.style.display = 'block';

		for ( const pair of formData.entries() ) {
			const key = pair[ 0 ];
			const value = formData.getAll( key );
			if ( ! filterCourses.hasOwnProperty( key ) ) {
				let value_convert = value;
				if ( 'object' === typeof value ) {
					value_convert = value.join( ',' );
				}
				filterCourses[ key ] = value_convert;
			}
		}

		if ( 'undefined' !== typeof lpData.urlParams.page_term_id_current ) {
			filterCourses.page_term_id_current = lpData.urlParams.page_term_id_current;
		} else if ( 'undefined' !== typeof lpData.urlParams.page_tag_id_current ) {
			filterCourses.page_tag_id_current = lpData.urlParams.page_tag_id_current;
		}

		const filterParamsUrl = { params_url: filterCourses };
		// Send lang to API if exist for multiple lang.
		if ( lpData.urlParams.hasOwnProperty( 'lang' ) ) {
			filterParamsUrl.params_url.lang = lpData.urlParams.lang;
		}

		const paramsFetch = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( { ...widgetData, ...filterParamsUrl } ),
		};

		if ( 0 !== parseInt( lpData.user_id ) ) {
			paramsFetch.headers[ 'X-WP-Nonce' ] = lpData.nonce;
		}

		const callBack = {
			before: () => {

			},
			success: ( res ) => {
				const { data, status, message } = res;

				if ( data && status === 'success' ) {
					widgetForm.innerHTML = data;
				} else if ( message ) {
					parent.insertAdjacentHTML( 'afterbegin', `<div class="lp-ajax-message error" style="display:block">${ message }</div>` );
				}
			},
			error: ( error ) => {

			},
			completed: () => {
				elLoadingChange.style.display = 'none';
			},
		};

		// Call API load widget
		lpFetchAPI( url, paramsFetch, callBack );
	},
	submit: ( form ) => {
		const formData = new FormData( form ); // Create a FormData object from the form
		const elListCourse = document.querySelector( '.learn-press-courses' );

		//const skeleton = elListCourse.querySelector( '.lp-archive-course-skeleton' );
		const filterCourses = { paged: 1 };

		if ( 'undefined' !== typeof window.lpCourseList ) {
			window.lpCourseList.updateEventTypeBeforeFetch( 'filter' );
		}

		for ( const pair of formData.entries() ) {
			const key = pair[ 0 ];
			const value = formData.getAll( key );
			if ( ! filterCourses.hasOwnProperty( key ) ) {
				filterCourses[ key ] = value;
			}
		}

		if ( 'undefined' !== typeof lpData.urlParams.page_term_id_current ) {
			filterCourses.page_term_id_current = lpData.urlParams.page_term_id_current;
		}

		if ( 'undefined' !== typeof lpData.urlParams.page_tag_id_current ) {
			filterCourses.page_tag_id_current = lpData.urlParams.page_tag_id_current;
		}

		// Send lang to API if exist for multiple lang.
		if ( lpData.urlParams.hasOwnProperty( 'lang' ) ) {
			filterCourses.lang = lpData.urlParams.lang;
		}

		if ( 'undefined' !== typeof lpSettingCourses &&
			lpData.is_course_archive &&
			lpSettingCourses.lpArchiveLoadAjax &&
			elListCourse &&
			'undefined' !== typeof window.lpCourseList ) {
			window.lpCourseList.triggerFetchAPI( filterCourses );
		} else {
			const courseUrl = lpData.urlParams.page_term_url || lpData.courses_url || '';
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
		if ( lpData.is_course_archive ) {
			btnSubmit.click();
		}

		// Load AJAX widget by params
		window.lpCourseFilter.loadWidgetFilterREST( form );
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
			const parent = target.closest( '.lp-course-filter__field' );
			if ( ! parent ) {
				return;
			}

			// Filter courses
			const form = parent.closest( `.${ classCourseFilter }` );
			const btnSubmit = form.querySelector( '.course-filter-submit' );
			let enableLoadAJAXCourses = false;
			enableLoadAJAXCourses = 'undefined' !== typeof lpSettingCourses ? parseInt( lpSettingCourses.lpArchiveLoadAjax ) : 0;
			const elListCourse = document.querySelector( '.learn-press-courses' );
			if ( elListCourse && enableLoadAJAXCourses ) {
				btnSubmit.click();
			}

			// Load AJAX widget by params
			window.lpCourseFilter.loadWidgetFilterREST( form );
			return;
		}

		// Click el parent of input to tick/untick field
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
