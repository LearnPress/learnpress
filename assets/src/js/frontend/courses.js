/**
 * @deprecated 4.2.5.8
 * But still support for theme has html old, override.
 * Todo: if want remove file, need to check theme override file archive, can via remove hook override file.
 */

import API from '../api';
import { lpAddQueryArgs, lpFetchAPI, lpGetCurrentURLNoParam } from '../utils';
import Cookies from '../utils/cookies';

const elListCoursesIdNewDefault = '.lp-list-courses-default';

if ( 'undefined' === typeof lpData || 'undefined' === typeof lpSettingCourses ) {
	console.log( 'lpData || lpSettingCourses is undefined' );
}

// Call API load courses.
// When LP v4.2.3.3 release a long time, we will remove this function on theme Eduma.
// assets/js/thim-course-filter-v2.js
window.lpArchiveRequestCourse = ( args ) => {
	window.lpCourseList.updateEventTypeBeforeFetch( 'filter' );
	window.lpCourseList.triggerFetchAPI( args );
};

// Events
document.addEventListener( 'change', function( e ) {
	const target = e.target;
	if ( window.lpCourseList.checkIsNewListCourses() ) {
		return;
	}

	window.lpCourseList.onChangeSortBy( e, target );
	window.lpCourseList.onChangeTypeLayout( e, target );
} );
document.addEventListener( 'click', function( e ) {
	const target = e.target;

	if ( window.lpCourseList.checkIsNewListCourses() ) {
		return;
	}

	window.lpCourseList.clickLoadMore( e, target );
	window.lpCourseList.clickNumberPage( e, target );
} );
document.addEventListener( 'scroll', function( e ) {
	const target = e.target;

	if ( window.lpCourseList.checkIsNewListCourses() ) {
		return;
	}

	window.lpCourseList.scrollInfinite( e, target );
} );
document.addEventListener( 'keyup', function( e ) {
	const target = e.target;

	if ( window.lpCourseList.checkIsNewListCourses() ) {
		return;
	}

	window.lpCourseList.searchCourse( e, target );
} );
document.addEventListener( 'submit', function( e ) {
	const target = e.target;

	if ( window.lpCourseList.checkIsNewListCourses() ) {
		return;
	}

	window.lpCourseList.searchCourse( e, target );
} );

