// Rest API load content in Tab Curriculum - Nhamdv.
import { addQueryArgs } from '@wordpress/url';
//import apiFetch from '@wordpress/api-fetch';
import scrollToItemCurrent from './scrolltoitem';
import { searchCourseContent } from './components/search';

export default function courseCurriculumSkeleton( courseID = '' ) {
	let isLoadingItems = false;
	let isLoadingSections = false;
	const Sekeleton = () => {
		const elementCurriculum = document.querySelector(
			'.learnpress-course-curriculum'
		);

		if ( ! elementCurriculum ) {
			return;
		}

		getResponse( elementCurriculum );
	};

	const getResponse = async ( ele ) => {
		const skeleton = ele.querySelector( '.lp-skeleton-animation' );
		const itemID = ele.dataset.id;
		const sectionID = ele.dataset.section;

		try {
			const page = 1;
			let url = lpData.lp_rest_url + 'lp/v1/lazy-load/course-curriculum/';
			url = addQueryArgs( url, {
				courseId: courseID || lpGlobalSettings.post_id || '',
				page,
				sectionID: sectionID || '',
			} );
			let paramsFetch = {};
			if ( 0 !== parseInt( lpData.user_id ) ) {
				paramsFetch = {
					headers: {
						'X-WP-Nonce': lpData.nonce,
					},
				};
			}

			let response = await fetch( url, { method: 'GET', ...paramsFetch } );
			response = await response.json();

			const { data, status, message } = response;
			const section_ids = data.section_ids;

			if ( status === 'error' ) {
				throw new Error( message || 'Error' );
			}

			const returnData = data.content;

			if ( sectionID ) {
				if ( section_ids && ! section_ids.includes( sectionID ) ) {
					const response2 = await getResponsive(
						'',
						page + 1,
						sectionID
					);

					if ( response2 ) {
						const { data2, pages2, page2 } = response2;

						await parseContentItems( {
							ele,
							returnData,
							sectionID,
							itemID,
							data2,
							pages2,
							page2,
						} );
					}
				} else {
					await parseContentItems( {
						ele,
						returnData,
						sectionID,
						itemID,
					} );
				}
			} else {
				returnData && ele.insertAdjacentHTML( 'beforeend', returnData );
			}
		} catch ( error ) {
			ele.insertAdjacentHTML(
				'beforeend',
				`<div class="lp-ajax-message error" style="display:block">${
					error.message ||
					'Error: Query lp/v1/lazy-load/course-curriculum'
				}</div>`
			);
		}

		skeleton && skeleton.remove();

		searchCourseContent();
	};

	const parseContentItems = async ( {
		ele,
		returnData,
		sectionID,
		itemID,
		data2,
		pages2,
		page2,
	} ) => {
		const parser = new DOMParser();
		const doc = parser.parseFromString( returnData, 'text/html' );

		if ( data2 ) {
			const sections = doc.querySelector( '.curriculum-sections' );

			const loadMoreBtn = doc.querySelector( '.curriculum-more__button' );

			if ( loadMoreBtn ) {
				if ( pages2 <= page2 ) {
					loadMoreBtn.remove();
				} else {
					loadMoreBtn.dataset.page = page2;
				}
			}

			sections.insertAdjacentHTML( 'beforeend', data2 );
		}

		const section = doc.querySelector( `[data-section-id="${ sectionID }"]` );

		if ( section ) {
			const items = section.querySelectorAll( '.course-item' );
			const item_ids = [ ...items ].map( ( item ) => item.dataset.id );
			const sectionContent = section.querySelector( '.section-content' );
			const itemLoadMore = section.querySelector(
				'.section-item__loadmore'
			);

			if ( itemID && ! item_ids.includes( itemID ) ) {
				const responseItem = await getResponsiveItem(
					'',
					2,
					sectionID,
					itemID
				);

				const { data3, pages3, paged3, page } = responseItem;

				if ( pages3 <= paged3 || pages3 <= page ) {
					itemLoadMore.remove();
				} else {
					itemLoadMore.dataset.page = page;
				}

				if ( data3 && sectionContent ) {
					sectionContent.insertAdjacentHTML( 'beforeend', data3 );
				}
			}
		}

		ele.insertAdjacentHTML( 'beforeend', doc.body.innerHTML );

		scrollToItemCurrent.init();
	};

	const getResponsiveItem = async ( returnData, paged, sectionID, itemID ) => {
		let url = lpData.lp_rest_url + 'lp/v1/lazy-load/course-curriculum-items/';
		url = addQueryArgs( url, {
			sectionId: sectionID || '',
			page: paged,
		} );
		let paramsFetch = {};
		if ( 0 !== parseInt( lpData.user_id ) ) {
			paramsFetch = {
				headers: {
					'X-WP-Nonce': lpData.nonce,
				},
			};
		}

		let response = await fetch( url, { method: 'GET', ...paramsFetch } );
		response = await response.json();

		const { data, status, pages, message } = response;

		const { page } = data;

		let item_ids;

		if ( status === 'success' ) {
			const dataTmp = data.content;
			item_ids = data.item_ids;

			returnData += dataTmp;

			if ( sectionID && item_ids && itemID && ! item_ids.includes( itemID ) ) {
				return getResponsiveItem(
					returnData,
					paged + 1,
					sectionID,
					itemID
				);
			}
		}

		isLoadingItems = false;

		return {
			data3: returnData,
			pages3: pages || data.pages,
			status3: status,
			message3: message,
			page: page || 0,
		};
	};

	const getResponsive = async ( returnData, page, sectionID ) => {
		let url = lpData.lp_rest_url + 'lp/v1/lazy-load/course-curriculum/';
		url = addQueryArgs( url, {
			courseId: courseID || lpGlobalSettings.post_id || '',
			page,
			sectionID: sectionID || '',
			loadMore: true,
		} );
		let paramsFetch = {};
		if ( 0 !== parseInt( lpData.user_id ) ) {
			paramsFetch = {
				headers: {
					'X-WP-Nonce': lpData.nonce,
				},
			};
		}

		let response = await fetch( url, { method: 'GET', ...paramsFetch } );
		response = await response.json();

		const { data, status, message } = response;

		const returnDataTmp = data.content;
		const section_ids = data.section_ids;
		const pages = data.pages;

		if ( status === 'success' ) {
			returnData += returnDataTmp;

			if (
				sectionID &&
				section_ids &&
				section_ids.length > 0 &&
				! section_ids.includes( sectionID )
			) {
				return getResponsive( returnData, page + 1, sectionID );
			}
		}

		isLoadingSections = false;

		return {
			data2: returnData,
			pages2: pages || data.pages,
			page2: page,
			status2: status,
			message2: message,
		};
	};

	Sekeleton();

	document.addEventListener( 'click', ( e ) => {
		const sectionBtns = document.querySelectorAll(
			'.section-item__loadmore'
		);

		[ ...sectionBtns ].map( async ( sectionBtn ) => {
			if ( sectionBtn.contains( e.target ) && ! isLoadingItems ) {
				isLoadingItems = true;
				const sectionItem = sectionBtn.parentNode;
				const sectionId = sectionItem.getAttribute( 'data-section-id' );
				const sectionContent =
					sectionItem.querySelector( '.section-content' );

				const paged = parseInt( sectionBtn.dataset.page );

				sectionBtn.classList.add( 'loading' );

				try {
					const response = await getResponsiveItem(
						'',
						paged + 1,
						sectionId,
						''
					);

					const { data3, pages3, status3, message3 } = response;

					if ( status3 === 'error' ) {
						throw new Error( message3 || 'Error' );
					}

					if ( pages3 <= paged + 1 ) {
						sectionBtn.remove();
					} else {
						sectionBtn.dataset.page = paged + 1;
					}

					sectionContent.insertAdjacentHTML( 'beforeend', data3 );
				} catch ( e ) {
					sectionContent.insertAdjacentHTML(
						'beforeend',
						`<div class="lp-ajax-message error" style="display:block">${
							e.message ||
							'Error: Query lp/v1/lazy-load/course-curriculum'
						}</div>`
					);
				}

				sectionBtn.classList.remove( 'loading' );

				searchCourseContent();
			}
		} );

		// Load more Sections
		const moreSections = document.querySelectorAll(
			'.curriculum-more__button'
		);

		[ ...moreSections ].map( async ( moreSection ) => {
			if ( moreSection.contains( e.target ) && ! isLoadingSections ) {
				isLoadingSections = true;
				const paged = parseInt( moreSection.dataset.page );

				const sections =
					moreSection.parentNode.parentNode.querySelector(
						'.curriculum-sections'
					);

				if ( paged && sections ) {
					moreSection.classList.add( 'loading' );

					try {
						const response2 = await getResponsive(
							'',
							paged + 1,
							''
						);

						const { data2, pages2, status2, message2 } = response2;

						if ( status2 === 'error' ) {
							throw new Error( message2 || 'Error' );
						}

						if ( pages2 <= paged + 1 ) {
							moreSection.remove();
						} else {
							moreSection.dataset.page = paged + 1;
						}

						sections.insertAdjacentHTML( 'beforeend', data2 );
					} catch ( e ) {
						sections.insertAdjacentHTML(
							'beforeend',
							`<div class="lp-ajax-message error" style="display:block">${
								e.message ||
								'Error: Query lp/v1/lazy-load/course-curriculum'
							}</div>`
						);
					}

					moreSection.classList.remove( 'loading' );

					searchCourseContent();
				}
			}
		} );

		// Show/Hide accordion
		if ( document.querySelector( '.learnpress-course-curriculum' ) ) {
			const sections = document.querySelectorAll( '.section' );

			[ ...sections ].map( ( section ) => {
				if ( section.contains( e.target ) ) {
					const toggle = section.querySelector( '.section-left' );

					toggle.contains( e.target ) &&
						section.classList.toggle( 'closed' );
				}
			} );
		}
	} );
}
