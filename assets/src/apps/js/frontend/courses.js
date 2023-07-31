import API from './api';
import { lpAddQueryArgs, lpFetchAPI, lpGetCurrentURLNoParam } from '../utils/utils';
import Cookies from '../utils/cookies';

if ( undefined === lpGlobalSettings ) {
	console.log( 'lpGlobalSettings is undefined' );
}

// Call API load courses.
// When LP v4.2.3.3 release a long time, we will remove this function on theme Eduma.
// assets/js/thim-course-filter-v2.js
window.lpArchiveRequestCourse = ( args ) => {
	window.lpCourseList.updateEventTypeBeforeFetch( 'filter' );
	window.lpCourseList.triggerFetchAPI( args );
};

// Events on change sort by.
document.addEventListener( 'change', function( e ) {
	const target = e.target;

	window.lpCourseList.onChangeSortBy( e, target );
	window.lpCourseList.onChangeTypeLayout( e, target );
} );
document.addEventListener( 'click', function( e ) {
	const target = e.target;

	window.lpCourseList.clickLoadMore( e, target );
	window.lpCourseList.clickNumberPage( e, target );
} );
document.addEventListener( 'scroll', function( e ) {
	const target = e.target;

	window.lpCourseList.scrollInfinite( e, target );
} );
document.addEventListener( 'keyup', function( e ) {
	const target = e.target;

	window.lpCourseList.searchCourse( e, target );
} );
document.addEventListener( 'submit', function( e ) {
	const target = e.target;

	window.lpCourseList.searchCourse( e, target );
} );

