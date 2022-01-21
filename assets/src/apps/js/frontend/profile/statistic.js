import { addQueryArgs } from '@wordpress/url';

// Rest API load content course progress - Nhamdv.
const courseStatistics = () => {
	const elements = document.querySelector( '.learn-press-profile-course__statistic' );

	if ( ! elements ) {
		return;
	}

	const getResponse = ( ele, dataset ) => {
		wp.apiFetch( {
			path: addQueryArgs( 'lp/v1/profile/statistic', dataset ),
			method: 'GET',
		} ).then( ( response ) => {
			if ( response.status === 'success' && response.data ) {
				ele.innerHTML = response.data;
			} else {
				ele.innerHTML = `<div class="lp-ajax-message error" style="display:block">${ response.message && response.message }</div>`;
			}
		} ).catch( ( err ) => {
			console.log( err );
			//ele.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ err.message && err.message }</div>`;
		} );
	};

	const elArgStatistic = document.querySelector( '[name="args_query_user_courses_statistic"]' );
	if ( ! elArgStatistic ) {
		return;
	}

	const data = JSON.parse( elArgStatistic.value );

	getResponse( elements, data );
};
export default courseStatistics;
