let restUrl;
let suggestTimeoutId, suggestData;

document.addEventListener( 'DOMContentLoaded', ( event ) => {
	if ( typeof lpCourseFilterSettings !== 'undefined' ) {
		restUrl = lpGlobalSettings.lp_rest_url;
	}

	courseFilterSubmit();
	suggestSearch();
} );

const courseFilterSubmit = () => {
	const courseUrl = lpCourseFilterSettings.courses_url || '';

	if ( ! courseUrl ) {
		return;
	}

	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( ! target.classList.contains( 'course-filter-submit' ) ) {
			return;
		}

		event.preventDefault();

		const filterForm = target.closest( '.lp-course-filter' );
		let filterCourses = { paged: 1 };
		const keyword = filterForm.querySelector( 'input[ name = "keyword" ]' );
		if ( keyword ) {
			filterCourses.c_search = keyword.value;
		}
		filterCourses = lpCourseFilterRequestParams( filterForm, filterCourses );

		const url = new URL( courseUrl );
		Object.keys( filterCourses ).forEach( ( arg ) => {
			url.searchParams.set( arg, filterCourses[ arg ] );
		} );

		if ( lpCourseFilterSettings.is_course_archive && lpGlobalSettings.lpArchiveLoadAjax ) {
			lpArchiveRequestCourse( { ...filterCourses } );
		} else {
			window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( filterCourses ) );
			document.location.href = url.href;
		}
	} );
};

window.lpCourseFilterRequestParams = ( filterForm, filterCourses ) => {
	const keyword = filterForm.querySelector( 'input[ name = "keyword" ]' );

	if ( keyword ) {
		filterCourses.c_search = keyword.value;
	}

	const price = filterForm.querySelectorAll( 'input[ name = "price" ]:checked' );
	if ( price.length ) {
		let priceValue = [];
		for ( let i = 0; i < price.length; i++ ) {
			const el = price[ i ];
			priceValue = [ ...priceValue, el.value ];
		}

		filterCourses.sort_by = priceValue;
	} else {
		delete filterCourses.sort_by;
	}

	const instructor = filterForm.querySelectorAll( 'input[ name = "instructor" ]:checked' );
	if ( instructor.length ) {
		let instructorValue = [];
		for ( let i = 0; i < instructor.length; i++ ) {
			const el = instructor[ i ];
			instructorValue = [ ...instructorValue, el.value ];
		}

		if ( instructorValue.length ) {
			filterCourses.c_authors = instructorValue;
		} else {
			delete filterCourses.c_authors;
		}
	} else {
		delete filterCourses.c_authors;
	}

	const level = filterForm.querySelectorAll( 'input[ name = "c_level" ]:checked' );
	if ( level.length ) {
		let levelValue = [];
		for ( let i = 0; i < level.length; i++ ) {
			const el = level[ i ];

			levelValue = [ ...levelValue, el.value ];
		}

		if ( levelValue.length ) {
			filterCourses.c_level = levelValue;
		}
	} else {
		delete filterCourses.c_level;
	}

	let termIds = [];
	const courseCat = filterForm.querySelectorAll( 'input[ name = "term_ids" ]:checked' );
	if ( courseCat.length ) {
		for ( let i = 0; i < courseCat.length; i++ ) {
			const el = courseCat[ i ];
			termIds = [ ...termIds, el.value ];
		}

		filterCourses.term_id = termIds;
	}

	const courseTag = filterForm.querySelectorAll( 'input[ name = "tag_ids" ]:checked' );
	if ( courseTag.length ) {
		for ( let i = 0; i < courseTag.length; i++ ) {
			const el = courseTag[ i ];
			termIds = [ ...termIds, el.value ];
		}

		filterCourses.tag_id = termIds;
	}

	return filterCourses;
};

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

		suggestResult.innerHTML = '';
	} );
};

const fetchSuggestSearchData = ( courseFilter ) => {
	if ( ! courseFilter ) {
		return;
	}

	const suggestResult = courseFilter.querySelector( '.lp-suggest-result' );
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
