( function( $ ) {
	'use strict';

	jQuery( document ).ready( function( $ ) {
		let data = {},
			paged = 1,
			term = '',
			hasItems = false,
			selectedItems = [];

		const $listItems = $( '#learn-press-order .list-order-items' ).find( 'tbody' );

		const getAddedItems = () => {
			return $( '#learn-press-order .list-order-items tbody' ).children( '.order-item-row' ).map( function() {
				return $( this ).data( 'id' );
			} ).get();
		};

		const focusSearch = _.debounce( function() {
			$( '#modal-search-items input[name="search"]' ).focus();
		}, 200 );

		const mountSearchModal = () => {
			const $modalSearchItems = $( '#learn-press-modal-search-items' );
			const $modalContainer = $( '#container-modal-search-items' );
			$modalContainer.html( $modalSearchItems.html() );
		};

		const search = _.debounce( function() {
			$( '#modal-search-items' ).addClass( 'loading' );

			$.post(
				window.location.href, {
					type: data.postType,
					context: data.context,
					context_id: data.contextId,
					term,
					paged,
					exclude: data.exclude,
					'lp-ajax': 'modal_search_items',
					dataType: 'text',
					nonce: lpGlobalSettings.nonce,
				}, ( response ) => {
					const jsonString = response.replace( /<-- LP_AJAX_START -->|<-- LP_AJAX_END -->/g, '' ).trim();
					try {
						const result = LP.parseJSON( jsonString );
						hasItems = !! _.size( result.items );

						const $modal_search_items = $( '#modal-search-items' );
						if ( hasItems ) {
							$modal_search_items.find( '.search-nav' ).css( 'display', 'block' );
						}

						$modal_search_items.removeClass( 'loading' );

						$modal_search_items.find( '.search-results' ).html( result.html ).find( 'input[type="checkbox"]' ).each( function() {
							const id = parseInt( $( this ).val() );
							if ( _.indexOf( selectedItems, id ) >= 0 ) { // checked checkbox khi search lại kết quả cũ
								this.checked = true;
							}
						} );

						_.debounce( function() {
							$modal_search_items.find( '.search-nav' ).html( result.nav ).find( 'a, span' ).addClass( 'button' ).filter( 'span' ).addClass( 'disabled' );
						}, 10 )();
					} catch ( e ) {
						console.error( 'Error parsing JSON:', e );
					}
				}
			).fail( function( jqXHR, textStatus, errorThrown ) {
				console.error( 'Request failed: ' + textStatus + ', ' + errorThrown );
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

				$.post(
					window.location.href, {
						order_id: data.contextId,
						items: selectedItems,
						'lp-ajax': 'add_items_to_order',
						nonce: lpGlobalSettings.nonce,
						dataType: 'text',
					},
					function( response ) {
						const jsonString = response.replace( /<-- LP_AJAX_START -->|<-- LP_AJAX_END -->/g, '' ).trim();
						try {
							const result = LP.parseJSON( jsonString );

							// Tìm phần tử không có item và ẩn nó
							const $noItem = $listItems.find( '.no-order-items' ).hide();
							// Chèn HTML mới vào trước phần tử không có item
							$( result.item_html ).insertBefore( $noItem );
							// Cập nhật subtotal và total
							$( '.order-subtotal' ).html( result.order_data.subtotal_html );
							$( '.order-total' ).html( result.order_data.total_html );
							removeModal();
						} catch ( e ) {
							console.error( 'Error parsing JSON:', e );
						}
					}
				).fail( function( jqXHR, textStatus, errorThrown ) {
					console.error( 'Request failed:', textStatus, errorThrown );
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

		//Khi click vào add Items -> Chuyển từ meta-box-order sang
		$( '#learn-press-add-order-item' ).on( 'click', function() {
			data = {
				postType: 'lp_course',
				context: 'order-items',
				contextId: $( '#post_ID' ).val(),
				exclude: getAddedItems(),
				show: true,
			};

			term = '';
			paged = 1;

			mountSearchModal();
			focusSearch();
			search();
			doSearch();
			loadPage();
			selectItems();
			addItems();
			closeModal();
		} );
	} );
}( jQuery ) );

