( function( $ ) {
	function QuickTip( el, options ) {
		const $el = $( el ),
			uniId = $el.attr( 'data-id' ) || LP.uniqueId();

		options = $.extend( {
			event: 'hover',
			autoClose: true,
			single: true,
			closeInterval: 1000,
			arrowOffset: null,
			tipClass: '',
		}, options, $el.data() );

		$el.attr( 'data-id', uniId );

		let content = $el.attr( 'data-content-tip' ) || $el.html(),
			$tip = $( '<div class="learn-press-tip-floating">' + content + '</div>' ),
			t = null,
			closeInterval = 0,
			useData = false,
			arrowOffset = options.arrowOffset === 'el' ? $el.outerWidth() / 2 : 8,
			$content = $( '#__' + uniId );

		if ( $content.length === 0 ) {
			$( document.body ).append( $( '<div />' ).attr( 'id', '__' + uniId ).html( content ).css( 'display', 'none' ) );
		}

		content = $content.html();

		$tip.addClass( options.tipClass );

		$el.data( 'content-tip', content );
		if ( $el.attr( 'data-content-tip' ) ) {
			//$el.removeAttr('data-content-tip');
			useData = true;
		}

		closeInterval = options.closeInterval;

		if ( options.autoClose === false ) {
			$tip.append( '<a class="close"></a>' );
			$tip.on( 'click', '.close', function() {
				close();
			} );
		}

		function show() {
			if ( t ) {
				clearTimeout( t );
				return;
			}

			if ( options.single ) {
				$( '.learn-press-tip' ).not( $el ).LP( 'QuickTip', 'close' );
			}

			$tip.appendTo( document.body );
			const pos = $el.offset();

			$tip.css( {
				top: pos.top - $tip.outerHeight() - 8,
				left: pos.left - $tip.outerWidth() / 2 + arrowOffset,
			} );
		}

		function hide() {
			t && clearTimeout( t );
			t = setTimeout( function() {
				$tip.detach();
				t = null;
			}, closeInterval );
		}

		function close() {
			closeInterval = 0;
			hide();
			closeInterval = options.closeInterval;
		}

		function open() {
			show();
		}

		if ( ! useData ) {
			$el.html( '' );
		}

		if ( options.event === 'click' ) {
			$el.on( 'click', function( e ) {
				e.stopPropagation();
				show();
			} );
		}

		$( document ).on( 'learn-press/close-all-quick-tip', function() {
			close();
		} );
		$el.hover(
			function( e ) {
				e.stopPropagation();
				if ( options.event !== 'click' ) {
					show();
				}
			},
			function( e ) {
				e.stopPropagation();
				if ( options.autoClose ) {
					hide();
				}
			}
		).addClass( 'ready' );
		return {
			close,
			open,
		};
	}

	$.fn.LP( 'QuickTip', function( options ) {
		return $.each( this, function() {
			let $tip = $( this ).data( 'quick-tip' );

			if ( ! $tip ) {
				$tip = new QuickTip( this, options );
				$( this ).data( 'quick-tip', $tip );
			}

			if ( typeof options === 'string' ) {
				$tip[ options ] && $tip[ options ].apply( $tip );
			}
		} );
	} );
}
( jQuery ) );
