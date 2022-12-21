const $ = window.jQuery;
const MessageBox = {
	$block: null,
	$window: null,
	events: {},
	instances: [],
	instance: null,
	quickConfirm( elem, args ) {
		const $e = $( elem );
		$( '[learn-press-quick-confirm]' ).each( function() {
			let $ins;
			( $ins = $( this ).data( 'quick-confirm' ) ) && ( console.log( $ins ), $ins.destroy() );
		} );
		! $e.attr( 'learn-press-quick-confirm' ) && $e.attr( 'learn-press-quick-confirm', 'true' ).data( 'quick-confirm',
			new ( function( elem, args ) {
				var $elem = $( elem ),
					$div = $( '<span class="learn-press-quick-confirm"></span>' ).insertAfter( $elem ), //($(document.body)),
					offset = $( elem ).position() || { left: 0, top: 0 },
					timerOut = null,
					timerHide = null,
					n = 3,
					hide = function() {
						$div.fadeOut( 'fast', function() {
							$( this ).remove();
							$div.parent().css( 'position', '' );
						} );
						$elem.removeAttr( 'learn-press-quick-confirm' ).data( 'quick-confirm', undefined );
						stop();
					},
					stop = function() {
						timerHide && clearInterval( timerHide );
						timerOut && clearInterval( timerOut );
					},
					start = function() {
						timerOut = setInterval( function() {
							if ( --n == 0 ) {
								hide.call( $div[ 0 ] );
								typeof ( args.onCancel ) === 'function' && args.onCancel( args.data );
								stop();
							}
							$div.find( 'span' ).html( ' (' + n + ')' );
						}, 1000 );

						timerHide = setInterval( function() {
							if ( ! $elem.is( ':visible' ) || $elem.css( 'visibility' ) == 'hidden' ) {
								stop();
								$div.remove();
								$div.parent().css( 'position', '' );
								typeof ( args.onCancel ) === 'function' && args.onCancel( args.data );
							}
						}, 350 );
					};
				args = $.extend( {
					message: '',
					data: null,
					onOk: null,
					onCancel: null,
					offset: { top: 0, left: 0 },
				}, args || {} );
				$div.html( args.message || $elem.attr( 'data-confirm-remove' ) || 'Are you sure?' ).append( '<span> (' + n + ')</span>' ).css( {} );
				$div.click( function() {
					typeof ( args.onOk ) === 'function' && args.onOk( args.data );
					hide();
				} ).hover( function() {
					stop();
				}, function() {
					start();
				} );
				//$div.parent().css('position', 'relative');
				$div.css( {
					left: ( ( offset.left + $elem.outerWidth() ) - $div.outerWidth() ) + args.offset.left,
					top: offset.top + $elem.outerHeight() + args.offset.top + 5,
				} ).hide().fadeIn( 'fast' );
				start();

				this.destroy = function() {
					$div.remove();
					$elem.removeAttr( 'learn-press-quick-confirm' ).data( 'quick-confirm', undefined );
					stop();
				};
			} )( elem, args )
		);
	},
	show( message, args ) {
		//this.hide();
		$.proxy( function() {
			args = $.extend( {
				title: '',
				buttons: '',
				events: false,
				autohide: false,
				message,
				data: false,
				id: LP.uniqueId(),
				onHide: null,
			}, args || {} );

			this.instances.push( args );
			this.instance = args;

			const $doc = $( document ),
				$body = $( document.body );
			if ( ! this.$block ) {
				this.$block = $( '<div id="learn-press-message-box-block"></div>' ).appendTo( $body );
			}
			if ( ! this.$window ) {
				this.$window = $( '<div id="learn-press-message-box-window"><div id="message-box-wrap"></div> </div>' ).insertAfter( this.$block );
				this.$window.click( function() {
				} );
			}
			//this.events = args.events || {};
			this._createWindow( message, args.title, args.buttons );
			this.$block.show();
			this.$window.show().attr( 'instance', args.id );
			$( window )
				.bind( 'resize.message-box', $.proxy( this.update, this ) )
				.bind( 'scroll.message-box', $.proxy( this.update, this ) );
			this.update( true );
			if ( args.autohide ) {
				setTimeout( function() {
					LP.MessageBox.hide();
					typeof ( args.onHide ) === 'function' && args.onHide.call( LP.MessageBox, args );
				}, args.autohide );
			}
		}, this )();
	},
	blockUI( message ) {
		message = ( message !== false ? ( message ? message : 'Wait a moment' ) : '' ) + '<div class="message-box-animation"></div>';
		this.show( message );
	},
	hide( delay, instance ) {
		if ( instance ) {
			this._removeInstance( instance.id );
		} else if ( this.instance ) {
			this._removeInstance( this.instance.id );
		}
		if ( this.instances.length === 0 ) {
			if ( this.$block ) {
				this.$block.hide();
			}
			if ( this.$window ) {
				this.$window.hide();
			}
			$( window )
				.unbind( 'resize.message-box', this.update )
				.unbind( 'scroll.message-box', this.update );
		} else if ( this.instance ) {
			this._createWindow( this.instance.message, this.instance.title, this.instance.buttons );
		}
	},
	update( force ) {
		let that = this,
			$wrap = this.$window.find( '#message-box-wrap' ),
			timer = $wrap.data( 'timer' ),
			_update = function() {
				LP.Hook.doAction( 'learn_press_message_box_before_resize', that );
				let $content = $wrap.find( '.message-box-content' ).css( 'height', '' ).css( 'overflow', 'hidden' ),
					width = $wrap.outerWidth(),
					height = $wrap.outerHeight(),
					contentHeight = $content.height(),
					windowHeight = $( window ).height(),
					top = $wrap.offset().top;
				if ( contentHeight > windowHeight - 50 ) {
					$content.css( {
						height: windowHeight - 25,
					} );
					height = $wrap.outerHeight();
				} else {
					$content.css( 'height', '' ).css( 'overflow', '' );
				}
				$wrap.css( {
					marginTop: ( $( window ).height() - height ) / 2,
				} );
				LP.Hook.doAction( 'learn_press_message_box_resize', height, that );
			};
		if ( force ) {
			_update();
		}
		timer && clearTimeout( timer );
		timer = setTimeout( _update, 250 );
	},
	_removeInstance( id ) {
		for ( let i = 0; i < this.instances.length; i++ ) {
			if ( this.instances[ i ].id === id ) {
				this.instances.splice( i, 1 );

				const len = this.instances.length;
				if ( len ) {
					this.instance = this.instances[ len - 1 ];
					this.$window.attr( 'instance', this.instance.id );
				} else {
					this.instance = false;
					this.$window.removeAttr( 'instance' );
				}
				break;
			}
		}
	},
	_getInstance( id ) {
		for ( let i = 0; i < this.instances.length; i++ ) {
			if ( this.instances[ i ].id === id ) {
				return this.instances[ i ];
			}
		}
	},
	_createWindow( message, title, buttons ) {
		const $wrap = this.$window.find( '#message-box-wrap' ).html( '' );
		if ( title ) {
			$wrap.append( '<h3 class="message-box-title">' + title + '</h3>' );
		}
		$wrap.append( $( '<div class="message-box-content"></div>' ).html( message ) );
		if ( buttons ) {
			const $buttons = $( '<div class="message-box-buttons"></div>' );
			switch ( buttons ) {
			case 'yesNo':
				$buttons.append( this._createButton( LP_Settings.localize.button_yes, 'yes' ) );
				$buttons.append( this._createButton( LP_Settings.localize.button_no, 'no' ) );
				break;
			case 'okCancel':
				$buttons.append( this._createButton( LP_Settings.localize.button_ok, 'ok' ) );
				$buttons.append( this._createButton( LP_Settings.localize.button_cancel, 'cancel' ) );
				break;
			default:
				$buttons.append( this._createButton( LP_Settings.localize.button_ok, 'ok' ) );
			}
			$wrap.append( $buttons );
		}
	},
	_createButton( title, type ) {
		const $button = $( '<button type="button" class="button message-box-button message-box-button-' + type + '">' + title + '</button>' ),
			callback = 'on' + ( type.substr( 0, 1 ).toUpperCase() + type.substr( 1 ) );
		$button.data( 'callback', callback ).click( function() {
			const instance = $( this ).data( 'instance' ),
				callback = instance.events[ $( this ).data( 'callback' ) ];
			if ( $.type( callback ) === 'function' ) {
				if ( callback.apply( LP.MessageBox, [ instance ] ) === false ) {
					// return;
				} else {
					LP.MessageBox.hide( null, instance );
				}
			} else {
				LP.MessageBox.hide( null, instance );
			}
		} ).data( 'instance', this.instance );
		return $button;
	},
};

export default MessageBox;
