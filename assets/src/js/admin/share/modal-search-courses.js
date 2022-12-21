const _ = window._;
window.$Vue = window.$Vue || window.Vue;

jQuery( document ).ready( function( $ ) {
	const $VueHTTP = $Vue ? $Vue.http : false;

	document.getElementById( 'vue-modal-search-items' ) && $Vue && ( function() {
		$Vue.component( 'learn-press-modal-search-items', {
			template: '#learn-press-modal-search-items',
			data() {
				return {
					paged: 1,
					term: '',
					hasItems: false,
					selected: [],
				};
			},
			watch: {
				show( value ) {
					if ( value ) {
						$( this.$refs.search ).focus();
					}
				},
			},
			props: [ 'postType', 'context', 'contextId', 'show', 'callbacks', 'exclude' ],
			created() {
			},
			mounted() {
				this.term = '';
				this.paged = 1;
				this.search();
			},
			methods: {
				doSearch( e ) {
					this.term = e.target.value;
					this.paged = 1;
					this.search();
				},
				search: _.debounce( function( term ) {
					$( '#modal-search-items' ).addClass( 'loading' );
					const that = this;
					$VueHTTP.post(
						window.location.href, {
							type: this.postType,
							context: this.context,
							context_id: this.contextId,
							term: term || this.term,
							paged: this.paged,
							exclude: this.exclude,
							'lp-ajax': 'modal_search_items',
						}, {
							emulateJSON: true,
							params: {},
						}
					).then( function( response ) {
						const result = LP.parseJSON( response.body || response.bodyText );
						that.hasItems = !! _.size( result.items );

						$( '#modal-search-items' ).removeClass( 'loading' );

						$( that.$el ).find( '.search-results' ).html( result.html ).find( 'input[type="checkbox"]' ).each( function() {
							const id = parseInt( $( this ).val() );
							if ( _.indexOf( that.selected, id ) >= 0 ) {
								this.checked = true;
							}
						} );
						_.debounce( function() {
							$( that.$el ).find( '.search-nav' ).html( result.nav ).find( 'a, span' ).addClass( 'button' ).filter( 'span' ).addClass( 'disabled' );
						}, 10 )();
					} );
				}, 500 ),
				loadPage( e ) {
					e.preventDefault();
					const $button = $( e.target );
					if ( $button.is( 'span' ) ) {
						return;
					}
					if ( $button.hasClass( 'next' ) ) {
						this.paged++;
					} else if ( $button.hasClass( 'prev' ) ) {
						this.paged--;
					} else {
						const paged = $button.html();
						this.paged = parseInt( paged );
					}
					this.search();
				},
				selectItem( e ) {
					const $select = $( e.target ).closest( 'li' ),
						$chk = $select.find( 'input[type="checkbox"]' ),
						id = parseInt( $chk.val() ),
						pos = _.indexOf( this.selected, id );

					if ( $chk.is( ':checked' ) ) {
						if ( pos === -1 ) {
							this.selected.push( id );
						}
					} else if ( pos >= 0 ) {
						this.selected.splice( pos, 1 );
					}
				},
				addItems() {
					const close = true;
					if ( this.callbacks && this.callbacks.addItems ) {
						this.callbacks.addItems.call( this );
					}
					$( document ).triggerHandler( 'learn-press/add-order-items', this.selected );
				},
				close() {
					this.$emit( 'close' );
				},
			},
		} );

		window.LP.$modalSearchItems = new $Vue( {
			el: '#vue-modal-search-items',
			data: {
				show: false,
				term: '',
				postType: '',
				callbacks: {},
				exclude: '',
				context: '',
			},
			methods: {
				open( options ) {
					_.each( options.data, function( v, k ) {
						this[ k ] = v;
					}, this );

					this.callbacks = options.callbacks;
					this.focusSearch();
				},
				close() {
					this.show = false;
				},
				focusSearch: _.debounce( function() {
					$( 'input[name="search"]', this.$el ).focus();
				}, 200 ),
			},
		} );
	}() );
} );
