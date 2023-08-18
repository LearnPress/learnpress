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
	let typePagination = 'number';
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
	const callBackFilter = ( elCoursesWrapper ) => {
		if ( ! elCoursesWrapper ) {
			return;
		}
		const skeleton = elCoursesWrapper.querySelector( `.${ classSkeleton }` );

		return {
			before: () => {

			},
			success: ( res ) => {
				elCoursesWrapper.innerHTML = res.data.content;
			},
			error: ( error ) => {

			},
			completed: () => {
				if ( skeleton ) {
					skeleton.style.display = 'none';
				}
			},
		};
	};
	const callBackPaginationTypeLoadMore = () => {
		return {
			before: () => {

			},
			success: ( res ) => {

			},
			error: ( error ) => {

			},
			completed: () => {
			},
		};
	};
	const callBackPaginationTypeInfinite = () => {
		return {
			before: () => {

			},
			success: ( res ) => {

			},
			error: ( error ) => {

			},
			completed: () => {
			},
		};
	};
	const callApiCoursesOfWidget = ( elCoursesWrapper, args = {} ) => {
		console.log( '/*** loadApiCoursesOfWidget ***/' );
		const idWidget = elCoursesWrapper.dataset.widgetId;
		let settingsWidget = window[ `lpWidget_${ idWidget }` ];

		if ( ! settingsWidget ) {
			return;
		}
		if ( 'yes' !== settingsWidget.courses_rest ) {
			return;
		}

		settingsWidget = { ...settingsWidget, ...args };

		urlAPI = settingsWidget.lp_rest_url ?? '';
		typePagination = settingsWidget.courses_rest_pagination_type ?? 'number';

		let callBack;
		switch ( typePagination ) {
		case 'load-more':
			callBack = callBackPaginationTypeLoadMore();
			break;
		case 'infinite':
			callBack = callBackPaginationTypeInfinite();
			break;
		default: // number
			callBack = callBackFilter( elCoursesWrapper );
			break;
		}

		if ( ! callBack ) {
			return;
		}

		fetchAPI( settingsWidget, callBack );
	};
	const findAllWidgetCoursesByPage = () => {
		const elCoursesWrappers = document.querySelectorAll( `.${ classCoursesWrapper }` );
		if ( ! elCoursesWrappers ) {
			return;
		}

		elCoursesWrappers.forEach( ( el ) => {
			callApiCoursesOfWidget( el );
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
			clickNumberPage( e, target );
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
	const clickNumberPage = ( e, target ) => {
		if ( ! target.classList.contains( 'page-numbers' ) ) {
			if ( ! target.closest( '.page-numbers' ) ) {
				return;
			}
			target = target.closest( '.page-numbers' );
		}
		const elCoursesWrapper = target.closest( `.${ classCoursesWrapper }` );
		if ( ! elCoursesWrapper ) {
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

		callApiCoursesOfWidget( elCoursesWrapper, filterCourses );
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
