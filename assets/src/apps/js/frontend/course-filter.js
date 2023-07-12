let restUrl;
let suggestTimeoutId, suggestData;
const courseUrl = lpCourseFilterSettings.courses_url || '';

document.addEventListener( 'DOMContentLoaded', ( event ) => {
	if ( typeof lpCourseFilterSettings !== 'undefined' ) {
		restUrl = lpGlobalSettings.lp_rest_url;
	}

	suggestSearch();
} );

// Events
// Submit form filter
document.addEventListener( 'submit', function( e ) {
	const target = e.target;

	if ( ! target.classList.contains( 'lp-form-course-filter' ) ) {
		return;
	}

	e.preventDefault();

	const formData = new FormData( target ); // Create a FormData object from the form

	const filterCourses = { paged: 1 };
	for ( const pair of formData.entries() ) {
		const key = pair[ 0 ];
		const value = formData.getAll( key );
		if ( ! filterCourses.hasOwnProperty( key ) ) {
			filterCourses[ key ] = value;
		}
	}

	const url = new URL( courseUrl );

	Object.keys( filterCourses ).forEach( ( arg ) => {
		url.searchParams.set( arg, filterCourses[ arg ] );
	} );

	if ( lpCourseFilterSettings.is_course_archive && lpGlobalSettings.lpArchiveLoadAjax ) {
		lpArchiveRequestCourse( filterCourses );
	} else {
		window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( filterCourses ) );
		document.location.href = url.href;
	}
} );

// Reset form filter
document.addEventListener( 'click', function( e ) {
	const target = e.target;

	if ( ! target.classList.contains( 'course-filter-reset' ) ) {
		return;
	}

	e.preventDefault();
	const form = target.closest( '.lp-form-course-filter' );
	const btnSubmit = form.querySelector( '.course-filter-submit' );

	form.reset();
	btnSubmit.click();
} );

const suggestSearch = () => {
	document.addEventListener( 'input', function( event ) {
		const target = event.target;
		const courseFilter = target.closest( '.lp-course-filter' );

		if ( ! courseFilter ) {
			return;
		}

		const keyword = target.closest( '.keyword[data-suggest="1"]' );
		if ( ! keyword ) {
			return;
		}

		if ( target.value.length < 3 ) {
			return;
		}

		fetchSuggestSearchData( courseFilter );
	} );

	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		const courseFilter = target.closest( '.lp-course-filter' );

		if ( ! courseFilter ) {
			return;
		}

		const keyword = target.closest( '.keyword[data-suggest="1"]' );
		if ( ! keyword ) {
			return;
		}

		const suggestResult = courseFilter.querySelector( '.lp-suggest-result' );

		if ( suggestData && suggestResult ) {
			suggestResult.innerHTML = suggestData;
		} else {
			fetchSuggestSearchData();
		}
	} );

	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( target.classList.contains( 'lp-suggest-item' ) ) {
			return;
		}

		const keyword = target.closest( '.keyword[data-suggest="1"]' );
		if ( keyword ) {
			return;
		}

		const suggestResult = document.querySelector( '.lp-suggest-result' );

		if ( suggestResult ) {
			suggestResult.innerHTML = '';
		}
	} );
};

const fetchSuggestSearchData = ( courseFilter ) => {
	if ( ! courseFilter ) {
		return;
	}

	const suggestResult = courseFilter.querySelector( '.lp-suggest-result' );
	if ( ! suggestResult ) {
		return;
	}
	//Clear all callback before
	clearTimeout( suggestTimeoutId );

	const search = courseFilter.querySelector( '.lp-search-keyword' );

	const searchValue = search.querySelector( 'input' ).value;
	const loading = courseFilter.querySelector( 'i.loading' );

	if ( ! searchValue ) {
		if ( loading ) {
			courseFilter.querySelector( 'i.loading' ).remove();
		}
		suggestData = '';
		suggestResult.innerHTML = '';

		return;
	}
	if ( ! loading ) {
		search.insertAdjacentHTML( 'beforeend', '<i class="loading"></i>' );
	}

	// let query = getQuery();
	const url = new URL( restUrl + 'lp/v1/courses/suggest-course' );
	url.searchParams.set( 'c_search', searchValue );

	suggestTimeoutId = setTimeout( () => {
		fetch( url, { method: 'GET' } )
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				if ( res.data.content ) {
					suggestData = res.data.content;
					suggestResult.innerHTML = suggestData;
				} else if ( res.message ) {
					suggestData = '';
					suggestResult.innerHTML = '';
				}
			} ).catch( ( err ) => {
				console.log( err );
			} ).finally( () => {
				const loading = courseFilter.querySelector( 'i.loading' );
				if ( loading ) {
					loading.remove();
				}
				clearTimeout( suggestTimeoutId );
			} );
	}, 1000 );
};
