const urlCourses = lpGlobalSettings.courses_url || '';
const urlCurrent = document.location.href;
let filterCourses = JSON.parse( window.localStorage.getItem( 'lp_filter_courses' ) ) || {};
let skeleton;
let skeletonClone;
let isLoading = false;
let firstLoad = 1;
let elNoLoadAjaxFirst;
let elArchive;

if ( lpGlobalSettings.is_course_archive ) {
	const queryString = window.location.search;

	if ( ! queryString.length && urlCurrent.search( 'page' ) === -1 ) {
		filterCourses = {};
	}
}

const lpArchiveAddQueryArgs = ( endpoint, args ) => {
	const url = new URL( endpoint );

	Object.keys( args ).forEach( ( arg ) => {
		url.searchParams.set( arg, args[ arg ] );
	} );

	return url;
};

const lpArchiveCourse = () => {
	skeleton = document.querySelector( '.lp-archive-course-skeleton' );
	elNoLoadAjaxFirst = document.querySelector( '.no-first-load-ajax' );

	if ( ! skeleton ) {
		return;
	}

	if ( ! elNoLoadAjaxFirst ) {
		lpArchiveRequestCourse( filterCourses );
	}

	lpArchivePaginationCourse();
	lpArchiveSearchCourse();
};

window.lpArchiveRequestCourse = ( args, callBackSuccess ) => {
	const wpRestUrl = lpGlobalSettings.lp_rest_url;

	if ( ! wpRestUrl ) {
		return;
	}

	if ( ! skeleton ) {
		return;
	}

	const archiveCourse = elArchive && elArchive.querySelector( 'div.lp-archive-courses .lp-content-area' );
	const listCourse = archiveCourse && archiveCourse.querySelector( 'ul.learn-press-courses' );

	if ( ! listCourse ) {
		return;
	}

	if ( isLoading ) {
		return;
	}

	isLoading = true;

	if ( ! skeletonClone ) {
		skeletonClone = skeleton.outerHTML;
	} else {
		listCourse.append( skeleton );
		// return;
	}

	const urlCourseArchive = lpArchiveAddQueryArgs( wpRestUrl + 'lp/v1/courses/archive-course', { ...lpGlobalSettings.lpArchiveSkeleton, ...args } );
	const url = lpGlobalSettings.lp_rest_url + 'lp/v1/courses/archive-course' + urlCourseArchive.search;

	fetch( url, {
		method: 'GET',
	} )
		.then( ( response ) => response.json() )
		.then( ( response ) => {
			if ( typeof response.data.content !== 'undefined' && listCourse ) {
				listCourse.innerHTML = response.data.content || '';
			}

			const pagination = response.data.pagination;

			// lpArchiveSearchCourse();

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
			console.log( error );
		} ).finally( () => {
			isLoading = false;
			// skeleton && skeleton.remove();

			jQuery( 'form.search-courses button' ).removeClass( 'loading' );

			if ( ! firstLoad ) {
			// Scroll to archive element
				const optionScroll = { behavior: 'smooth' };
				elArchive.scrollIntoView( optionScroll );
			} else {
				firstLoad = 0;
			}

			// Save filter courses to Storage
			window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( args ) );
			// Change url by params filter courses
			const urlPush = lpArchiveAddQueryArgs( document.location, args );
			window.history.pushState( '', '', urlPush );
		} );
};

const lpArchiveSearchCourse = () => {
	const searchForm = document.querySelectorAll( 'form.search-courses' );
	const filterCourses = JSON.parse( window.localStorage.getItem( 'lp_filter_courses' ) ) || {};

	searchForm.forEach( ( s ) => {
		const search = s.querySelector( 'input[name="c_search"]' );
		const btn = s.querySelector( '[type="submit"]' );
		let timeOutSearch;

		search.addEventListener( 'keyup', ( event ) => {
			if ( skeleton ) {
				skeleton.style.display = 'block';
			}
			event.preventDefault();

			const s = event.target.value.trim();

			if ( ! s || ( s && s.length > 2 ) ) {
				if ( undefined !== timeOutSearch ) {
					clearTimeout( timeOutSearch );
				}

				timeOutSearch = setTimeout( function() {
					btn.classList.add( 'loading' );

					filterCourses.c_search = s;
					filterCourses.paged = 1;

					lpArchiveRequestCourse( { ...filterCourses } );
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

		if ( ! elArchive ) {
			return;
		}

		if ( skeleton ) {
			skeleton.style.display = 'block';
		}

		// Scroll to archive element
		elArchive.scrollIntoView( { behavior: 'smooth' } );

		let filterCourses = {};
		filterCourses = JSON.parse( window.localStorage.getItem( 'lp_filter_courses' ) ) || {};

		const urlString = event.currentTarget.getAttribute( 'href' );

		if ( urlString ) {
			const current = [ ...paginationEle ].filter( ( el ) => el.classList.contains( 'current' ) );
			const paged = event.currentTarget.textContent || ( ele.classList.contains( 'next' ) && parseInt( current[ 0 ].textContent ) + 1 ) || ( ele.classList.contains( 'prev' ) && parseInt( current[ 0 ].textContent ) - 1 );
			filterCourses.paged = paged;

			lpArchiveRequestCourse( { ...filterCourses } );
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

// document.addEventListener( 'DOMContentLoaded', function( event ) {
// 	LPArchiveCourseInit();
// } );

const detectedElArchive = setInterval( function() {
	if ( typeof lpGlobalSettings.lpArchiveSkeleton === 'undefined' ) {
		return;
	}

	skeleton = document.querySelector( '.lp-archive-course-skeleton' );
	elArchive = document.querySelector( '.lp-archive-courses' );

	if ( elArchive && skeleton ) {
		LPArchiveCourseInit();
		clearInterval( detectedElArchive );
	}
}, 1 );
