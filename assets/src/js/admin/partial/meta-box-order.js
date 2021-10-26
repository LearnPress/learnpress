( function( $ ) {
	'use strict';
	window.$Vue = window.$Vue || Vue;

	jQuery( function() {
		// eslint-disable-next-line no-var
		var $listItems = $( '.list-order-items' ).find( 'tbody' ),
			$listUsers = $( '#list-users' ),
			template = function( templateHTML, data ) {
				return _.template( templateHTML, {
					evaluate: /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape: /\{\{([^\}]+?)\}\}(?!\})/g,
				} )( data );
			},
			advancedListOptions = {
				template: '#tmpl-order-advanced-list-item',
				onRemove() {
					if ( this.$el.children().length === 0 ) {
						this.$el.append( '<li class="user-guest">' + orderOptions.i18n_guest + '</li>' );
					}
					console.log( this.$el );
				},
				onAdd() {
					this.$el.find( '.user-guest' ).remove();
				},
			},
			orderOptions = lpMetaBoxOrderSettings;

		function getAddedUsers() {
			return $( '#list-users' ).children().map( function() {
				return $( this ).data( 'id' );
			} ).get();
		}

		function getAddedItems() {
			return $( '.list-order-items tbody' ).children( '.order-item-row' ).map( function() {
				return $( this ).data( 'id' );
			} ).get();
		}

		if ( $listUsers.length ) {
			$listUsers.LP( 'AdvancedList', advancedListOptions );
			if ( orderOptions.users ) {
				_.forEach( orderOptions.users, function( userData, userId ) {
					$listUsers.LP( 'AdvancedList', 'add', [
						template( orderOptions.userTextFormat, userData ),
						userId,
					] );
				} );
			}
		}

		$listItems.on( 'click', '.remove-order-item', function( e ) {
			e.preventDefault();
			const $item = $( this ).closest( 'tr' ),
				item_id = $item.data( 'item_id' );

			$item.remove();
			if ( $listItems.children( ':not(.no-order-items)' ).length === 0 ) {
				$listItems.find( '.no-order-items' ).show();
			}

			$Vue.http.post(
				window.location.href, {
					order_id: $( '#post_ID' ).val(),
					items: [ item_id ],
					'lp-ajax': 'remove_items_from_order',
					remove_nonce: $( this ).closest( '.order-item-row' ).data( 'remove_nonce' ),
				}, {
					emulateJSON: true,
					params: {},
				}
			).then( function( response ) {
				const result = LP.parseJSON( response.body || response.bodyText );
				$( '.order-subtotal' ).html( result.order_data.subtotal_html );
				$( '.order-total' ).html( result.order_data.total_html );
			} );
		} );

		$( '.order-date.date-picker-backendorder' ).on( 'change', function() {
			const m = this.value.split( '-' );
			[ 'aa', 'mm', 'jj' ].forEach( function( v, k ) {
				$( 'input[name="' + v + '"]' ).val( m[ k ] );
			} );
		} ).datepicker( {
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true,
			select() {
				console.log( arguments );
			},
		} );

		$( '#learn-press-add-order-item' ).on( 'click', function() {
			LP.$modalSearchItems.open( {
				data: {
					postType: 'lp_course',
					context: 'order-items',
					contextId: $( '#post_ID' ).val(),
					exclude: getAddedItems(),
					show: true,
				},
				callbacks: {
					addItems() {
						const that = this;
						$Vue.http.post(
							window.location.href, {
								order_id: this.contextId,
								items: this.selected,
								'lp-ajax': 'add_items_to_order',
							}, {
								emulateJSON: true,
								params: {},
							}
						).then( function( response ) {
							const result = LP.parseJSON( response.body || response.bodyText ),
								$noItem = $listItems.find( '.no-order-items' ).hide();
							$( result.item_html ).insertBefore( $noItem );
							$( '.order-subtotal' ).html( result.order_data.subtotal_html );
							$( '.order-total' ).html( result.order_data.total_html );
						} );
						this.close();
					},
				},
			} );
		} );

		$( document ).on( 'click', '.change-user', function( e ) {
			e.preventDefault();
			LP.$modalSearchUsers.open( {
				data: {
					context: 'order-items',
					contextId: $( '#post_ID' ).val(),
					show: true,
					multiple: $( this ).data( 'multiple' ) === 'yes',
					exclude: getAddedUsers(),
					textFormat: orderOptions.userTextFormat,
				},
				callbacks: {
					addUsers( data ) {
						if ( this.multiple ) {
							if ( ! $listUsers.length ) {
								$listUsers = $( LP.template( 'tmpl-order-data-user' )( { multiple: true } ) );
								$listUsers.LP( 'AdvancedList', advancedListOptions );

								$( '.order-data-user' ).replaceWith( $listUsers );
							}
							for ( let i = 0; i < this.selected.length; i++ ) {
								$listUsers.LP( 'AdvancedList', 'add', [ template( this.textFormat, this.selected[ i ] ), this.selected[ i ].id ] );
							}
						} else {
							const $html = LP.template( 'tmpl-order-data-user' )( {
								name: template( this.textFormat, this.selected[ 0 ] ),
								id: this.selected[ 0 ].id,
							} );

							$( '.order-data-user' ).replaceWith( $html );
						}

						this.close();
					},
				},
			} );
		} );
	} );
}( jQuery ) );
