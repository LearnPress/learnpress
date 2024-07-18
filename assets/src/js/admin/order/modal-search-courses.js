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

const search = _.debounce( function() {
	document.querySelector( '#modal-search-items' ).classList.add( 'loading' );
	const restUrl = lpGlobalSettings.rest || '';

	const query = {
		type: data.postType, //lp_course
		context: data.context, //order items
		context_id: data.contextId, //order id
		term, // input search
		paged,
		'lp-ajax': 'modal_search_items',
		exclude: data.exclude,
		nonce: lpGlobalSettings.nonce,
	};

	const params = new URLSearchParams( Object.entries( query ) );
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
	} ).then( ( response ) => {
		const jsonString = response.replace( /<-- LP_AJAX_START -->|<-- LP_AJAX_END -->/g, '' ).trim();
		try {
			const result = LP.parseJSON( jsonString );
			hasItems = !! _.size( result.items );

			const modal_search_items = document.querySelector( '#modal-search-items' );
			if ( hasItems ) {
				modal_search_items.querySelector( '.search-nav' ).style.display = 'block';
			}

			modal_search_items.classList.remove( 'loading' );

			modal_search_items.querySelector( '.search-results' ).innerHTML = result.html;
			const checkBoxNodes = modal_search_items.querySelectorAll( '.search-results input[type="checkbox"]' );

			[ ...checkBoxNodes ].map( ( checkBoxNode ) => {
				const id = parseInt( checkBoxNode.value );

				if ( _.indexOf( selectedItems, id ) >= 0 ) {
					checkBoxNode.checked = true;
				}
			} );

			_.debounce( function() {
				const searchNav = modal_search_items.querySelector( '.search-nav' );
				searchNav.innerHTML = result.nav;

				const buttons = searchNav.querySelectorAll( 'a,span' );
				[ ...buttons ].map( ( button ) => {
					button.classList.add( 'button' );

					if ( button.tagName === 'SPAN' ) {
						button.classList.add( 'disabled' );
					}
				} );
			}, 10 )();
		} catch ( e ) {
			console.error( 'Error parsing JSON:', e );
		}
	} )
		.catch( ( error ) => {
			console.error( 'Error:', error );
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

		const buttons = modalSearchItems.querySelectorAll( '.search-nav *' );

		buttons.forEach( ( button ) => {
			button.classList.add( 'disabled' );
		} );

		if ( target.classList.contains( 'next' ) ) {
			paged++;
		} else if ( target.classList.contains( 'prev' ) ) {
			paged--;
		} else {
			paged = parseInt( target.innerHTML );
		}
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
				console.log( response );
				const jsonString = response.replace( /<-- LP_AJAX_START -->|<-- LP_AJAX_END -->/g, '' ).trim();
				try {
					const result = LP.parseJSON( jsonString );

					const noItem = listItems.querySelector( '.no-order-items' );
					noItem.style.display = 'none';
					const itemHtml = result.item_html;
					noItem.insertAdjacentHTML( 'beforebegin', itemHtml );
					const lpOrder = document.querySelector( '#learn-press-order' );
					lpOrder.querySelector( '.order-subtotal' ).innerHTML = result.order_data.subtotal_html;
					lpOrder.querySelector( '.order-total' ).innerHTML = result.order_data.total_html;

					removeModal();
				} catch ( e ) {
					console.error( 'Error parsing JSON:', e );
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
