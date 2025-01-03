import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

export default function lpMaterialsLoad() {
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
		const course_id = parseInt( ele.dataset.courseId ),
			  item_id = parseInt( ele.dataset.itemId );
		const elementMaterial = ele.querySelector( '.course-material-table' );
		const loadMoreBtn = document.querySelector( '.lp-loadmore-material' );
		const elListItems = document.querySelector( '.lp-list-material' );
		try {
			const response = await apiFetch( {
				path: `lp/v1/material/by-item`,
				data: {
					course_id,
					item_id,
					page,
				},
				method: 'POST',
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
