import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

export default function lpMaterialsLoad( is_curriculum = false ) {
	// console.log('loaded');
	const Sekeleton = () => {
		const elementSkeleton = document.querySelector( '.lp-material-skeleton' );

		if ( ! elementSkeleton ) {
			return;
		}
		const loadMoreBtn = elementSkeleton.querySelector( '.lp-loadmore-material' );
		elementSkeleton.querySelector( '.course-material-table' ).style.display = 'none';
		loadMoreBtn.style.display = 'none';
		getResponse( elementSkeleton );
	};
	const getResponse = async ( ele, page = 1 ) => {
		let itemID = 0;
		if ( is_curriculum ) {
			const elCurriculum = document.querySelector( '.learnpress-course-curriculum' );
			if ( ! elCurriculum ) {
				return;
			}
			const itemId = elCurriculum.dataset.id;
			itemID = itemId || 0;
		} else {
			itemID = lpGlobalSettings.post_id;
		}
		const elementMaterial = ele.querySelector( '.course-material-table' );
		const loadMoreBtn = document.querySelector( '.lp-loadmore-material' );
		const elListItems = document.querySelector( '.lp-list-material' );
		try {
			const response = await apiFetch( {
				path: addQueryArgs( `lp/v1/material/item-materials/${ itemID }`, {
					page,
				} ),
				method: 'GET',
			} );
			const { data, status, message } = response;

			if ( status !== 'success' ) {
				return console.log( message );
			}

			if ( data.items && data.items.length > 0 ) {
				if ( ele.querySelector( '.lp-skeleton-animation' ) ) {
					ele.querySelector( '.lp-skeleton-animation' ).remove();
				}

				elementMaterial.style.display = 'table';
				elementMaterial.querySelector( 'tbody' ).insertAdjacentHTML( 'beforeend', data.items );
			} else {
				elListItems.innerHTML = message;
			}

			if ( data.load_more ) {
				loadMoreBtn.style.display = 'inline-block';
				loadMoreBtn.setAttribute( 'page', page + 1 );
				if ( loadMoreBtn.classList.contains( 'loading' ) ) {
					loadMoreBtn.classList.remove( 'loading' );
				}
			} else {
				loadMoreBtn.style.display = 'none';
			}
		} catch ( error ) {
			console.log( error.message );
		}
	};

	Sekeleton();
	document.addEventListener( 'click', function( e ) {
		const target = e.target;
		if ( target.classList.contains( 'lp-loadmore-material' ) ) {
			const elementSkeleton = document.querySelector( '.lp-material-skeleton' );
			const page = parseInt( target.getAttribute( 'page' ) );
			target.classList.add( 'loading' );
			getResponse( elementSkeleton, page );
			// target.classList.remove( 'loading' );
		}
	} );
}
