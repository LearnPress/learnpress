import { lpAjaxParseJsonOld } from '../../utils';
import { addQueryArgs } from '@wordpress/url';

let data = {},
	paged = 1,
	term = '',
	hasItems = false,
	selectedItems = [];

const lpOrderNode = document.querySelector( '#learn-press-order' );
let listItems, displayModalBtn;
const modalSearchCourses = () => {
	if ( ! lpOrderNode ) {
		return;
	}

	listItems = lpOrderNode.querySelector( '.list-order-items tbody' );
	displayModalBtn = lpOrderNode.querySelector( '#learn-press-add-order-item' );

	displayModal();
	doSearch();
	loadPage();
	selectItems();
	addItems();
	closeModal();
	removeItem();
};

const removeItem = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( ! target.classList.contains( 'remove-order-item' ) && ! target.closest( '.remove-order-item' ) ) {
			return;
		}

		const lpOrderNode = target.closest( '#learn-press-order' );

		if ( ! lpOrderNode ) {
			return;
		}

		event.preventDefault();

		const item = target.closest( 'tr' );
		const itemId = item.getAttribute( 'data-item_id' );

		item.remove();

		const orderItems = lpOrderNode.querySelectorAll( '.list-order-items tbody tr:not(.no-order-items)' );
		const noOrderItems = lpOrderNode.querySelector( '.list-order-items tbody .no-order-items' );
		if ( orderItems.length === 0 ) {
			noOrderItems.style.display = 'block';
		}

		const query = {
			order_id: document.querySelector( '#post_ID' ).value,
			items: [ itemId ],
			'lp-ajax': 'remove_items_from_order',
			remove_nonce: target.closest( '.order-item-row' ).dataset.remove_nonce,
		};

		const params = new URLSearchParams();

		for ( const [ key, value ] of Object.entries( query ) ) {
			if ( Array.isArray( value ) ) {
				value.forEach( ( item ) => params.append( `${ key }[]`, item ) );
			} else {
				params.append( key, value );
			}
		}

		fetch( window.location.href, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: params,
		} )
			.then( ( response ) => response.text() )
			.then( ( response ) => {
				const data = lpAjaxParseJsonOld( response );
				lpOrderNode.querySelector( '.order-subtotal' ).innerHTML = data.order_data.subtotal_html;
				lpOrderNode.querySelector( '.order-total' ).innerHTML = data.order_data.total_html;
				// lpOrderNode.querySelector( '#item-container' ).innerHTML = data.item_html;
			} )
			.catch( ( error ) => {
				console.error( 'Error:', error );
			} );
	} );
};

const getAddedItems = () => {
	const orderItems = document.querySelectorAll( '#learn-press-order .list-order-items tbody .order-item-row' );
	return [ ...orderItems ].map( ( orderItem ) => {
		return parseInt( orderItem.getAttribute( 'data-id' ) );
	} );
};

const focusSearch = _.debounce( function() {
	document.querySelector( '#modal-search-items input[name="search"]' ).focus();
}, 200 );

const mountSearchModal = () => {
	const modalSearchItems = document.querySelector( '#learn-press-modal-search-items' );
	const modalContainer = document.querySelector( '#container-modal-search-items' );

	modalContainer.innerHTML = modalSearchItems.innerHTML;
};

const renderSearchResult = ( courses ) => {
	let html = '';

	for ( let i = 0; i < courses.length; i++ ) {
		html += `
		<li class="lp-result-item" data-id="${ courses[ i ].ID }" data-type="lp_course" data-text="${ courses[ i ].post_title }">
			<label>
				<input type="checkbox" value="${ courses[ i ].ID }" name="selectedItems[]">
				<span class="lp-item-text">${ courses[ i ].post_title } (Course - #${ courses[ i ].ID })</span>
			</label>
		</li>`;
	}

	return html;
};

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

const search = _.debounce( function() {
	document.querySelector( '#modal-search-items' ).classList.add( 'loading' );

	const query = {
		c_search: term, // input search
		paged,
		not_ids: data.exclude,
	};

	wp.apiFetch( {
		path: addQueryArgs( '/lp/v1/admin/tools/search-course', query ),
		method: 'GET',
	} ).then( ( res ) => {
		if ( res.status && res.status === 'success' ) {
			const courses = res.data.courses;
			hasItems = _.size( courses );
			const modal_search_items = document.querySelector( '#modal-search-items' );
			if ( hasItems ) {
				const searchNav = modal_search_items.querySelector( '.search-nav' );
				searchNav.style.display = 'flex';
				searchNav.style.gap = '4px';
			}
			modal_search_items.classList.remove( 'loading' );
			modal_search_items.querySelector( '.search-results' ).innerHTML = renderSearchResult( courses );
			const checkBoxNodes = modal_search_items.querySelectorAll( '.search-results input[type="checkbox"]' );

			[ ...checkBoxNodes ].map( ( checkBoxNode ) => {
				const id = parseInt( checkBoxNode.value );

				if ( _.indexOf( selectedItems, id ) >= 0 ) {
					checkBoxNode.checked = true;
				}
			} );

			_.debounce( function() {
				const searchNav = modal_search_items.querySelector( '.search-nav' );
				searchNav.innerHTML = renderPagination( paged, res.data.total_pages );
			}, 10 )();
		}
	} ).catch( ( err ) => {
		console.log( err );
	} ).finally( () => {
	} );
}, 500 );

