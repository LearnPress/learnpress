( function( $ ) {
	const $doc = $( document );
	let isRunning = false;

	const installSampleCourse = function installSampleCourse( e ) {
		e.preventDefault();

		const $button = $( this );

		if ( isRunning ) {
			return;
		}

		if ( ! confirm( lpGlobalSettings.i18n.confirm_install_sample_data ) ) {
			return;
		}

		$button.addClass( 'disabled' ).html( $button.data( 'installing-text' ) );
		$( '.lp-install-sample-data-response' ).remove();
		isRunning = true;
		$.ajax( {
			url: $button.attr( 'href' ),
			data: $( '.lp-install-sample-data-options' ).serializeJSON(),
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
			error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
			},
		} );
	};

	const uninstallSampleCourse = function uninstallSampleCourse( e ) {
		e.preventDefault();

		const $button = $( this );

		if ( isRunning ) {
			return;
		}

		if ( ! confirm( lpGlobalSettings.i18n.confirm_uninstall_sample_data ) ) {
			return;
		}

		$button.addClass( 'disabled' ).html( $button.data( 'uninstalling-text' ) );
		isRunning = true;
		$.ajax( {
			url: $button.attr( 'href' ), success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
			}, error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
			},
		} );
	};

	const clearHardCache = function clearHardCache( e ) {
		e.preventDefault();
		const $button = $( this );

		if ( $button.hasClass( 'disabled' ) ) {
			return;
		}

		$button.addClass( 'disabled' ).html( $button.data( 'cleaning-text' ) );
		$.ajax( {
			url: $button.attr( 'href' ), data: {}, success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
			}, error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
			},
		} );
	};

	const toggleHardCache = function toggleHardCache() {
		$.ajax( {
			url: 'admin.php?page=lp-toggle-hard-cache-option',
			data: { v: this.checked ? 'yes' : 'no' },
			success( response ) {
			},
			error() {
			},
		} );
	};

	const toggleOptions = function( e ) {
		e.preventDefault();
		$( '.lp-install-sample-data-options' ).toggleClass( 'hide-if-js' );
	};

	const optimizeDatabase = function( e ) {
		e.preventDefault();
		const $param = { 'lp-ajax': 'lp-database-optimize', 'lp-nonce': $( 'input[name=lp-nonce]' ).val() };

		$el = $( e.target );
		const el_spinner = $el.closest( '.tools-button' ).find( '.spinner' );
		el_spinner.css( 'visibility', 'visible' );

		$.post( lpGlobalSettings.ajax, $param )
			.done( function( res ) {
				console.log( res );
			} )
			.fail( function( err ) {} )
			.always( function() {
				el_spinner.css( 'visibility', 'hidden' );
			} );
	};

	$doc.on( 'click', '#learn-press-install-sample-data', installSampleCourse ).
		on( 'click', '#learn-press-uninstall-sample-data',
			uninstallSampleCourse ).
		on( 'click', '#learn-press-clear-cache', clearHardCache ).
		on( 'click', 'input[name="enable_hard_cache"]', toggleHardCache ).
		on( 'click', '#learn-press-install-sample-data-options', toggleOptions ).
		on( 'click', '.lp-button-optimize-database', optimizeDatabase );
}( jQuery ) );
