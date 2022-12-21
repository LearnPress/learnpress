( function( $ ) {
	window.FIB = {

		getSelectedText: function getSelectedText() {
			let html = '';
			if ( typeof window.getSelection !== 'undefined' ) {
				const sel = window.getSelection();
				if ( sel.rangeCount ) {
					const container = document.createElement( 'div' );
					for ( let i = 0, len = sel.rangeCount; i < len; ++i ) {
						container.appendChild( sel.getRangeAt( i ).cloneContents() );
					}
					html = container.innerHTML;
				}
			} else if ( typeof document.selection !== 'undefined' ) {
				if ( document.selection.type === 'Text' ) {
					html = document.selection.createRange().htmlText;
				}
			}
			return html;
		},

		createTextNode( content ) {
			return document.createTextNode( content );
		},

		isContainHtml: function isContainHtml( content ) {
			const $el = $( content ),
				sel = 'b.fib-blank';
			return $el.is( sel ) || $el.find( sel ).length || $el.parent().is( sel );
		},

		getSelectionRange: function getSelectionRange() {
			let t = '';
			if ( window.getSelection ) {
				t = window.getSelection();
			} else if ( document.getSelection ) {
				t = document.getSelection();
			} else if ( document.selection ) {
				t = document.selection.createRange().text;
			}
			return t;
		},

		outerHTML( $dom ) {
			return $( '<div>' ).append( $( $dom ).clone() ).html();
		},

		doUpgrade( callback ) {
			$.ajax( {
				url: '',
				data: {
					'lp-ajax': 'fib-upgrade',
				},
				success( res ) {
					console.log( res );
					callback && callback.call( res );
				},
			} );
		},
	};

	$( document ).ready( function() {
		$( '#do-upgrade-fib' ).on( 'click', function() {
			const $button = $( this ).prop( 'disabled', true ).addClass( 'ajaxloading' );
			FIB.doUpgrade( function() {
				$button.prop( 'disabled', false ).removeClass( 'ajaxloading' );
			} );
		} );
	} );
}( jQuery ) );
