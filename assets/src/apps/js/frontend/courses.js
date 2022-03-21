
//const { debounce } = lodash;

const lpArchiveAddQueryArgs = ( endpoint, args ) => {
	const url = new URL( endpoint );

	Object.keys( args ).forEach( ( arg ) => {
		url.searchParams.set( arg, args[ arg ] );
	} );

	return url;
};

const lpArchiveCourse = () => {
	const elements = document.querySelectorAll( '.lp-archive-course-skeleton' );

	if ( ! elements.length ) {
		return;
	}

	if ( ! lpArchiveSkeleton ) {
		return;
	}

	lpArchiveRequestCourse( lpArchiveSkeleton );
};

let skeleton;
let skeletonClone;
let isLoading = false;
let firstLoad = 1;
window.lpArchiveRequestCourse = ( args, callBackSuccess ) => {
	const wpRestUrl = lpGlobalSettings.lp_rest_url;

	if ( ! wpRestUrl ) {
		return;
	}

	const archive = document.querySelector( '.lp-archive-courses' );
	const archiveCourse = archive && archive.querySelector( 'div.lp-archive-courses .lp-content-area' );
	const listCourse = archiveCourse && archiveCourse.querySelector( 'ul.learn-press-courses' );

	if ( ! listCourse ) {
		return;
	}

	if ( isLoading ) {
		return;
	}

	isLoading = true;

	if ( ! skeletonClone ) {
		skeleton = document.querySelector( '.lp-archive-course-skeleton' );
		skeletonClone = skeleton.outerHTML;
	} else {
		listCourse.innerHTML = skeletonClone;
	}

	const urlCourseArchive = lpArchiveAddQueryArgs( wpRestUrl + 'lp/v1/courses/archive-course', { ...args } );

	wp.apiFetch( {
		path: 'lp/v1/courses/archive-course' + urlCourseArchive.search,
		method: 'GET',
	} ).then( ( response ) => {
		if ( typeof response.data.content !== 'undefined' && listCourse ) {
			listCourse.innerHTML = response.data.content || '';
		}

		const pagination = response.data.pagination;

		lpArchiveSearchCourse();

		const paginationEle = document.querySelector( '.learn-press-pagination' );
		if ( paginationEle ) {
			paginationEle.remove();
		}

		if ( typeof pagination !== 'undefined' ) {
			const paginationHTML = new DOMParser().parseFromString( pagination, 'text/html' );
			const paginationNewNode = paginationHTML.querySelector( '.learn-press-pagination' );
			//const paginationInnerHTML = paginationSelector && paginationSelector.innerHTML;

			if ( paginationNewNode ) {
				listCourse.after( paginationNewNode );
				lpArchivePaginationCourse();
			}
		}

		wp.hooks.doAction( 'lp-js-get-courses', response );

		if ( typeof callBackSuccess === 'function' ) {
			callBackSuccess( response );
		}
	} ).catch( ( error ) => {
		listCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error: Query lp/v1/courses/archive-course' }</div>`;
	} ).finally( () => {
		isLoading = false;
		skeleton && skeleton.remove();

		jQuery( 'form.search-courses button' ).removeClass( 'loading' );

		// Push url
		const urlPush = lpArchiveAddQueryArgs( document.location, args );
		window.history.pushState( '', '', urlPush );

		// Scroll to archive element
		if ( ! firstLoad ) {
			archive.scrollIntoView();
		} else {
			firstLoad = 0;
		}
	} );
};

const lpArchiveSearchCourse = () => {
	const searchForm = document.querySelectorAll( 'form.search-courses' );

	searchForm.forEach( ( s ) => {
		const search = s.querySelector( 'input[name="c_search"]' );
		const urlAction = s.getAttribute( 'action' );
		const postType = s.querySelector( '[name="post_type"]' ).value || '';
		const taxonomy = s.querySelector( '[name="taxonomy"]' ).value || '';
		const termID = s.querySelector( '[name="term_id"]' ).value || '';
		const btn = s.querySelector( '[type="submit"]' );
		let timeOutSearch;

		search.addEventListener( 'keyup', ( event ) => {
			event.preventDefault();

			const s = event.target.value.trim();

			if ( ! s || ( s && s.length > 2 ) ) {
				if ( undefined !== timeOutSearch ) {
					clearTimeout( timeOutSearch );
				}

				timeOutSearch = setTimeout( function() {
					btn.classList.add( 'loading' );

					delete lpArchiveSkeleton.paged;

					const url = new URL( urlAction );
					const urlCurrent = new URL( document.location );
					urlCurrent.searchParams.set( 'c_search', s );
					const strParams = urlCurrent.searchParams.toString();
					const params = strParams.split( '&' );

					const objectParams = {};
					params.forEach( ( val, i ) => {
						const keyVal = val.split( '=' );
						objectParams[ keyVal[ 0 ] ] = keyVal[ 1 ];
						url.searchParams.append( keyVal[ 0 ], keyVal[ 1 ] );
					} );

					lpArchiveRequestCourse( { ...objectParams } );

					window.history.pushState( '', '', url );
				}, 800 );
			}
		} );

		s.addEventListener( 'submit', ( e ) => {
			e.preventDefault();

			const eleSearch = s.querySelector( 'input[name="c_search"]' );
			eleSearch && eleSearch.dispatchEvent( new Event( 'keyup' ) );
		} );
	} );
};

const lpArchivePaginationCourse = () => {
	const paginationEle = document.querySelectorAll( '.lp-archive-courses .learn-press-pagination .page-numbers' );

	paginationEle.length > 0 && paginationEle.forEach( ( ele ) => ele.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		event.stopPropagation();

		const urlString = event.currentTarget.getAttribute( 'href' );

		if ( urlString ) {
			const url = new URL( urlString );

			const params = {};
			url.searchParams.forEach( ( key, value ) => {
				params[ value ] = key;
			} );

			const current = [ ...paginationEle ].filter( ( el ) => el.classList.contains( 'current' ) );

			const paged = event.currentTarget.textContent || ( ele.classList.contains( 'next' ) && parseInt( current[ 0 ].textContent ) + 1 ) || ( ele.classList.contains( 'prev' ) && parseInt( current[ 0 ].textContent ) - 1 );

			lpArchiveRequestCourse( { ...params, paged } );

			window.history.pushState( '', '', urlString );
		}
	} ) );
};

const lpArchiveGridListCourse = () => {
	const layout = LP.Cookies.get( 'courses-layout' );

	const switches = document.querySelectorAll( '.lp-courses-bar .switch-layout [name="lp-switch-layout-btn"]' );

	switches.length > 0 && [ ...switches ].map( ( ele ) => ele.value === layout && ( ele.checked = true ) );
};

const lpArchiveGridListCourseHandle = () => {
	const gridList = document.querySelectorAll( '.lp-archive-courses input[name="lp-switch-layout-btn"]' );

	gridList.length > 0 && gridList.forEach( ( element ) => element.addEventListener( 'change', ( e ) => {
		e.preventDefault();

		const layout = e.target.value;

		if ( layout ) {
			const dataLayout = document.querySelector( '.lp-archive-courses .learn-press-courses[data-layout]' );

			dataLayout && ( dataLayout.dataset.layout = layout );
			LP.Cookies.set( 'courses-layout', layout );
		}
	} ) );
};

function LPArchiveCourseInit() {
	lpArchiveCourse();
	lpArchiveGridListCourseHandle();
	lpArchiveGridListCourse();
}

document.addEventListener( 'DOMContentLoaded', function( event ) {
	LPArchiveCourseInit();
} );
