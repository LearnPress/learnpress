
const { debounce } = lodash;

const addQueryArgs = ( endpoint, args ) => {
	const url = new URL( endpoint );

	Object.keys( args ).forEach( ( arg ) => {
		url.searchParams.append( arg, args[ arg ] );
	} );

	return url;
};

const requestCourse = ( args ) => {
	const wpRestUrl = lpGlobalSettings.lp_rest_url;
	const userID = lpGlobalSettings.user_id || '';

	if ( ! wpRestUrl ) {
		return;
	}

	const archive = document.querySelector( '.lp-archive-courses' );
	const archiveCourse = archive && archive.querySelector( 'div.lp-archive-courses .lp-content-area' );
	const listCourse = archiveCourse && archiveCourse.querySelector( 'ul.learn-press-courses' );

	if ( ! listCourse ) {
		return;
	}

	archive.classList.add( 'loading' );

	fetch( addQueryArgs( wpRestUrl + 'lp/v1/courses/archive-course', { ...args, userID } ), {
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
		},
	} ).then( ( response ) => {
		return response.json();
	} ).then( ( reponsive ) => {
		if ( typeof reponsive.data.content !== 'undefined' && listCourse ) {
			listCourse.innerHTML = reponsive.data.content || '';
		}

		const pagination = reponsive.data.pagination;

		if ( typeof pagination !== 'undefined' ) {
			const paginationHTML = new DOMParser().parseFromString( pagination, 'text/html' );
			const paginationSelector = paginationHTML.querySelector( '.learn-press-pagination' );
			const paginationInnerHTML = paginationSelector && paginationSelector.innerHTML;
			const paginationEle = document.querySelector( '.learn-press-pagination' );

			if ( paginationEle ) {
				if ( paginationInnerHTML ) {
					paginationEle.innerHTML = paginationInnerHTML || '';
				} else {
					paginationEle.remove();
				}
			} else {
				archiveCourse.innerHTML += pagination;
			}
		}

		paginationCourse();
	} ).catch( ( error ) => {
		listCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error: Query lp/v1/courses/archive-course' }</div>`;
	} ).finally( () => {
		archive.classList.remove( 'loading' );
	} );
};

const searchCourse = debounce( ( event ) => {
	event.preventDefault();

	requestCourse( {
		s: event.target.value,
	} );
}, 600 );

const paginationCourse = () => {
	const paginationEle = document.querySelectorAll( '.lp-archive-courses .learn-press-pagination .page-numbers' );

	paginationEle.length > 0 && paginationEle.forEach( ( ele ) => ele.addEventListener( 'click', ( event ) => {
		event.preventDefault();

		const urlString = event.target.getAttribute( 'href' );

		if ( urlString ) {
			const url = new URL( urlString );
			const s = url.searchParams.get( 's' ) || '';
			const orderby = url.searchParams.get( 'orderby' ) || '';
			const order = url.searchParams.get( 'order' ) || '';
			const page = event.target.textContent || 1;

			requestCourse( { s, page, order, orderby } );
		}
	} ) );
};

const gridListCourse = () => {
	const layout = LP.Cookies.get( 'courses-layout' );

	const switches = document.querySelectorAll( '.lp-courses-bar .switch-layout [name="lp-switch-layout-btn"]' );

	switches.length > 0 && [ ...switches ].map( ( ele ) => ele.value === layout && ( ele.checked = true ) );
};

const gridListCourseHandle = ( e ) => {
	e.preventDefault();

	const layout = e.target.value;

	if ( layout ) {
		const dataLayout = document.querySelector( '.lp-archive-courses .learn-press-courses[data-layout]' );

		dataLayout && ( dataLayout.dataset.layout = layout );
		LP.Cookies.set( 'courses-layout', layout );
	}
};

document.addEventListener( 'DOMContentLoaded', function( event ) {
	const archiveEle = document.querySelector( '.lp-archive-courses' );
	const search = archiveEle.querySelectorAll( '.search-courses input[name="s"]' );
	const gridList = archiveEle.querySelectorAll( 'input[name="lp-switch-layout-btn"]' );

	search.forEach( ( s ) => s.addEventListener( 'keyup', searchCourse ) );
	gridList.forEach( ( s ) => s.addEventListener( 'change', gridListCourseHandle ) );

	paginationCourse();
	gridListCourse();
} );
