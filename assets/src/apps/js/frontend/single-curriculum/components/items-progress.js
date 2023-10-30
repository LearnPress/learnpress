// Rest API load content course progress - Nhamdv.
import { addQueryArgs } from '@wordpress/url';
import lpModalOverlayCompleteItem from '../../show-lp-overlay-complete-item';

export const itemsProgress = () => {
	const elements = document.querySelectorAll( '.popup-header__inner' );

	if ( ! elements.length ) {
		return;
	}

	if ( document.querySelector( '#learn-press-quiz-app div.quiz-result' ) !== null ) {
		return;
	}

	if ( elements[ 0 ].querySelectorAll( 'form.form-button-finish-course' ).length !== 0 ) {
		return;
	}

	const user_id = lpGlobalSettings.user_id || 0;
	if ( user_id === 0 ) {
		return;
	}

	if ( 'IntersectionObserver' in window ) {
		const eleObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					const ele = entry.target;

					getResponse( ele );

					eleObserver.unobserve( ele );
				}
			} );
		} );

		[ ...elements ].map( ( ele ) => eleObserver.observe( ele ) );
	}
};

export const getResponse = async ( ele ) => {
	const response = await wp.apiFetch( {
		path: addQueryArgs( 'lp/v1/lazy-load/items-progress', {
			courseId: lpGlobalSettings.post_id || '',
			userId: lpGlobalSettings.user_id || '',
		} ),
		method: 'GET',
	} );

	const { data } = response;

	ele.innerHTML += data;

	lpModalOverlayCompleteItem.init();
};