const doSearch = () => {
	document.addEventListener( 'input', function( event ) {
		const target = event.target;
		if ( target.name !== 'search' ) {
			return;
		}

		const modalSearchItems = target.closest( '#modal-search-items' );
		if ( ! modalSearchItems ) {
			return;
		}

		term = target.value;
		paged = 1;

		search();
	} );
};

const loadPage = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( ! target.classList.contains( 'page-numbers' ) ) {
			return;
		}

		const modalSearchItems = target.closest( '#modal-search-items' );
		if ( ! modalSearchItems ) {
			return;
		}

		event.preventDefault();

		const buttons = modalSearchItems.querySelectorAll( '.search-nav a' );

		buttons.forEach( ( button ) => {
			button.classList.add( 'disabled' );
		} );

		paged = target.getAttribute( 'data-page' );
		search();
	} );
};

const selectItems = () => {
	document.addEventListener( 'change', function( event ) {
		const target = event.target;
		if ( target.name !== 'selectedItems[]' ) {
			return;
		}

		const modalSearchItems = target.closest( '#modal-search-items' );
		if ( ! modalSearchItems ) {
			return;
		}

		const id = parseInt( target.value );
		const pos = _.indexOf( selectedItems, id );

		if ( target.checked ) {
			if ( pos === -1 ) {
				selectedItems.push( id );
			}
		} else if ( pos >= 0 ) {
			selectedItems.splice( pos, 1 );
		}

		const addBtn = document.querySelector( '#modal-search-items button.button-primary' );
		if ( addBtn ) {
			if ( selectedItems.length ) {
				addBtn.style.display = 'block';
			} else {
				addBtn.style.display = 'none';
			}
		}
	} );
};

const addItems = () => {
	document.addEventListener( 'click', function( event ) {
		const addBtn = event.target;
		if ( ! addBtn.classList.contains( 'add' ) ) {
			return;
		}

		const modalSearchItems = addBtn.closest( '#modal-search-items' );
		if ( ! modalSearchItems ) {
			return;
		}

		addBtn.disabled = true;

		const query = {
			order_id: data.contextId,
			items: selectedItems,
			'lp-ajax': 'add_items_to_order',
			nonce: lpGlobalSettings.nonce,
		};

		const params = new URLSearchParams();
		for ( const [ key, value ] of Object.entries( query ) ) {
			if ( Array.isArray( value ) ) {
				value.forEach( ( item ) => params.append( `${ key }[]`, item ) );
			} else {
				params.append( key, value );
			}
		}

		fetch( window.location.href, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: params,
		} ).then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( 'Error' );
			}
			return response.text();
		} )
			.then( ( response ) => {
				const jsonString = response.replace( /<-- LP_AJAX_START -->|<-- LP_AJAX_END -->/g, '' ).trim();
				try {
					const result = LP.parseJSON( jsonString );

					const noItem = listItems.querySelector( '.no-order-items' );
					noItem.style.display = 'none';
					const itemHtml = result.item_html;
					noItem.insertAdjacentHTML( 'beforebegin', itemHtml );

					lpOrderNode.querySelector( '.order-subtotal' ).innerHTML = result.order_data.subtotal_html;
					lpOrderNode.querySelector( '.order-total' ).innerHTML = result.order_data.total_html;

					removeModal();
				} catch ( error ) {
					console.error( 'Error parsing JSON:', error );
				}
			} )
			.catch( ( error ) => {
				console.error( 'Error:', error );
			} );
	} );
};

const closeModal = () => {
	document.addEventListener( 'click', function( event ) {
		const closeBtn = event.target;
		if ( ! closeBtn.classList.contains( 'close' ) ) {
			return;
		}

		const modalSearchItems = closeBtn.closest( '#modal-search-items' );
		if ( ! modalSearchItems ) {
			return;
		}

		removeModal();
	} );
};

const removeModal = () => {
	const modal = document.querySelector( '#modal-search-items' );
	if ( modal ) {
		selectedItems = [];
		modal.remove();
	}
};

const displayModal = () => {
	if ( displayModalBtn ) {
		displayModalBtn.addEventListener( 'click', function( event ) {
			data = {
				postType: 'lp_course',
				context: 'order-items',
				exclude: getAddedItems(),
				show: true,
			};

			const postIdNode = document.querySelector( '#post_ID' );

			if ( postIdNode ) {
				data.contextId = postIdNode.value; //order id
			}

			term = '';
			paged = 1;

			mountSearchModal();
			focusSearch();
			search();
		} );
	}
};

export default modalSearchCourses;
