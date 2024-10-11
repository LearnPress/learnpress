import getStepsUpgradeStatus from './tools/database/upgrade';
import createIndexes from './tools/database/create_indexs';
import reUpgradeDB from './tools/database/re-upgrade-db';
import cleanDatabases from './tools/database/clean_database';

import resetData from './tools/reset-data';

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
		$( '.lp-install-sample__response' ).remove();
		isRunning = true;

		$.ajax( {
			url: $button.attr( 'href' ),
			data: $( '.lp-install-sample__options' ).serializeJSON(),
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
			error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
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
			url: $button.attr( 'href' ),
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
			error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
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
			url: $button.attr( 'href' ),
			data: {},
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
			},
			error() {
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

	const toggleOptions = function toggleOptions( e ) {
		e.preventDefault();
		$( '.lp-install-sample__options' ).toggleClass( 'hide-if-js' );
	};

	const formatValueInput = ( e ) => {
		const input = $( e.target );
		let value = input.val();
		value = value.replace( /[^0-9]/g, '' );

		input.val( value );

		if ( parseInt( value, 10 ) < input.attr( 'min' ) ) {
			input.val( input.attr( 'min' ) );
		}

		if ( parseInt( value, 10 ) > input.attr( 'max' ) ) {
			input.val( input.attr( 'max' ) );
		}
	};

	$( function() {
		getStepsUpgradeStatus();
		createIndexes();
		reUpgradeDB();
		resetData();
		cleanDatabases();
		$doc.on( 'click', '.lp-install-sample__install', installSampleCourse )
			.on( 'click', '.lp-install-sample__uninstall', uninstallSampleCourse )
			.on( 'click', '#learn-press-clear-cache', clearHardCache )
			.on( 'click', 'input[name="enable_hard_cache"]', toggleHardCache )
			.on( 'click', '.lp-install-sample__toggle-options', toggleOptions )
			.on( 'change', 'input[name="section-range[]"]', formatValueInput )
			.on( 'change', 'input[name="item-range[]"]', formatValueInput )
			.on( 'change', 'input[name="question-range[]"]', formatValueInput )
			.on( 'change', 'input[name="answer-range[]"]', formatValueInput )
			.on( 'change', 'input[name="course-price"]', formatValueInput );
	} );
}( jQuery ) );
