import { lpAddQueryArgs, lpFetchAPI, lpGetCurrentURLNoParam } from '../../apps/js/utils/utils';
import Cookies from '../utils/cookies';

window.lpElWidgetCoursesByPage = ( () => {
	const classCoursesWrapper = 'list-courses-elm-wrapper';
	const classListCourse = 'list-courses-elm';
	const classPaginationCourse = 'learn-press-pagination';
	const classSkeleton = 'lp-skeleton-animation';
	const filterCourses = {};
	const currentUrl = lpGetCurrentURLNoParam();
	let urlAPI;
	let typeEventBeforeFetch;
	let timeOutSearch;
	const isLoadingInfinite = false;
	const fetchAPI = ( args, callBack = {} ) => {
		//console.log( 'Fetch API Courses' );
		const paramsFetch = {
			method: 'POST',
			body: JSON.stringify( args ),
			headers: {
				'Content-Type': 'application/json', // Set the content type to JSON
			},
		};

		if ( 0 !== args.current_user_id ) {
			paramsFetch.headers[ 'X-WP-Nonce' ] = args.nonce;
		}

		lpFetchAPI( urlAPI + 'lp/v1/courses/courses-widget-by-page', paramsFetch, callBack );
	};
	const triggerFetchAPI = ( args ) => { // For case, click on pagination, filter.
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
			callBack = callBackFilter( args, elArchive, elListCourse );
			break;
		}

		if ( ! callBack ) {
			return;
		}

		fetchAPI( args, callBack );
	};
	const callBackFilter = ( args, elCoursesWrapper, elListCourse ) => {
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
	};
	const loadApiCoursesOfWidget = ( elCoursesWrapper ) => {
		console.log( 'loadApiCoursesOfWidget' );
		const idWidget = elCoursesWrapper.dataset.widgetId;
		const settingsWidget = window[ `lpWidget_${ idWidget }` ];
		const skeleton = elCoursesWrapper.querySelector( `.${ classSkeleton }` );

		if ( ! settingsWidget ) {
			return;
		}
		if ( 'yes' !== settingsWidget.courses_rest ) {
			return;
		}

		urlAPI = settingsWidget.lp_rest_url ?? '';

		const callBack = {
			before: () => {

			},
			success: ( res ) => {
				elCoursesWrapper.insertAdjacentHTML( 'beforeend', res.data.content );
			},
			error: ( error ) => {

			},
			completed: () => {
				if ( skeleton ) {
					skeleton.style.display = 'none';
				}
			},
		};
		fetchAPI( settingsWidget, callBack );
	};
	const findAllWidgetCoursesByPage = () => {
		const elCoursesWrappers = document.querySelectorAll( `.${ classCoursesWrapper }` );
		if ( ! elCoursesWrappers ) {
			return;
		}

		elCoursesWrappers.forEach( ( el ) => {
			loadApiCoursesOfWidget( el );
		} );
	};
	const onChangeSortBy = ( e, target ) => {
		if ( ! target.classList.contains( 'courses-order-by' ) ) {
			return;
		}
		const elCoursesWrapper = target.closest( `.${ classCoursesWrapper }` );
		if ( ! elCoursesWrapper ) {
			return;
		}

		e.preventDefault();
		const idWidget = elCoursesWrapper.dataset.widgetId;
		const settingsWidget = window[ `lpWidget_${ idWidget }` ];
		if ( 'yes' !== settingsWidget.courses_rest ) {
			filterCourses.order_by = target.value;
			window.location.href = lpAddQueryArgs( currentUrl, filterCourses );
		} else {
			loadApiCoursesOfWidget( elCoursesWrapper );
		}
	};
	const onChangeTypeLayout = ( e, target ) => {

	};
	const events = () => {
		document.addEventListener( 'change', function( e ) {
			const target = e.target;

			onChangeSortBy( e, target );
			onChangeTypeLayout( e, target );
		} );
		document.addEventListener( 'click', function( e ) {
			const target = e.target;

			//window.lpCourseList.clickLoadMore( e, target );
			//window.lpCourseList.clickNumberPage( e, target );
		} );
		document.addEventListener( 'scroll', function( e ) {
			const target = e.target;

			//window.lpCourseList.scrollInfinite( e, target );
		} );
		document.addEventListener( 'keyup', function( e ) {
			const target = e.target;

			//window.lpCourseList.searchCourse( e, target );
		} );
		document.addEventListener( 'submit', function( e ) {
			const target = e.target;

			//window.lpCourseList.searchCourse( e, target );
		} );
	};
	return {
		init: () => {
			findAllWidgetCoursesByPage();
			events();
		},
	};
} )();

document.addEventListener( 'DOMContentLoaded', function() {
	window.lpElWidgetCoursesByPage.init();
} );
