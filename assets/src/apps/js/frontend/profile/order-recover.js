const $ = window.jQuery;

export default function recoverOrder() {
		const wrap = $( '.order-recover' ),
		buttonRecoverOrder = wrap.find( '.button-recover-order' );

		const ajaxRecover = () => {
			wrap.find( '.learn-press-message' ).remove();

			$( '.profile-recover-order' ).find( '.learn-press-message' ).remove();

			$.post( {
				url: '',
				data: wrap.serializeJSON(),
				beforeSend() {
					buttonRecoverOrder.addClass( 'loading' ).attr( 'disabled', 'disabled' );
				},
				success( response ) {
					response = LP.parseJSON( response );

					if ( response.message ) {
						const $msg = $( '<div class="learn-press-message icon"><i class="fa"></i> ' + response.message + '</div>' );

						if ( response.result == 'error' ) {
							$msg.addClass( 'error' );
						}

						wrap.before( $msg );
					}

					if ( response.redirect ) {
						window.location.href = response.redirect;
					}

					buttonRecoverOrder.removeClass( 'loading' ).removeAttr( 'disabled', '' );
				},
				error() {
					buttonRecoverOrder.removeClass( 'loading' ).removeAttr( 'disabled', '' );
				},
			} );
		}

		buttonRecoverOrder.on( 'click', ajaxRecover );
};