window.lpCourseList = ( () => {
	const classArchiveCourse = 'lp-archive-courses';
	const classListCourse = 'learn-press-courses';
	const classPaginationCourse = 'learn-press-pagination';
	const classSkeletonArchiveCourse = 'lp-archive-course-skeleton';
	const classCoursesPageResult = 'courses-page-result';
	const lpArchiveLoadAjax = parseInt( lpSettingCourses.lpArchiveLoadAjax || 0 );
	const lpArchiveNoLoadAjaxFirst = parseInt( lpSettingCourses.lpArchiveNoLoadAjaxFirst ) === 1;
	const lpArchiveSkeletonParam = lpData.urlParams || [];
	const currentUrl = lpGetCurrentURLNoParam();
	let filterCourses = {};
	const typePagination = lpSettingCourses.lpArchivePaginationType || 'number';
	let typeEventBeforeFetch;
	let timeOutSearch;
	let isLoadingInfinite = false;
	const fetchAPI = ( args, callBack = {} ) => {
		//console.log( 'Fetch API Courses' );
		const url = lpAddQueryArgs( API.frontend.apiCourses, args );
		let paramsFetch = {};

		if ( 0 !== parseInt( lpData.user_id ) ) {
			paramsFetch = {
				headers: {
					'X-WP-Nonce': lpData.nonce,
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

			filterCourses = { ...lpArchiveSkeletonParam, ...urlParams };
			filterCourses.paged = parseInt( filterCourses.paged || 1 );
			if ( isNaN( filterCourses.paged ) ) {
				filterCourses.paged = 1;
			}
			if ( lpArchiveNoLoadAjaxFirst && typePagination !== 'number' ) {
				filterCourses.paged = 1;
			}
			window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( filterCourses ) );
		},
		updateEventTypeBeforeFetch: ( type ) => {
			typeEventBeforeFetch = type;
		},
		onChangeSortBy: ( e, target ) => {
			if ( ! target.classList.contains( 'courses-order-by' ) ) {
				return;
			}

			e.preventDefault();

			const filterCourses = JSON.parse( window.localStorage.getItem( 'lp_filter_courses' ) ) || {};
			filterCourses.order_by = target.value || '';

			if ( 'undefined' !== typeof lpSettingCourses &&
				lpData.is_course_archive &&
				lpSettingCourses.lpArchiveLoadAjax ) {
				window.lpCourseList.triggerFetchAPI( filterCourses );
			} else {
				window.location.href = lpAddQueryArgs( currentUrl, filterCourses );
			}
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
			const layout = target.value;
			if ( layout ) {
				elListCourse.dataset.layout = layout;
				Cookies.set( 'courses-layout', layout );
			}
		},
		clickNumberPage: ( e, target ) => {
			if ( ! lpArchiveLoadAjax || parseInt( lpSettingCourses.noLoadCoursesJs ) ) {
				return;
			}

			if ( target.classList.contains( 'page-numbers' ) ) {
				const parentArchive = target.closest( `.${ classArchiveCourse }` );
				if ( ! parentArchive ) {
					return;
				}

				e.preventDefault();
				const pageCurrent = filterCourses.paged;
				if ( target.classList.contains( 'prev' ) ) {
					filterCourses.paged = pageCurrent - 1;
				} else if ( target.classList.contains( 'next' ) ) {
					filterCourses.paged = pageCurrent + 1;
				} else {
					filterCourses.paged = parseInt( target.textContent );
				}

				typeEventBeforeFetch = 'number';
				window.lpCourseList.triggerFetchAPI( filterCourses );
				return;
			}

			const parent = target.closest( '.page-numbers' );
			if ( parent ) {
				e.preventDefault();
				parent.click();
			}
		},
		clickLoadMore: ( e, target ) => {
			if ( ! target.classList.contains( 'courses-btn-load-more' ) ) {
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
			++filterCourses.paged;
			typeEventBeforeFetch = 'load-more';
			window.lpCourseList.triggerFetchAPI( filterCourses );
		},
		scrollInfinite: ( e, target ) => {
			const elArchive = document.querySelector( `.${ classArchiveCourse }` );
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
			const elArchive = document.querySelector( `.${ classArchiveCourse }` );
			if ( ! elArchive ) {
				return;
			}
			const elListCourse = elArchive.querySelector( `.${ classListCourse }` );
			if ( ! elListCourse ) {
				return;
			}

			filterCourses = args;
			let callBack;
			switch ( typeEventBeforeFetch ) {
			case 'load-more':
				callBack = window.lpCourseList.callBackPaginationTypeLoadMore( elArchive, elListCourse );
				break;
			case 'infinite':
				callBack = window.lpCourseList.callBackPaginationTypeInfinite( elArchive, elListCourse );
				break;
			case 'custom':
				callBack = args.customCallBack || false;
				break;
			case 'filter':
			default: // number
				// Change url by params filter courses
				//callBack = window.lpCourseList.callBackPaginationTypeNumber( elListCourse );
				callBack = window.lpCourseList.callBackFilter( args, elArchive, elListCourse );
				break;
			}

			if ( ! callBack ) {
				return;
			}

			//console.log( 'Args', args );

			fetchAPI( args, callBack );
		},
		callBackFilter: ( args, elArchive, elListCourse ) => {
			if ( ! elListCourse ) {
				return;
			}
			const skeleton = elListCourse.querySelector( `.${ classSkeletonArchiveCourse }` );

			return {
				before: () => {
					window.history.pushState( '', '', lpAddQueryArgs( currentUrl, args ) );
					window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( args ) );
					if ( skeleton ) {
						skeleton.style.display = 'block';
					}
				},
				success: ( res ) => {
					// Remove all items before insert new items.
					const elLis = elListCourse.querySelectorAll( `:not(.${ classSkeletonArchiveCourse })` );
					elLis.forEach( ( elLi ) => {
						const parent = elLi.closest( `.${ classSkeletonArchiveCourse }` );
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

					// Set showing results page.
					const elCoursesPageResult = document.querySelector( `.${ classCoursesPageResult }` );
					if ( elCoursesPageResult ) {
						elCoursesPageResult.innerHTML = res.data.from_to || '';
					}
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
					elListCourse.closest( `.${ classArchiveCourse }` ).scrollIntoView( optionScroll );
				},
			};
		},
		/*callBackPaginationTypeNumber: ( elListCourse ) => {
			if ( ! elListCourse ) {
				return;
			}
			const skeleton = elListCourse.querySelector( `.${ classSkeletonArchiveCourse }` );

			return {
				before: () => {
					const urlPush = lpAddQueryArgs( currentUrl, args );
					window.history.pushState( '', '', urlPush );
					// Save filter courses to Storage
					window.localStorage.setItem( 'lp_filter_courses', JSON.stringify( args ) );
					if ( skeleton ) {
						skeleton.style.display = 'block';
					}
				},
				success: ( res ) => {
					// Remove all items before insert new items.
					const elLis = elListCourse.querySelectorAll( `:not(.${ classSkeletonArchiveCourse })` );
					elLis.forEach( ( elLi ) => {
						const parent = elLi.closest( `.${ classSkeletonArchiveCourse }` );
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
		},*/
		callBackPaginationTypeLoadMore: ( elArchive, elListCourse ) => {
			//console.log( 'callBackPaginationTypeLoadMore' );
			if ( ! elListCourse || ! elArchive ) {
				return false;
			}
			const btnLoadMore = elArchive.querySelector( '.courses-btn-load-more' );
			let elLoading;
			if ( btnLoadMore ) {
				elLoading = btnLoadMore.querySelector( '.lp-loading-circle' );
			}
			//const skeleton = document.querySelector( `.${ classSkeletonArchiveCourse }` );

			return {
				before: () => {
					if ( btnLoadMore ) {
						elLoading.classList.remove( 'hide' );
						btnLoadMore.setAttribute( 'disabled', 'disabled' );
					}

					/*if ( skeleton ) {
						skeleton.style.display = 'block';
					}*/
				},
				success: ( res ) => {
					elListCourse.insertAdjacentHTML( 'beforeend', res.data.content || '' );
					elListCourse.insertAdjacentHTML( 'afterend', res.data.pagination || '' );

					// Set showing results page.
					const elCoursesPageResult = document.querySelector( `.${ classCoursesPageResult }` );
					if ( elCoursesPageResult ) {
						elCoursesPageResult.innerHTML = res.data.from_to || '';
					}
				},
				error: ( error ) => {
					elListCourse.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error' }</div>`;
				},
				completed: () => {
					if ( btnLoadMore ) {
						elLoading.classList.add( 'hide' );
						btnLoadMore.remove();
					}

					/*if ( skeleton ) {
						skeleton.style.display = 'none';
					}*/
				},
			};
		},
		callBackPaginationTypeInfinite: ( elArchive, elListCourse ) => {
			//console.log( 'callBackPaginationTypeInfinite' );
			if ( ! elListCourse || ! elListCourse ) {
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

					// Set showing results page.
					const elCoursesPageResult = document.querySelector( `.${ classCoursesPageResult }` );
					if ( elCoursesPageResult ) {
						elCoursesPageResult.innerHTML = res.data.from_to || '';
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
			let countTime = 0;
			if ( ! lpArchiveNoLoadAjaxFirst ) {
				let detectedElArchive;
				const callBack = {
					success: ( res ) => {
						detectedElArchive = setInterval( function() {
							const skeleton = document.querySelector( `.${ classSkeletonArchiveCourse }` );
							const elArchive = document.querySelector( `.${ classArchiveCourse }` );
							let elListCourse;
							if ( elArchive ) {
								elListCourse = elArchive.querySelector( `.${ classListCourse }` );
							}

							++countTime;
							if ( countTime > 5000 ) {
								clearInterval( detectedElArchive );
							}

							if ( elListCourse && skeleton ) {
								clearInterval( detectedElArchive );
								elListCourse.insertAdjacentHTML( 'afterbegin', res.data.content || '' );
								skeleton.style.display = 'none';

								const pagination = res.data.pagination || '';
								elListCourse.insertAdjacentHTML( 'afterend', pagination );

								// Set showing results page.
								const elCoursesPageResult = document.querySelector( `.${ classCoursesPageResult }` );
								if ( elCoursesPageResult ) {
									elCoursesPageResult.innerHTML = res.data.from_to || '';
								}
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
		getFilterParams: () => {
			return filterCourses;
		},
		// Check has exists new list courses.
		checkIsNewListCourses: () => {
			const elListCoursesNew = document.querySelector( elListCoursesIdNewDefault );
			return !! elListCoursesNew;
		},
	};
} )();

document.addEventListener( 'DOMContentLoaded', function() {
	if ( window.lpCourseList.checkIsNewListCourses() ) {
		return;
	}

	window.lpCourseList.init();
	window.lpCourseList.ajaxEnableLoadPage();
} );
