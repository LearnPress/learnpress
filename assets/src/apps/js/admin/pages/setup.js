( function( $ ) {
	'use strict';
	let $main, $setupForm;

	const checkForm = function checkForm( $form ) {
		const $emails = $form.find( 'input[type="email"]' );
		let valid = true;

		$emails.each( function() {
			const $this = $( this );
			$this.css( 'border-color', '' );

			switch ( $this.attr( 'name' ) ) {
			case 'settings[paypal][paypal_email]':
			case 'settings[paypal][paypal_sandbox_email]':
				if ( ! $this.closest( 'tr' ).prev().find( 'input[type="checkbox"]' ).is( ':checked' ) ) {
					return;
				}
				break;
			}

			if ( ! isEmail( this.value ) ) {
				valid = false;
				$this.css( 'border-color', '#FF0000' );
			}
		} );

		return valid;
	};

	const blockContent = function blockContent( block ) {
		$main.toggleClass( 'loading', block === undefined ? true : block );
	};

	const getFormData = function getFormData( more ) {
		$setupForm = $( '#learn-press-setup-form' );
		const data = $setupForm.serializeJSON();

		return $.extend( data, more || {} );
	};

	const replaceMainContent = function replaceMainContent( newContent ) {
		const $newContent = $( newContent );
		$main.replaceWith( $newContent );
		$main = $newContent;
	};

	const navPages = function navPages( e ) {
		e.preventDefault();

		if ( ! checkForm( $setupForm ) ) {
			return;
		}

		const loadUrl = $( this ).attr( 'href' );

		$main.addClass( 'loading' );
		$.post( {
			url: loadUrl,
			data: getFormData(),
			success( res ) {
				const $html = $( res );
				replaceMainContent( $html.contents().filter( '#main' ) );

				LP.setUrl( loadUrl );

				$( '.learn-press-dropdown-pages' ).LP( 'DropdownPages' );
				$( '.learn-press-tip' ).LP( 'QuickTip' );
				$main.removeClass( 'loading' );
			},
		} );
	};

	const updateCurrency = function updateCurrency() {
		const m = $( this ).children( ':selected' ).html().match( /\((.*)\)/ ),
			symbol = m ? m[ 1 ] : '';
		$( '#currency-pos' ).children().each( function() {
			const $option = $( this );
			let text = $option.html();

			switch ( $option.val() ) {
			case 'left':
				text = text.replace( /\( (.*)69/, '( ' + symbol + '69' );
				break;
			case 'right':
				text = text.replace( /9([^0-9]*) \)/, '9' + symbol + ' )' );
				break;
			case 'left_with_space':
				text = text.replace( /\( (.*) 6/, '( ' + symbol + ' 6' );
				break;
			case 'right_with_space':
				text = text.replace( /9 (.*) \)/, '9 ' + symbol + ' )' );
				break;
			}
			$option.html( text );
		} );
	};

	const updatePrice = function updatePrice() {
		$.post( {
			url: '',
			dataType: 'html',
			data: getFormData( {
				'lp-ajax': 'get-price-format',
			} ),
			success( res ) {
				$( '#preview-price' ).html( res );
			},
		} );
	};

	const createPages = function createPages( e ) {
		e.preventDefault();
		blockContent();

		$.post( {
			url: $( this ).attr( 'href' ),
			dataType: 'html',
			data: getFormData( {
				'lp-ajax': 'setup-create-pages',
			} ),
			success( res ) {
				replaceMainContent( $( res ).contents().filter( '#main' ) );
				$( '.learn-press-dropdown-pages' ).LP( 'DropdownPages' );
				blockContent( false );
			},
		} );
	};

	const installSampleCourse = function installSampleCourse( e ) {
		e.preventDefault();

		const $button = $( this );
		blockContent();

		$.post( {
			url: $( this ).attr( 'href' ),
			dataType: 'html',
			data: {},
			success( res ) {
				blockContent( false );
				$button.replaceWith( $( res ).find( 'a:first' ).addClass( 'button button-primary' ) );
			},
		} );
	};

	function isEmail( email ) {
		const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		return re.test( email );
	}

	$( function() {
		$main = $( '#main' );
		$setupForm = $( '#learn-press-setup-form' );
		$( '.learn-press-select2' ).select2();

		$( document ).
			on( 'click', '.buttons .button', navPages ).
			on( 'change', '#currency', updateCurrency ).
			on( 'change', 'input, select', updatePrice ).
			on( 'click', '#create-pages', createPages ).
			on( 'click', '#install-sample-course', installSampleCourse );
	} );
}( jQuery ) );
