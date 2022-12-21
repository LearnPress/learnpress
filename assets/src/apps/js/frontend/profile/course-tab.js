import { addQueryArgs } from '@wordpress/url';

// Rest API load content course enrolled, created - Nhamdv.
const courseTab = () => {
	const elements = document.querySelectorAll( '.learn-press-course-tab__filter__content' );

	const getResponse = ( ele, dataset, append = false, viewMoreEle = false ) => {
		wp.apiFetch( {
			path: addQueryArgs( 'lp/v1/profile/course-tab', dataset ),
			method: 'GET',
		} ).then( ( response ) => {
			const skeleton = ele.querySelector( '.lp-skeleton-animation' );
			skeleton && skeleton.remove();

			if ( response.status === 'success' && response.data ) {
				if ( append ) {
					ele.innerHTML += response.data;
				} else {
					ele.innerHTML = response.data;
				}
			} else if ( append ) {
				ele.innerHTML += `<div class="lp-ajax-message" style="display:block">${ response.message && response.message }</div>`;
			} else {
				ele.innerHTML = `<div class="lp-ajax-message" style="display:block">${ response.message && response.message }</div>`;
			}

			if ( viewMoreEle ) {
				viewMoreEle.classList.remove( 'loading' );

				const paged = viewMoreEle.dataset.paged;
				const numberPage = viewMoreEle.dataset.number;

				if ( numberPage <= paged ) {
					viewMoreEle.remove();
				}

				viewMoreEle.dataset.paged = parseInt( paged ) + 1;
			}

			viewMore( ele, dataset );
		} ).catch( ( error ) => {
			if ( append ) {
				ele.innerHTML += `<div class="lp-ajax-message error" style="display:block">${ error.message && error.message }</div>`;
			} else {
				ele.innerHTML = `<div class="lp-ajax-message error" style="display:block">${ error.message && error.message }</div>`;
			}

			if ( viewMoreEle ) {
				viewMoreEle.classList.remove( 'loading' );

				const paged = viewMoreEle.dataset.paged;
				const numberPage = viewMoreEle.dataset.number;

				if ( numberPage <= paged ) {
					viewMoreEle.remove();
				}

				viewMoreEle.dataset.paged = parseInt( paged ) + 1;
			}
		} );
	};

	if ( 'IntersectionObserver' in window ) {
		const eleObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					const ele = entry.target;

					const params = ele.parentNode.querySelector( '.lp_profile_tab_input_param' );
					const data = { ...JSON.parse( params.value ), status: ele.dataset.tab || '' };

					getResponse( ele, data );

					eleObserver.unobserve( ele );
				}
			} );
		} );

		[ ...elements ].map( ( ele ) => {
			if ( ele.dataset.tab !== 'all' ) {
				eleObserver.observe( ele );
			} else {
				const params = ele.parentNode.querySelector( '.lp_profile_tab_input_param' );
				const data = { ...JSON.parse( params.value ), status: ele.dataset.tab === 'all' ? '' : ele.dataset.tab || '' };

				getResponse( ele, data );
			}
		} );
	}

	const changeFilter = () => {
		const tabs = document.querySelectorAll( '.learn-press-course-tab-filters' );

		tabs.forEach( ( tab ) => {
			const filters = tab.querySelectorAll( '.learn-press-filters a' );

			filters.forEach( ( filter ) => {
				filter.addEventListener( 'click', ( e ) => {
					e.preventDefault();

					const tabName = filter.dataset.tab;

					[ ...filters ].map( ( ele ) => {
						ele.classList.remove( 'active' );
					} );

					filter.classList.add( 'active' );

					[ ...tab.querySelectorAll( '.learn-press-course-tab__filter__content' ) ].map( ( ele ) => {
						ele.style.display = 'none';

						if ( ele.dataset.tab === tabName ) {
							ele.style.display = '';
						}
					} );
				} );
			} );
		} );
	};

	changeFilter();

	const changeTab = () => {
		const tabUls = document.querySelectorAll( '.learn-press-profile-course__tab__inner' );

		tabUls.forEach( ( tabUl ) => {
			const tabs = tabUl.querySelectorAll( 'li> a' );

			tabs.forEach( ( tab ) => {
				tab.addEventListener( 'click', ( e ) => {
					e.preventDefault();

					const tabName = tab.dataset.tab;

					[ ...tabs ].map( ( ele ) => {
						ele.classList.remove( 'active' );
					} );

					tab.classList.add( 'active' );

					[ ...document.querySelectorAll( '.learn-press-course-tab-filters' ) ].map( ( ele ) => {
						ele.style.display = 'none';

						if ( ele.dataset.tab === tabName ) {
							ele.style.display = '';
						}
					} );
				} );
			} );
		} );
	};
	changeTab();

	const viewMore = ( ele, dataset ) => {
		const viewMoreEle = ele.querySelector( 'button[data-paged]' );

		if ( viewMoreEle ) {
			viewMoreEle.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				const paged = viewMoreEle && viewMoreEle.dataset.paged;

				viewMoreEle.classList.add( 'loading' );

				const element = dataset.layout === 'list' ? '.lp_profile_course_progress' : '.learn-press-courses';

				getResponse( ele.querySelector( element ), { ...dataset, ...{ paged } }, true, viewMoreEle );
			} );
		}
	};
};
export default courseTab;
