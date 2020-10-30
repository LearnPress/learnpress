/**
 * Become a Teacher form handler
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 3.0.0
 */

( function( $ ) {
	$( function() {
		$( 'form[name="become-teacher-form"]' ).each( function() {
			const $form = $( this ),
				$submit = $form.find( 'button[type="submit"]' ),
				hideMessages = function() {
					$( '.learn-press-error, .learn-press-message' ).fadeOut( 'fast', function() {
						$( this ).remove();
					} );
				},
				showMessages = function( messages ) {
					let m = [];
					if ( $.isPlainObject( messages ) ) {
						for ( const i in messages ) {
							m.push( $( messages[ i ] ) );
						}
					} else if ( $.isArray( messages ) ) {
						m = messages.reverse();
					} else {
						m = [ messages ];
					}
					for ( let i = 0; i < m.length; i++ ) {
						$( m[ i ] ).insertBefore( $form );
					}
				},
				blockForm = function( block ) {
					return $form.find( 'input, select, button, textarea' )
						.prop( 'disabled', !! block );
				},
				beforeSend = function() {
					hideMessages();

					blockForm( true )
						.filter( $submit )
						.data( 'origin-text', $submit.text() )
						.html( $submit.data( 'text' ) );
				},
				ajaxSuccess = function( response ) {
					if ( response.message ) {
						showMessages( response.message );
					}

					blockForm().filter( $submit ).html( $submit.data( 'origin-text' ) );

					if ( response.status === 'success' ) {
						$form.remove();
					} else {
						$submit.prop( 'disabled', false );
						$submit.html( $submit.data( 'text' ) );
					}
				},
				ajaxError = function( response ) {
					if ( response.message ) {
						showMessages( response.message );
					}

					blockForm().filter( $submit ).html( $submit.data( 'origin-text' ) );
				};

			$form.submit( function( e ) {
				e.preventDefault();
				if ( $form.triggerHandler( 'become_teacher_send' ) !== false ) {
					$.ajax( {
						url: window.location.href.addQueryVar( 'lp-ajax', 'request-become-a-teacher' ),
						data: $form.serialize(),
						dataType: 'json',
						type: 'post',
						beforeSend,
						success: ajaxSuccess,
						error: ajaxError,
					} );
				}
				return false;
			} );
		} );
	} );
}( jQuery ) );
