/**
 * Handle events for courses list.
 *
 * @since 4.2.5.8
 * @version 1.0.1
 */

import API from '../api';
import { lpAddQueryArgs, lpGetCurrentURLNoParam, listenElementViewed, listenElementCreated } from '../utils.js';

if ( 'undefined' === typeof lpData ) {
	console.log( 'lpData is undefined' );
}

// Events
document.addEventListener( 'change', function( e ) {
	const target = e.target;

	window.lpCoursesList.onChangeSortBy( e, target );
	window.lpCoursesList.onChangeTypeLayout( e, target );
} );
document.addEventListener( 'click', function( e ) {
	const target = e.target;

	window.lpCoursesList.clickNumberPage( e, target );
	window.lpCoursesList.LoadMore( e, target );
} );

document.addEventListener( 'keyup', function( e ) {
	const target = e.target;

	window.lpCoursesList.searchCourse( e, target );
} );
document.addEventListener( 'submit', function( e ) {
	const target = e.target;

	//window.lpCourseList.searchCourse( e, target );
} );

let timeOutSearch;
window.lpCoursesList = ( () => {
	const classListCourse = '.lp-list-courses-no-css';
	const classLPTarget = '.lp-target';
	const classLoadMore = 'courses-btn-load-more-no-css';
	const classPageResult = '.courses-page-result';
	const classLoading = '.lp-loading-no-css';
	const urlCurrent = lpGetCurrentURLNoParam();
	return {
		clickNumberPage: ( e, target ) => {
			const btnNumber = target.closest( '.page-numbers:not(.disabled)' );
			if ( ! btnNumber ) {
				return;
			}

			const elLPTarget = btnNumber.closest( `${ classLPTarget }` );
			if ( ! elLPTarget ) {
				return;
			}

			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			if ( ! dataSend.args.hasOwnProperty( 'paged' ) ) {
				dataSend.args.paged = 1;
			}

			// If no load ajax, will return.
			if ( dataSend.args.courses_load_ajax === 0 ) {
				return;
			}

			e.preventDefault();

			if ( btnNumber.classList.contains( 'prev' ) ) {
				dataSend.args.paged--;
			} else if ( btnNumber.classList.contains( 'next' ) ) {
				dataSend.args.paged++;
			} else {
				dataSend.args.paged = btnNumber.textContent;
			}

			elLPTarget.dataset.send = JSON.stringify( dataSend );

			// Set url params to reload page.
			// Todo: need check allow set url params.
			lpData.urlParams.paged = dataSend.args.paged;
			window.history.pushState( {}, '', lpAddQueryArgs( urlCurrent, lpData.urlParams ) );
			// End.

			// Show loading
			const elLoading = elLPTarget.closest( 'div:not(.lp-target)' ).querySelector( '.lp-loading-change' );
			if ( elLoading ) {
				elLoading.style.display = 'block';
			}
			// End

			// Scroll to archive element
			const elLPTargetY = elLPTarget.getBoundingClientRect().top + window.scrollY;
			window.scrollTo( { top: elLPTargetY } );

			const callBack = {
				success: ( response ) => {
					//console.log( 'response', response );
					const { status, message, data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					//console.log( 'completed' );
					if ( elLoading ) {
						elLoading.style.display = 'none';
					}
				},
			};

			window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
		},
		LoadMore: ( e, target ) => {
			const btnLoadMore = target.closest( `.${ classLoadMore + ':not(.disabled)' }` );
			if ( ! btnLoadMore ) {
				return;
			}

			const elLPTarget = btnLoadMore.closest( `${ classLPTarget }` );
			if ( ! elLPTarget ) {
				return;
			}

			e.preventDefault();
			btnLoadMore.classList.add( 'disabled' );

			const elLoading = btnLoadMore.querySelector( classLoading );
			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			if ( ! dataSend.args.hasOwnProperty( 'paged' ) ) {
				dataSend.args.paged = 1;
			}

			dataSend.args.paged++;
			elLPTarget.dataset.send = JSON.stringify( dataSend );
			if ( elLoading ) {
				elLoading.classList.remove( 'hide' );
			}

			const callBack = {
				success: ( response ) => {
					const { status, message, data } = response;
					const paged = parseInt( data.paged );
					const totalPages = parseInt( data.total_pages );

					const newEl = document.createElement( 'div' );
					newEl.innerHTML = data.content || '';
					const elListCourse = elLPTarget.querySelector( classListCourse );
					const elPageResult = elLPTarget.querySelector( classPageResult );
					const elPageResultNew = newEl.querySelector( classPageResult );

					elListCourse.insertAdjacentHTML( 'beforeend', newEl.querySelector( classListCourse ).innerHTML );
					if ( elPageResult && elPageResultNew ) {
						elPageResult.innerHTML = elPageResultNew.innerHTML;
					}

					if ( paged >= totalPages ) {
						btnLoadMore.remove();
					}
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					//console.log( 'completed' );
					if ( elLoading ) {
						elLoading.classList.add( 'hide' );
					}
					btnLoadMore.classList.remove( 'disabled' );
				},
			};

			window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
		},
		LoadInfinite: () => {
			// When see element, will call API to load more items.
			const callBackAfterSeeItem = ( entry ) => {
				const elInfinite = entry.target;
				const elLoading = elInfinite.querySelector( `${ classLoading }:not(.disabled)` );
				if ( ! elLoading ) {
					return;
				}
				elLoading.classList.remove( 'hide' );
				elLoading.classList.add( 'disabled' );

				const elLPTarget = elInfinite.closest( classLPTarget );
				if ( ! elLPTarget ) {
					return;
				}

				const dataObj = JSON.parse( elLPTarget.dataset.send );
				const dataSend = { ...dataObj }; // Clone object

				if ( ! dataSend.args.hasOwnProperty( 'paged' ) ) {
					dataSend.args.paged = 1;
				}

				// Handle set data send to call API
				dataSend.args.paged++;
				elLPTarget.dataset.send = JSON.stringify( dataSend );
				const callBack = {
					success: ( response ) => {
						const { status, message, data } = response;

						const newEl = document.createElement( 'div' );
						newEl.innerHTML = data.content || '';
						const elListCourse = elLPTarget.querySelector( classListCourse );
						const elPageResult = elLPTarget.querySelector( classPageResult );
						const elPageResultNew = newEl.querySelector( classPageResult );

						elListCourse.insertAdjacentHTML( 'beforeend', newEl.querySelector( classListCourse ).innerHTML );
						if ( elPageResult && elPageResultNew ) {
							elPageResult.innerHTML = elPageResultNew.innerHTML;
						}

						if ( data.total_pages === data.paged ) {
							elInfinite.remove();
						}
					},
					error: ( error ) => {
						console.log( error );
					},
					completed: () => {
						//console.log( 'completed' );
						elLoading.classList.add( 'hide' );
						elLoading.classList.remove( 'disabled' );
					},
				};

				window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
			};

			// Listen el courses load infinite have just created.
			listenElementCreated( ( node ) => {
				if ( node.classList.contains( 'courses-load-infinite-no-css' ) ) {
					listenElementViewed( node, callBackAfterSeeItem );
				}
			} );

			// If el created on DOMContentLoaded.
			const elInfinite = document.querySelector( '.courses-load-infinite-no-css' );
			if ( elInfinite ) {
				listenElementViewed( elInfinite, callBackAfterSeeItem );
			}
		},
		onChangeSortBy: ( e, target ) => {
			if ( ! target.classList.contains( 'courses-order-by' ) ) {
				return;
			}

			const elLPTarget = target.closest( classLPTarget );
			if ( ! elLPTarget ) {
				lpData.urlParams.paged = 1;
				lpData.urlParams.order_by = target.value || '';
				window.location.href = lpAddQueryArgs( urlCurrent, lpData.urlParams );
				return;
			}

			e.preventDefault();

			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			dataSend.args.paged = 1;
			dataSend.args.order_by = target.value || '';
			elLPTarget.dataset.send = JSON.stringify( dataSend );

			// Set url params to reload page.
			// Todo: need check allow set url params.
			lpData.urlParams.paged = dataSend.args.paged;
			lpData.urlParams.order_by = dataSend.args.order_by;
			window.history.pushState( {}, '', lpAddQueryArgs( urlCurrent, lpData.urlParams ) );
			// End.

			// Show loading
			const elLoading = elLPTarget.closest( 'div:not(.lp-target)' ).querySelector( '.lp-loading-change' );
			if ( elLoading ) {
				elLoading.style.display = 'block';
			}
			// End

			const callBack = {
				success: ( response ) => {
					//console.log( 'response', response );
					const { status, message, data } = response;
					elLPTarget.innerHTML = data.content || '';
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					//console.log( 'completed' );
					if ( elLoading ) {
						elLoading.style.display = 'none';
					}
				},
			};

			window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
		},
		onChangeTypeLayout: ( e, target ) => {
			if ( 'lp-switch-layout-btn' !== target.getAttribute( 'name' ) ) {
				return;
			}

			const elListCourse = document.querySelector( classListCourse );
			if ( ! elListCourse ) {
				return;
			}
			e.preventDefault();
			const layout = target.value;
			if ( layout ) {
				elListCourse.dataset.layout = layout;
				window.wpCookies.set( 'courses-layout', layout, 24 * 60 * 60, '/' );
			}
		},
		searchCourse: ( e, target ) => {
			if ( 'c_search' !== target.name ) {
				return;
			}

			const elLPTarget = target.closest( classLPTarget );
			if ( ! elLPTarget ) {
				return;
			}

			e.preventDefault();
			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };

			const keyword = target.value;
			dataSend.args.c_search = keyword || '';
			dataSend.args.paged = 1;
			elLPTarget.dataset.send = JSON.stringify( dataSend );

			// Set url params to reload page.
			// Todo: need check allow set url params.
			lpData.urlParams.paged = dataSend.args.paged;
			lpData.urlParams.c_search = dataSend.args.c_search;
			window.history.pushState( {}, '', lpAddQueryArgs( lpGetCurrentURLNoParam(), lpData.urlParams ) );
			// End.

			if ( ! keyword || ( keyword && keyword.length > 2 ) ) {
				if ( undefined !== timeOutSearch ) {
					clearTimeout( timeOutSearch );
				}

				timeOutSearch = setTimeout( function() {
					const callBack = {
						success: ( response ) => {
							//console.log( 'response', response );
							const { status, message, data } = response;
							elLPTarget.innerHTML = data.content || '';
						},
						error: ( error ) => {
							console.log( error );
						},
						completed: () => {
							//console.log( 'completed' );
						},
					};

					window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
				}, 800 );
			}
		},
	};
} )();
window.lpCoursesList.LoadInfinite();
