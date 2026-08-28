import getStepsUpgradeStatus from './tools/database/upgrade';
import createIndexes from './tools/database/create_indexs';
import reUpgradeDB from './tools/database/re-upgrade-db';
import cleanDatabases from './tools/database/clean_database';

import ResetCourseProgress from './tools/reset-course-progress';
import ResetItemProgress from './tools/reset-item-progress';
import HandleSampleData from './tools/handle-sample-data';

( function( $ ) {
	const $doc = $( document );

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

	$( function() {
		getStepsUpgradeStatus();
		createIndexes();
		reUpgradeDB();
		cleanDatabases();
		( new ResetCourseProgress() ).init();
		( new ResetItemProgress() ).init();
		( new HandleSampleData() ).init();
		$doc.on( 'click', '#learn-press-clear-cache', clearHardCache )
			.on( 'click', 'input[name="enable_hard_cache"]', toggleHardCache );
	} );
}( jQuery ) );
