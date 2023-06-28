( function( $ ) {
	$( function() {
		const _ = window._;
		window.$Vue = window.$Vue || window.Vue;
		const $VueHTTP = $Vue ? $Vue.http : false;
		document.getElementById( 'vue-modal-search-users' ) && $Vue && ( function() {
			$Vue.component( 'learn-press-modal-search-users', {
				template: '#learn-press-modal-search-users',
				data: function data() {
					return {
						paged: 1,
						term: '',
						hasUsers: false,
						selected: [],
					};
				},
				watch: {
					show: function show( value ) {
						if ( value ) {
							$( this.$refs.search ).trigger( 'focus' );
						}
					},
				},
				props: [ 'multiple', 'context', 'contextId', 'show', 'callbacks', 'textFormat', 'exclude' ],
				created: function created() {},
				methods: {
					doSearch: function doSearch( e ) {
						this.term = e.target.value;
						this.paged = 1;
						this.search();
					},
					search: _.debounce( function( term ) {
						const that = this;
						$VueHTTP.post( window.location.href, {
							type: this.postType,
							context: this.context,
							context_id: this.contextId,
							term: term || this.term,
							paged: this.paged,
							multiple: this.multiple ? 'yes' : 'no',
							text_format: this.textFormat,
							exclude: this.exclude,
							'lp-ajax': 'modal_search_users',
							nonce: lpGlobalSettings.nonce,
						}, {
							emulateJSON: true,
							params: {},
						} ).then( function( response ) {
							const result = LP.parseJSON( response.body || response.bodyText );
							that.hasUsers = !! _.size( result.users );
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
					loadPage: function loadPage( e ) {
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
					selectItem: function selectItem( e ) {
						const $select = $( e.target ).closest( 'li' ),
							$chk = $select.find( 'input[type="checkbox"]' ),
							id = parseInt( $chk.val() ),
							//pos = _.indexOf(this.selected, id),
							pos = _.findLastIndex( this.selected, {
								id,
							} );

						if ( this.multiple ) {
							if ( $chk.is( ':checked' ) ) {
								if ( pos === -1 ) {
									this.selected.push( $select.closest( 'li' ).data( 'data' ) );
								}
							} else if ( pos >= 0 ) {
								this.selected.splice( pos, 1 );
							}
						} else {
							e.preventDefault();
							this.selected = [ $select.closest( 'li' ).data( 'data' ) ];
							this.addUsers();
						}
					},
					addUsers: function addUsers() {
						const $els = $( this.$el ).find( '.lp-result-item' );

						if ( this.callbacks && this.callbacks.addUsers ) {
							this.callbacks.addUsers.call( this, this.selected );
						}

						$( document ).triggerHandler( 'learn-press/modal-add-users', this.selected );
					},
					close: function close() {
						this.$emit( 'close' );
					},
				},
			} );
			window.LP.$modalSearchUsers = new $Vue( {
				el: '#vue-modal-search-users',
				data: {
					show: false,
					term: '',
					multiple: false,
					callbacks: {},
					textFormat: '{{display_name}} ({{email}})',
					exclude: 0,
				},
				methods: {
					open: function open( options ) {
						_.each( options.data, function( v, k ) {
							this[ k ] = v;
						}, this );

						this.callbacks = options.callbacks;
						this.focusSearch();
					},
					close: function close() {
						this.show = false;
					},
					focusSearch: _.debounce( function() {
						$( 'input[name="search"]', this.$el ).trigger( 'focus' );
					}, 200 ),
				},
			} );
		}() );
	} );
}( jQuery ) );

