let restUrl;
let suggestTimeoutId, suggestData;
const courseUrl = lpCourseFilterSettings.courses_url || '';

document.addEventListener( 'DOMContentLoaded', ( event ) => {
	if ( typeof lpCourseFilterSettings !== 'undefined' ) {
		restUrl = lpGlobalSettings.lp_rest_url;
	}
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

	if ( lpCourseFilterSettings.is_course_archive && lpGlobalSettings.lpArchiveLoadAjax ) {
		lpArchiveRequestCourse( filterCourses );
	} else {
		const url = new URL( courseUrl );
		Object.keys( filterCourses ).forEach( ( arg ) => {
			url.searchParams.set( arg, filterCourses[ arg ] );
		} );

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
	// Empty the values in the form.
	for ( let i = 0; i < form.elements.length; i++ ) {
		form.elements[ i ].value = '';
		form.elements[ i ].removeAttribute( 'checked' );
	}
	// If on the page archive course will call btnSubmit click.
	if ( lpCourseFilterSettings.is_course_archive ) {
		btnSubmit.click();
	}
} );
