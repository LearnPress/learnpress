
// Rest API load content in Tab Curriculum - Nhamdv.
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

export default function courseCurriculumSkeleton() {
	const Sekeleton = () => {
		const elements = document.querySelectorAll( '.learnpress-course-curriculum' );

		if ( ! elements.length ) {
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
	}

	const getResponse = async ( ele ) => {
		const skeleton = ele.querySelector( '.lp-skeleton-animation' );

		try {
			const response = await apiFetch( {
				path: addQueryArgs( 'lp/v1/lazy-load/course-curriculum', {
					courseId: lpGlobalSettings.post_id || '',
					page: 1,
				} ),
				method: 'GET',
			} );

			const { data, status, message } = response;

			if ( status  === 'error' ) {
				throw new Error( message || "Error" );
			}

			data && ele.insertAdjacentHTML( 'beforeend', data );
		} catch ( error ) {
			ele.insertAdjacentHTML( 'beforeend', `<div class="lp-ajax-message error" style="display:block">${ error.message || 'Error: Query lp/v1/lazy-load/course-curriculum' }</div>` );
		}

		skeleton && skeleton.remove();
	};

	Sekeleton();

	document.addEventListener( 'click',  ( e ) => {
		const sectionBtns = document.querySelectorAll( '.section-item__loadmore' );

		[...sectionBtns].map( async sectionBtn => {
			if ( sectionBtn.contains( e.target ) ) {
				const sectionItem = sectionBtn.parentNode;
				const sectionId = sectionItem.getAttribute( 'data-section-id' );
				const sectionContent = sectionItem.querySelector( '.section-content' );

				const paged = parseInt( sectionBtn.dataset.page );

				sectionBtn.classList.add( 'loading' );

				try {
					const response = await apiFetch( {
						path: addQueryArgs( 'lp/v1/lazy-load/course-curriculum-items', {
							sectionId: sectionId || '',
							page: paged + 1,
						} ),
						method: 'GET',
					} );

					const { data, pages, status, message } = response;

					if ( status === 'error' ) {
						throw new Error( message || "Error" );
					}

					if ( pages <= paged + 1 ) {
						sectionBtn.remove();
					} else {
						sectionBtn.dataset.page = paged + 1;
					}

					sectionContent.insertAdjacentHTML( 'beforeend', data );
				} catch( e ) {
					sectionContent.insertAdjacentHTML( 'beforeend', `<div class="lp-ajax-message error" style="display:block">${ e.message || 'Error: Query lp/v1/lazy-load/course-curriculum' }</div>` );
				}

				sectionBtn.classList.remove( 'loading' );
			}
		});

		// Load more Sections
		const moreSections = document.querySelectorAll( '.curriculum-more__button' );

		[ ...moreSections ].map( async moreSection => {
			if ( moreSection.contains( e.target ) ) {
				const paged = parseInt( moreSection.dataset.page );

				const sections = moreSection.parentNode.parentNode.querySelector( '.curriculum-sections' );

				if ( paged && sections ) {
					moreSection.classList.add( 'loading' );

					try{
						const response = await apiFetch( {
							path: addQueryArgs( 'lp/v1/lazy-load/course-curriculum', {
								courseId: lpGlobalSettings.post_id || '',
								page: paged + 1,
								loadMore: true,
							} ),
							method: 'GET',
						} );

						const { data, pages, status, message } = response;

						if ( status === 'error' ) {
							throw new Error( message || "Error" );
						}

						if ( pages <= paged + 1 ) {
							moreSection.remove();
						} else {
							moreSection.dataset.page = paged + 1;
						}

						sections.insertAdjacentHTML( 'beforeend', data );
					} catch( e ) {
						sections.insertAdjacentHTML( 'beforeend', `<div class="lp-ajax-message error" style="display:block">${ e.message || 'Error: Query lp/v1/lazy-load/course-curriculum' }</div>` );
					}

					moreSection.classList.remove( 'loading' );
				}
			}
		});

		// Show/Hide accordion
		if ( document.querySelector( '.learnpress-course-curriculum' ) ) {
			const sections = document.querySelectorAll( '.section' );

			[ ...sections ].map( section => {
				if ( section.contains( e.target ) ) {
					const toggle = section.querySelector( '.section-left' );

					toggle.contains( e.target ) && section.classList.toggle( 'closed' );
				}
			});
		}
	});
};
