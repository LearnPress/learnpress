import API from '../api';
import { lpAddQueryArgs } from '../utils';
import Cookies from '../utils/cookies';

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
document.addEventListener( 'scroll', function( e ) {
	window.lpCoursesList.LoadInfinite( e );
} );
document.addEventListener( 'keyup', function( e ) {
	const target = e.target;
} );
document.addEventListener( 'submit', function( e ) {
	const target = e.target;

	//window.lpCourseList.searchCourse( e, target );
} );

const elListenScroll = [];
window.lpCoursesList = ( () => {
	const classListCourseWrapper = '.learn-press-courses-wrapper';
	const classListCourse = '.learn-press-courses';
	const classLPTarget = '.lp-target';
	return {
		clickNumberPage: ( e, target ) => {
			const btnNumber = target.closest( '.page-numbers' );
			if ( ! btnNumber ) {
				return;
			}

			const elLPTarget = btnNumber.closest( `${ classLPTarget }` );
			if ( ! elLPTarget ) {
				return;
			}

			e.preventDefault();

			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			if ( ! dataSend.args.hasOwnProperty( 'paged' ) ) {
				dataSend.args.paged = 1;
			}

			if ( btnNumber.classList.contains( 'prev' ) ) {
				dataSend.args.paged--;
			} else if ( btnNumber.classList.contains( 'next' ) ) {
				dataSend.args.paged++;
			} else {
				dataSend.args.paged = btnNumber.textContent;
			}

			elLPTarget.dataset.send = JSON.stringify( dataSend );

			// Scroll to archive element
			const elCoursesWrapper = elLPTarget.closest( `${ classListCourseWrapper }` );
			if ( elCoursesWrapper ) {
				const optionScroll = { behavior: 'smooth' };
				elCoursesWrapper.scrollIntoView( optionScroll );
				//window.scrollBy( 0, -40 );
			}

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
		},
		LoadMore: ( e, btnLoadMore ) => {
			if ( ! btnLoadMore.classList.contains( 'courses-btn-load-more' ) ) {
				return;
			}

			const elLPTarget = btnLoadMore.closest( `${ classLPTarget }` );
			if ( ! elLPTarget ) {
				return;
			}

			e.preventDefault();

			const elLoading = btnLoadMore.querySelector( '.lp-loading-circle' );
			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			if ( ! dataSend.args.hasOwnProperty( 'paged' ) ) {
				dataSend.args.paged = 1;
			}

			dataSend.args.paged++;
			elLPTarget.dataset.send = JSON.stringify( dataSend );
			elLoading.classList.remove( 'hide' );

			const callBack = {
				success: ( response ) => {
					const { status, message, data } = response;

					const newEl = document.createElement( 'div' );
					newEl.innerHTML = data.content || '';
					const elListCourse = elLPTarget.querySelector( `${ classListCourse }` );

					elListCourse.insertAdjacentHTML( 'beforeend', newEl.querySelector( `${ classListCourse }` ).innerHTML );

					if ( data.total_pages === data.paged ) {
						const elPagination = elLPTarget.querySelector( '.learn-press-pagination' );
						elPagination.remove();
					}
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					console.log( 'completed' );
					elLoading.classList.add( 'hide' );
				},
			};

			window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
		},
		LoadInfinite: ( e ) => {
			const elInfinite = document.querySelector( '.courses-load-infinite' );
			if ( ! elInfinite ) {
				return;
			}

			const elLPTarget = elInfinite.closest( classLPTarget );
			if ( ! elLPTarget ) {
				return;
			}

			// Check if el scroll is registered listener observer, will return.
			const lpTargetId = elLPTarget.dataset.id;
			if ( elListenScroll.indexOf( lpTargetId ) !== -1 ) {
				return;
			}
			elListenScroll.push( lpTargetId );

			// Create an IntersectionObserver object.
			const observer = new IntersectionObserver( function( entries ) {
				for ( const entry of entries ) {
					// If the entry is intersecting, call API.
					if ( entry.isIntersecting ) {
						const elInfinite = entry.target;
						const elLoading = elInfinite.querySelector( '.lp-loading-circle' );
						elLoading.classList.remove( 'hide' );
						const elLPTarget = elInfinite.closest( classLPTarget );
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

								elListCourse.insertAdjacentHTML( 'beforeend', newEl.querySelector( classListCourse ).innerHTML );

								if ( data.total_pages === data.paged ) {
									elInfinite.remove();
								}
							},
							error: ( error ) => {
								console.log( error );
							},
							completed: () => {
								//console.log( 'completed' );
							},
						};

						window.lpAJAXG.fetchAPI( API.frontend.apiAJAX, dataSend, callBack );
					}
				}
			} );

			observer.observe( elInfinite );
		},
		onChangeSortBy: ( e, target ) => {
			if ( ! target.classList.contains( 'courses-order-by' ) ) {
				return;
			}

			const elLPTarget = target.closest( classLPTarget );
			if ( ! elLPTarget ) {
				return;
			}

			e.preventDefault();

			const dataObj = JSON.parse( elLPTarget.dataset.send );
			const dataSend = { ...dataObj };
			dataSend.args.paged = 1;
			dataSend.args.order_by = target.value || '';
			elLPTarget.dataset.send = JSON.stringify( dataSend );

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
	};
} )();
