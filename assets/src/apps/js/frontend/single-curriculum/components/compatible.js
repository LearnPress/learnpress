/**
 * Compatible with Page Builder.
 *
 * @author nhamdv
 */

LP.Hook.addAction( 'lp-compatible-builder', () => {
	if ( typeof elementorFrontend !== 'undefined' ) {
		LP.Hook.removeAction( 'lp-compatible-builder' );

		[ ...document.querySelectorAll( '#popup-content' ) ][ 0 ].addEventListener( 'scroll', () => {
			Waypoint.refreshAll();
			window.dispatchEvent( new Event( 'resize' ) );
		} );
	}
} );

LP.Hook.addAction( 'lp-quiz-compatible-builder', () => {
	if ( typeof elementorFrontend !== 'undefined' ) {
		LP.Hook.removeAction( 'lp-quiz-compatible-builder' );

		LP.Hook.doAction( 'lp-compatible-builder' );

		return window.elementorFrontend.init();
	}
} );

LP.Hook.addAction( 'lp-question-compatible-builder', () => {
	if ( typeof elementorFrontend !== 'undefined' ) {
		LP.Hook.removeAction( 'lp-question-compatible-builder' );
		LP.Hook.removeAction( 'lp-quiz-compatible-builder' );

		LP.Hook.doAction( 'lp-compatible-builder' );

		return window.elementorFrontend.init();
	}
} );