let isLoadingInfinite = false;
const isPaged = 1;
let timeOutSearch;
window.lpCourseList = ( () => {
	const classArchiveCourse = 'lp-archive-courses';
	const classListCourse = 'learn-press-courses';
	const classPaginationCourse = 'learn-press-pagination';
	const currentUrl = lpGetCurrentURLNoParam();
	let filterCourses = {};
	const typePagination = lpGlobalSettings.lpArchivePaginationType || 'number';
	let typeEventBeforeFetch;
	const fetchAPI = ( args, callBack = {} ) => {
		console.log( 'Fetch API Courses' );
		const url = lpAddQueryArgs( API.apiCourses, args );
		let paramsFetch = {};

		if ( 0 !== lpGlobalSettings.user_id ) {
			paramsFetch = {
				headers: {
					'X-WP-Nonce': lpGlobalSettings.nonce,
				},
			};
		}

		lpFetchAPI( url, paramsFetch, callBack );
	};
	return {
		init: () => {
			const urlParams = {};
			const urlQueryString = window.location.search;
			const urlSearchParams = new URLSearchParams( urlQueryString );
			for ( const [ key, val ] of urlSearchParams.entries() ) {
				urlParams[ key ] = val;
			}

			filterCourses = { ...lpGlobalSettings.lpArchiveSkeleton, ...urlParams };
			filterCourses.paged = parseInt( filterCourses.paged || 1 );
			window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( filterCourses ) );
		},
		updateEventTypeBeforeFetch: ( type ) => {
			typeEventBeforeFetch = type;
		},
		onChangeSortBy: ( e, target ) => {
			if ( ! target.classList.contains( 'course-order-by' ) ) {
				return;
			}

			e.preventDefault();
			let filterCoursesParams = window.localStorage.getItem( 'lp_filter_courses' );
			filterCoursesParams = JSON.parse( filterCoursesParams ) || {};
			filterCoursesParams.order_by = target.value;
			window.location.href = lpAddQueryArgs( currentUrl, filterCoursesParams );
		},
		onChangeTypeLayout: ( e, target ) => {
			if ( 'lp-switch-layout-btn' !== target.getAttribute( 'name' ) ) {
				return;
			}
			const elArchive = target.closest( `.${ classArchiveCourse }` );
			if ( ! elArchive ) {
				return;
			}
			const elListCourse = elArchive.querySelector( `.${ classListCourse }` );
			if ( ! elListCourse ) {
				return;
			}
			e.preventDefault();
			const layout = e.target.value;
			if ( layout ) {
				elListCourse && ( elListCourse.dataset.layout = layout );
				Cookies.set( 'courses-layout', layout );
			}
		},
		clickNumberPage: ( e, target ) => {
			const enableLoadAjax = parseInt( lpGlobalSettings.lpArchiveLoadAjax || 0 );
			if ( 0 === enableLoadAjax ) {
				return;
			}
			const parent = target.closest( '.page-numbers' );
			if ( target.classList.contains( 'page-numbers' ) ) {
				const parentArchive = target.closest( `.${ classArchiveCourse }` );
				if ( ! parentArchive ) {
					return;
				}

				e.preventDefault();

				const pageCurrent = parseInt( filterCourses.paged || 1 );

				if ( parent.classList.contains( 'prev' ) ) {
					filterCourses.paged = pageCurrent - 1;
				} else if ( parent.classList.contains( 'next' ) ) {
					filterCourses.paged = pageCurrent + 1;
				} else {
					filterCourses.paged = parseInt( target.textContent );
				}

				typeEventBeforeFetch = 'number';
				window.lpCourseList.triggerFetchAPI( filterCourses );
			} else if ( parent ) {
				e.preventDefault();
				parent.click();
			}
		},
		clickLoadMore: ( e, target ) => {
			if ( ! target.classList.contains( 'courses-btn-load-more' ) ) {
				return;
			}

			e.preventDefault();
			++filterCourses.paged;
			typeEventBeforeFetch = 'load-more';
			window.lpCourseList.triggerFetchAPI( filterCourses );
		},
		scrollInfinite: ( e, target ) => {
			const elArchive = document.querySelector( '.lp-archive-courses' );
			if ( ! elArchive ) {
				return;
			}

			const elInfinite = elArchive.querySelector( '.courses-load-infinite' );
			if ( ! elInfinite ) {
				return;
			}

			// Create an IntersectionObserver object.
			const observer = new IntersectionObserver( function( entries ) {
				for ( const entry of entries ) {
					// If the entry is intersecting, load the image.
					if ( entry.isIntersecting ) {
						if ( isLoadingInfinite ) {
							return;
						}

						++filterCourses.paged;
						typeEventBeforeFetch = 'infinite';
						window.lpCourseList.triggerFetchAPI( filterCourses );

						//observer.unobserve( entry.target );
					}
				}
			} );

			observer.observe( elInfinite );
		},
		triggerFetchAPI: ( args ) => { // For case, click on pagination, filter.
			const elArchive = document.querySelector( '.lp-archive-courses' );
			if ( ! elArchive ) {
				return;
			}
			const elListCourse = elArchive.querySelector( '.learn-press-courses' );
			if ( ! elListCourse ) {
				return;
			}

			filterCourses = args;

			let callBack;
			switch ( typeEventBeforeFetch ) {
			case 'load-more':
				callBack = window.lpCourseList.callBackPaginationTypeLoadMore( args, elArchive, elListCourse );
				break;
			case 'infinite':
				callBack = window.lpCourseList.callBackPaginationTypeInfinite( elArchive, elListCourse );
				break;
			case 'filter':
				callBack = window.lpCourseList.callBackFilter( args, elArchive, elListCourse );
				break;
			case 'custom':
				callBack = args.customCallBack || false;
				break;
			default: // number
				// Change url by params filter courses
				const urlPush = lpAddQueryArgs( currentUrl, args );
				window.history.pushState( '', '', urlPush );

				// Save filter courses to Storage
				window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( args ) );

				callBack = window.lpCourseList.callBackPaginationTypeNumber( elListCourse );
				break;
			}

			if ( ! callBack ) {
				return;
			}

			console.log( 'Args', args );

			fetchAPI( args, callBack );
		},
		callBackFilter: ( args, elArchive, elListCourse ) => {
			if ( ! elListCourse ) {
				return;
			}

			const skeleton = elListCourse.querySelector( '.lp-archive-course-skeleton' );
			if ( ! skeleton ) {
				return;
			}

			return {
				before: () => {
					args.paged = 1;
					window.history.pushState( '', '', lpAddQueryArgs( currentUrl, args ) );
					window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( args ) );
					if ( skeleton ) {
						skeleton.style.display = 'block';
					}
				},
				success: ( res ) => {
					// Remove all items before insert new items.
					const elLis = elListCourse.querySelectorAll( ':not(.lp-archive-course-skeleton)' );
					elLis.forEach( ( elLi ) => {
						const parent = elLi.closest( '.lp-archive-course-skeleton' );
						if ( parent ) {
							return;
						}
						elLi.remove();
					} );

					// Insert new items.
					elListCourse.insertAdjacentHTML( 'afterbegin', res.data.content || '' );

					// Check if Pagination exists will remove.
					const elPagination = document.querySelector( `.${ classPaginationCourse }` );
					if ( elPagination ) {
						elPagination.remove();
					}

					// Insert Pagination.
					const pagination = res.data.pagination || '';
					elListCourse.insertAdjacentHTML( 'afterend', pagination );
				},
				error: ( error ) => {
					elListCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error' }</div>`;
				},
				completed: () => {
					if ( skeleton ) {
						skeleton.style.display = 'none';
					}
					// Scroll to archive element
					const optionScroll = { behavior: 'smooth' };
					elListCourse.closest( '.lp-archive-courses' ).scrollIntoView( optionScroll );
				},
			};
		},
		callBackPaginationTypeNumber: ( elListCourse ) => {
			if ( ! elListCourse ) {
				return;
			}
			const skeleton = elListCourse.querySelector( '.lp-archive-course-skeleton' );

			return {
				before: () => {
					skeleton.style.display = 'block';
				},
				success: ( res ) => {
					// Remove all items before insert new items.
					const elLis = elListCourse.querySelectorAll( ':not(.lp-archive-course-skeleton)' );
					elLis.forEach( ( elLi ) => {
						const parent = elLi.closest( '.lp-archive-course-skeleton' );
						if ( parent ) {
							return;
						}
						elLi.remove();
					} );

					// Insert new items.
					skeleton.insertAdjacentHTML( 'beforebegin', res.data.content || '' );

					// Delete Pagination if exists.
					skeleton.style.display = 'block';
					const paginationEle = document.querySelector( `.${ classPaginationCourse }` );
					if ( paginationEle ) {
						paginationEle.remove();
					}
					// Insert Pagination.
					const pagination = res.data.pagination || '';
					elListCourse.insertAdjacentHTML( 'afterend', pagination );
				},
				error: ( error ) => {
					elListCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error' }</div>`;
				},
				completed: () => {
					skeleton.style.display = 'none';
					// Scroll to archive element
					const optionScroll = { behavior: 'smooth' };
					elListCourse.closest( '.lp-archive-courses' ).scrollIntoView( optionScroll );
				},
			};
		},
		callBackPaginationTypeLoadMore: ( args, elArchive, elListCourse ) => {
			console.log( 'callBackPaginationTypeLoadMore' );
			if ( ! elListCourse ) {
				return false;
			}
			const btnLoadMore = elArchive.querySelector( '.courses-btn-load-more' );
			let elLoading;
			if ( btnLoadMore ) {
				elLoading = btnLoadMore.querySelector( '.lp-loading-circle' );
			}
			const skeleton = document.querySelector( '.lp-archive-course-skeleton' );

			if ( args.eventFilter === 1 ) {
				window.history.pushState( '', '', lpAddQueryArgs( currentUrl, args ) );
			}

			if ( args.paged === 1 ) {
				if ( btnLoadMore ) {
					btnLoadMore.remove();
				}

				if ( skeleton ) {
					skeleton.style.display = 'block';
				}
			}

			return {
				before: () => {
					if ( btnLoadMore ) {
						elLoading.classList.remove( 'hide' );
					}
				},
				success: ( res ) => {
					skeleton.style.display = 'none';
					if ( args.paged === 1 ) {
						elListCourse.innerHTML = skeleton.outerHTML;
					}

					elListCourse.insertAdjacentHTML( 'beforeend', res.data.content || '' );
					elListCourse.insertAdjacentHTML( 'afterend', res.data.pagination || '' );
				},
				error: ( error ) => {
					elListCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error' }</div>`;
				},
				completed: () => {
					if ( btnLoadMore ) {
						elLoading.classList.add( 'hide' );
						btnLoadMore.remove();
					}

					if ( args.eventFilter === 1 ) {
						// Scroll to archive element
						const optionScroll = { behavior: 'smooth' };
						elListCourse.closest( '.lp-archive-courses' ).scrollIntoView( optionScroll );
					}
				},
			};
		},
		callBackPaginationTypeInfinite: ( elArchive, elListCourse ) => {
			console.log( 'callBackPaginationTypeInfinite' );
			if ( ! elListCourse ) {
				return;
			}

			const elInfinite = elArchive.querySelector( '.courses-load-infinite' );
			if ( ! elInfinite ) {
				return;
			}
			const loading = elInfinite.querySelector( '.lp-loading-circle' );

			isLoadingInfinite = true;

			elInfinite.classList.remove( 'courses-load-infinite' );

			return {
				before: () => {
					loading.classList.remove( 'hide' );
				},
				success: ( res ) => {
					elListCourse.insertAdjacentHTML( 'beforeend', res.data.content || '' );

					if ( res.data.pagination ) {
						elListCourse.insertAdjacentHTML( 'afterend', res.data.pagination || '' );
					}
				},
				error: ( error ) => {
					elListCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error' }</div>`;
				},
				completed: () => {
					elInfinite.remove();
					isLoadingInfinite = false;
				},
			};
		},
		searchCourse: ( e, target ) => {
			if ( 'c_search' === target.name ) {
				e.preventDefault();

				const parentFormSearch = target.closest( 'form.search-courses' );
				if ( ! parentFormSearch ) {
					return;
				}

				const btnSearch = parentFormSearch.querySelector( 'button[type="submit"]' );
				btnSearch.click();
				return;
			}

			if ( ! target.classList.contains( 'search-courses' ) ) {
				return;
			}
			const formSearchCourses = target;
			e.preventDefault();
			const elArchive = formSearchCourses.closest( `.${ classArchiveCourse }` );
			if ( ! elArchive ) {
				return;
			}
			const elListCourse = elArchive.querySelector( `.${ classListCourse }` );
			if ( ! elListCourse ) {
				return;
			}

			const elInputSearch = formSearchCourses.querySelector( 'input[name=c_search]' );
			const keyword = elInputSearch.value;

			if ( ! keyword || ( keyword && keyword.length > 2 ) ) {
				if ( undefined !== timeOutSearch ) {
					clearTimeout( timeOutSearch );
				}

				timeOutSearch = setTimeout( function() {
					typeEventBeforeFetch = 'filter';
					filterCourses.c_search = keyword;
					filterCourses.paged = 1;

					window.lpCourseList.triggerFetchAPI( filterCourses );
				}, 800 );
			}
		},
		ajaxEnableLoadPage: () => { // For case enable AJAX when load page.
			const countTime = 0;

			if ( ! lpGlobalSettings.lpArchiveNoLoadAjaxFirst ) {
				let detectedElArchivex;
				const callBack = {
					success: ( res ) => {
						detectedElArchivex = setInterval( function() {
							const skeleton = document.querySelector( '.lp-archive-course-skeleton' );
							const elArchive = document.querySelector( `.${ classArchiveCourse }` );
							let elListCourse;
							if ( elArchive ) {
								elListCourse = elArchive.querySelector( '.learn-press-courses' );
							}

							if ( countTime > 5000 ) {
								clearInterval( detectedElArchivex );
							}

							if ( elListCourse && skeleton ) {
								clearInterval( detectedElArchivex );
								elListCourse.insertAdjacentHTML( 'afterbegin', res.data.content || '' );
								skeleton.style.display = 'none';

								const pagination = res.data.pagination || '';
								elListCourse.insertAdjacentHTML( 'afterend', pagination );
							}
						}, 1 );
					},
				};

				if ( 'number' !== typePagination ) {
					filterCourses.paged = 1;
				}
				fetchAPI( filterCourses, callBack );
			}
		},
	};
} )();

window.lpCourseList.init();
window.lpCourseList.ajaxEnableLoadPage();
