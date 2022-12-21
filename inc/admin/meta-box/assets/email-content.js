( function( $ ) {
	$( '.lp-metabox-field__email-content' ).each( function() {
		const $field = $( this ),
			$select = $field.find( 'select.lp-email-format' ),
			$templates = $field.find( '.learn-press-email-template' ),
			$variables = $field.find( '.learn-press-email-variables' );

		function _insertVariableToEditor( edId, variable ) {
			let editorId = null;
			const activeEditor = tinyMCE.activeEditor;

			for ( editorId in tinyMCE.editors ) {
				if ( editorId == edId ) {
					break;
				}
				editorId = null;
			}

			if ( ! editorId ) {
				_insertVariableToTextarea( edId, variable );
				return;
			}

			if ( activeEditor && $( activeEditor.getElement() ).attr( 'id' ) == editorId ) {
				activeEditor.execCommand( 'insertHTML', false, variable );

				if ( $( activeEditor.getElement() ).is( ':visible' ) ) {
					_insertVariableToTextarea( edId, variable );
				}
			}
		}

		function _insertVariableToTextarea( eId, varibale ) {
			const $el = $( '#' + eId ).get( 0 );

			if ( document.selection ) {
				$el.focus();
				sel = document.selection.createRange();
				sel.text = varibale;
				$el.focus();
			} else if ( $el.selectionStart || $el.selectionStart == '0' ) {
				const startPos = $el.selectionStart;
				const endPos = $el.selectionEnd;
				const scrollTop = $el.scrollTop;
				$el.value = $el.value.substring( 0, startPos ) + varibale + $el.value.substring( endPos, $el.value.length );
				$el.focus();
				$el.selectionStart = startPos + varibale.length;
				$el.selectionEnd = startPos + varibale.length;
				$el.scrollTop = scrollTop;
			} else {
				$el.value += varibale;
				$el.focus();
			}
		}

		$select.on( 'change', function() {
			if ( this.value ) {
				$templates.filter( '.' + this.value ).removeClass( 'hide-if-js' ).siblings().addClass( 'hide-if-js' );
			}
		} ).trigger( 'change' );

		$variables.each( function() {
			const $list = $( this ),
				hasEditor = $list.hasClass( 'has-editor' );

			$list.on( 'click', 'li', function() {
				if ( hasEditor ) {
					_insertVariableToEditor( $list.attr( 'data-target' ), $( this ).data( 'variable' ) );
				} else {
					_insertVariableToTextarea( $list.attr( 'data-target' ), $( this ).data( 'variable' ) );
				}
			} );
		} );
	} );
}( jQuery ) );
