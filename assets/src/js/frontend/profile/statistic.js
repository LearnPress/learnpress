import { lpAddQueryArgs, lpFetchAPI, lpOnElementReady } from '../../utils.js';

// Rest API load content course progress - Nhamdv.
const courseStatistics = () => {
	const loadAPICourseStatistic = ( elCourseStatistic ) => {
		let apiUrl = 'lp/v1/profile/student/statistic';
		const tabActive = document.querySelector( '.lp-profile-nav-tabs li.active' );
		if ( ! tabActive ) {
			return;
		}

		if ( tabActive.classList.contains( 'courses' ) ) {
			apiUrl = 'lp/v1/profile/instructor/statistic';
		}

		const elArgStatistic = elCourseStatistic.querySelector( '[name="args_query_user_courses_statistic"]' );
		if ( ! elArgStatistic ) {
			return;
		}

		const data = JSON.parse( elArgStatistic.value );

		const callBack = {
			success: ( response ) => {
				if ( response.status === 'success' && response.data ) {
					elCourseStatistic.innerHTML = response.data;
				} else {
					elCourseStatistic.innerHTML = `<div class="lp-ajax-message error" style="display:block">${ response.message && response.message }</div>`;
				}
			},
			error: ( error ) => {
				console.log( error );
			},
			completed: () => {

			},
		};

		apiUrl = lpAddQueryArgs( lpData.lp_rest_url + apiUrl, data );

		if ( 0 !== parseInt( lpData.user_id ) ) {
			data.headers = {
				'X-WP-Nonce': lpData.nonce,
			};
		}
		lpFetchAPI( apiUrl, data, callBack );
	};

	lpOnElementReady( '.learn-press-profile-course__statistic', ( elCourseStatistic ) => {
		loadAPICourseStatistic( elCourseStatistic );
	} );
};

export default courseStatistics;
