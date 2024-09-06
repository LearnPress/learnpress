import { AdminUtilsFunctions, Api, Utils } from '../utils-admin.js';

const addCoursesToOrder = () => {
	let elModalSearchCourses;
	let elBtnAddOrderItem, elSearchCoursesResult;
	let elOrderDetails, modalSearchItemsTemplate, modalContainer;
	let elOrderModalFooter, elOrderModalBtnAdd;
	let elListOrderItems;
	let timeOutSearch;
	const idModalSearchItems = '#modal-search-items';
	const idOrderDetails = '#learn-press-order';
	let dataSend = {
		search: '',
		id_not_in: '',
		paged: 1,
	};
	const courseIdsNewSelected = [];
	const courseIdsAdded = [];

	const getAllElements = () => {
		elOrderDetails = document.querySelector( '#learn-press-order' );
		elListOrderItems = elOrderDetails.querySelector( '.list-order-items' );
		elBtnAddOrderItem = elOrderDetails.querySelector( '#learn-press-add-order-item' );
		modalSearchItemsTemplate = document.querySelector( '#learn-press-modal-search-items' );
		modalContainer = document.querySelector( '#container-modal-search-items' );
	};

	/**
	 * Fetch courses from API.
	 *
	 * @param keySearch
	 * @param course_ids_exclude
	 * @param paged
	 */
	const fetchCoursesAPI = ( keySearch = '', course_ids_exclude = [], paged = 1 ) => {
		let id_not_in = '';
		if ( course_ids_exclude.length > 0 ) {
			id_not_in = course_ids_exclude.join( ',' );
		}

		dataSend = {
			search: keySearch,
			id_not_in,
			paged,
		};

		AdminUtilsFunctions.fetchCourses( keySearch, dataSend, {
			before() {
				elModalSearchCourses.classList.add( 'loading' );
			},
			success( response ) {
				const { data, status, message } = response;
				const { courses, total_pages } = data;

				if ( 'success' !== status ) {
					console.error( message );
				} else {
					if ( ! courses.length ) {
						elSearchCoursesResult.innerHTML = '<li class="lp-result-item">No courses found</li>';
						return;
					}

					elSearchCoursesResult.innerHTML = renderSearchResult( courses );
					const paginationHtml = renderPagination( paged, total_pages );
					const searchNav = elModalSearchCourses.querySelector( '.search-nav' );
					searchNav.innerHTML = paginationHtml;
				}
			},
			error( err ) {
				console.error( err );
			},
			completed() {
				elModalSearchCourses.classList.remove( 'loading' );
			},
		} );
	};

	/**
	 * Get list course ids added.
	 */
	const getCoursesAdded = () => {
		const orderItems = document.querySelectorAll( '#learn-press-order .list-order-items tbody .order-item-row' );
		orderItems.forEach( ( orderItem ) => {
			const orderItemId = parseInt( orderItem.getAttribute( 'data-id' ) );
			courseIdsAdded.push( orderItemId );
		} );
	};

	/**
	 * Add courses to order.
	 * @param e
	 * @param target
	 */
	const addCourses = ( e, target ) => {
		if ( ! target.classList.contains( 'add' ) ) {
			return;
		}

		if ( ! target.closest( idModalSearchItems ) ) {
			return;
		}

		e.preventDefault();

		target.disabled = true;
		const dataSend = {
			'lp-ajax': 'add_items_to_order',
			order_id: document.querySelector( '#post_ID' ).value,
			items: courseIdsNewSelected,
			nonce: lpDataAdmin.nonce,
		};

		const callBack = {
			success( response ) {
				const { data, messages, status } = response;
				if ( 'error' === status ) {
					console.error( messages );
					return;
				}

				const { item_html, order_data } = data;
				const elNoItem = elListOrderItems.querySelector( '.no-order-items' );
				elNoItem.style.display = 'none';
				elNoItem.insertAdjacentHTML( 'beforebegin', item_html );
				elOrderDetails.querySelector( '.order-subtotal' ).innerHTML = order_data.subtotal_html;
				elOrderDetails.querySelector( '.order-total' ).innerHTML = order_data.total_html;
				courseIdsAdded.push( ...courseIdsNewSelected );
				courseIdsNewSelected.splice( 0, courseIdsNewSelected.length );
			},
			error( err ) {
				console.error( err );
			},
			completed() {
				target.disabled = false;
				modalContainer.style.display = 'none';
			},
		};

		Utils.lpFetchAPI( Utils.lpAddQueryArgs( Utils.lpGetCurrentURLNoParam(), dataSend ), {}, callBack );
	};

	/**
	 * Remove course from order.
	 *
	 * @param e
	 * @param target
	 */
	const removeCourse = ( e, target ) => {
		if ( target.tagName !== 'SPAN' ) {
			return;
		}

		if ( ! target.closest( idOrderDetails ) ) {
			return;
		}

		e.preventDefault();

		if ( ! confirm( 'Are you sure you want to remove this item?' ) ) {
			return;
		}

		target.disabled = true;
		target.classList.add( 'dashicons-update' );
		const elItemRow = target.closest( '.order-item-row' );
		const elListOrderItems = target.closest( '.list-order-items' );
		const orderItemId = parseInt( elItemRow.getAttribute( 'data-item_id' ) );
		const courseId = parseInt( elItemRow.getAttribute( 'data-id' ) );

		const dataSend = {
			'lp-ajax': 'remove_items_from_order',
			order_id: document.querySelector( '#post_ID' ).value,
			items: orderItemId,
			nonce: lpDataAdmin.nonce,
		};

		const callBack = {
			success( response ) {
				const { data, messages, status } = response;
				if ( 'error' === status ) {
					console.error( messages );
					return;
				}

				const { item_html, order_data } = data;
				const elNoItem = elListOrderItems.querySelector( '.no-order-items' );
				const orderItems = elListOrderItems.querySelectorAll( '.order-item-row' );
				orderItems.forEach( ( orderItem ) => {
					orderItem.remove();
				} );
				if ( item_html.length ) {
					elNoItem.insertAdjacentHTML( 'beforebegin', item_html );
				} else {
					elNoItem.style.display = 'block';
				}

				courseIdsNewSelected.splice( courseIdsNewSelected.indexOf( courseId ), 1 );
				courseIdsAdded.splice( courseIdsNewSelected.indexOf( courseId ), 1 );
				elOrderDetails.querySelector( '.order-subtotal' ).innerHTML = order_data.subtotal_html;
				elOrderDetails.querySelector( '.order-total' ).innerHTML = order_data.total_html;
			},
			error( err ) {
				console.error( err );
			},
			completed() {

			},
		};
		Utils.lpFetchAPI( Utils.lpAddQueryArgs( Utils.lpGetCurrentURLNoParam(), dataSend ), {}, callBack );
	};

	/**
	 * Search courses before add Order.
	 *
	 * @param e
	 * @param target
	 */
	const searchCourse = ( e, target ) => {
		if ( 'search' !== target.name ) {
			return;
		}

		const elLPTarget = target.closest( idModalSearchItems );
		if ( ! elLPTarget ) {
			return;
		}

		e.preventDefault();
		const keyword = target.value;

		if ( ! keyword || ( keyword && keyword.length > 2 ) ) {
			if ( undefined !== timeOutSearch ) {
				clearTimeout( timeOutSearch );
			}

			timeOutSearch = setTimeout( function() {
				fetchCoursesAPI( keyword, courseIdsAdded, 1 );
			}, 800 );
		}
	};

	/**
	 * Display list courses when search done.
	 *
	 * @param courses
	 */
	const renderSearchResult = ( courses ) => {
		let html = '';

		courses.forEach( ( course ) => {
			const courseId = parseInt( course.ID );
			const checked = courseIdsNewSelected.includes( courseId ) ? 'checked' : '';

			html += `
			<li class="lp-result-item" data-id="${ courseId }" data-type="lp_course" data-text="${ course.post_title }">
				<label>
					<input type="checkbox" value="${ courseId }" name="selectedItems[]" ${ checked }>
					<span class="lp-item-text">${ course.post_title } (#${ courseId })</span>
				</label>
			</li>`;
		} );

		return html;
	};

	/**
	 * Render pagination.
	 *
	 * @param currentPage
	 * @param maxPage
	 */
	const renderPagination = ( currentPage, maxPage ) => {
		currentPage = parseInt( currentPage );
		maxPage = parseInt( maxPage );

		let html = '';
		if ( maxPage <= 1 ) {
			return html;
		}
		const nextPage = currentPage + 1;
		const prevPage = currentPage - 1;

		let pages = [];

		if ( maxPage <= 9 ) {
			for ( let i = 1; i <= maxPage; i++ ) {
				pages.push( i );
			}
		} else if ( currentPage <= 3 ) {
			// x is ...
			pages = [ 1, 2, 3, 4, 5, 'x', maxPage ];
		} else if ( currentPage <= 5 ) {
			for ( let i = 1; i <= currentPage; i++ ) {
				pages.push( i );
			}
			for ( let j = 1; j <= 2; j++ ) {
				const tempPage = currentPage + j;
				pages.push( tempPage );
			}
			pages.push( 'x' );
			pages.push( maxPage );
		} else {
			pages = [ 1, 'x' ];

			for ( let k = 2; k >= 0; k-- ) {
				const tempPage = currentPage - k;
				pages.push( tempPage );
			}

			const currentToLast = maxPage - currentPage;

			if ( currentToLast <= 5 ) {
				for ( let m = currentPage + 1; m <= maxPage; m++
				) {
					pages.push( m );
				}
			} else {
				for ( let n = 1; n <= 2; n++ ) {
					const tempPage = currentPage + n;
					pages.push( tempPage );
				}
				pages.push( 'x' );
				pages.push( maxPage );
			}
		}

		const maximum = pages.length;

		if ( currentPage !== 1 ) {
			html += `<a class="prev page-numbers button" href="#" data-page="${ prevPage }"><</a>`;
		}
		for ( let i = 0; i < maximum; i++ ) {
			if ( currentPage === parseInt( pages[ i ] ) ) {
				html += `<a aria-current="page" class="page-numbers current button disabled" data-page="${ pages[ i ] }">
				${ pages[ i ] }
			</a>`;
			} else if ( pages[ i ] === 'x' ) {
				html += `<span class="page-numbers dots button disabled">...</span>`;
			} else {
				html += `<a class="page-numbers button" href="#" data-page="${ pages[ i ] }">${ pages[ i ] } </a>`;
			}
		}

		if ( currentPage !== maxPage ) {
			html += `<a class="next page-numbers button" href="#" data-page="${ nextPage }">></a>`;
		}

		return html;
	};

	const showPopupSearchCourses = () => {
		modalContainer.style.display = 'block';
		elOrderModalBtnAdd.style.display = 'none';
		elSearchCoursesResult.innerHTML = '';
		fetchCoursesAPI( dataSend.search, courseIdsAdded, dataSend.paged );
	};

	// Events.
	document.addEventListener( 'click', ( e ) => {
		const target = e.target;
		//console.dir( target );
		if ( elBtnAddOrderItem && target.id === elBtnAddOrderItem.id ) {
			e.preventDefault();
			showPopupSearchCourses();
		}

		if ( target.classList.contains( 'close' ) && target.closest( idModalSearchItems ) ) {
			e.preventDefault();
			elModalSearchCourses.querySelector( 'input[name="search"]' ).value = '';
			dataSend.search = '';
			dataSend.paged = 1;
			modalContainer.style.display = 'none';
		}

		if ( target.classList.contains( 'page-numbers' ) ) {
			if ( target.closest( idModalSearchItems ) ) {
				e.preventDefault();
				const paged = target.getAttribute( 'data-page' );
				fetchCoursesAPI( dataSend.search, dataSend.id_not_in, paged );
			}
		}

		if ( target.name === 'selectedItems[]' ) {
			if ( target.closest( idModalSearchItems ) ) {
				const courseId = parseInt( target.value );
				if ( target.checked ) {
					courseIdsNewSelected.push( courseId );
				} else {
					const index = courseIdsNewSelected.indexOf( courseId );
					if ( index > -1 ) {
						courseIdsNewSelected.splice( index, 1 );
					}
				}

				elOrderModalBtnAdd.style.display = courseIdsNewSelected.length > 0 ? 'block' : 'none';
			}
		}

		addCourses( e, target );
		removeCourse( e, target );
	} );
	document.addEventListener( 'keyup', function( e ) {
		const target = e.target;

		searchCourse( e, target );
	} );

	// DOMContentLoaded.
	document.addEventListener( 'DOMContentLoaded', () => {
		getAllElements();

		if ( ! elOrderDetails || ! elBtnAddOrderItem ) {
			return;
		}

		getCoursesAdded();
		modalContainer.innerHTML = modalSearchItemsTemplate.innerHTML;
		elModalSearchCourses = modalContainer.querySelector( idModalSearchItems );
		elSearchCoursesResult = elModalSearchCourses.querySelector( '.search-results' );
		elOrderModalFooter = elModalSearchCourses.querySelector( 'footer' );
		elOrderModalBtnAdd = elOrderModalFooter.querySelector( '.add' );
		modalContainer.style.display = 'none';
	} );
};

export default addCoursesToOrder;
