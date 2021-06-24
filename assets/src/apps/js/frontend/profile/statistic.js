import { addQueryArgs } from '@wordpress/url';

// Rest API load content course progress - Nhamdv.
const courseStatistics = () => {
	const elements = document.querySelectorAll( '.learn-press-profile-course__statistic' );

	if ( ! elements.length ) {
		return;
	}

	if ( 'IntersectionObserver' in window ) {
		const eleObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					const ele = entry.target;
					const data = JSON.parse( ele.dataset.ajax );

					getResponse( ele, data );

					eleObserver.unobserve( ele );
				}
			} );
		} );

		[ ...elements ].map( ( ele ) => eleObserver.observe( ele ) );
	}

	const getResponse = async ( ele, dataset ) => {
		try {
			const response = await wp.apiFetch( {
				path: addQueryArgs( 'lp/v1/profile/statistic', dataset ),
				method: 'GET',
			} );

			if ( response.status === 'success' && response.data ) {
				ele.innerHTML = response.data;
			} else {
				ele.innerHTML = `<div class="lp-ajax-message error" style="display:block">${ response.message && response.message }</div>`;
			}
		} catch ( error ) {
			ele.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message && error.message }</div>`;
		}
	};
};
export default courseStatistics;
