/**
 * Compatible with Page Builder.
 *
 * @author nhamdv
 */

LP.Hook.addAction( 'lp-compatible-builder', () => {
	LP.Hook.removeAction( 'lp-compatible-builder' );

	if ( typeof elementorFrontend !== 'undefined' ) {
		[ ...document.querySelectorAll( '#popup-content' ) ][ 0 ].addEventListener( 'scroll', () => {
			Waypoint.refreshAll();
			window.dispatchEvent( new Event( 'resize' ) );
		} );
	}

	if ( typeof vc_js !== 'undefined' ) {
		[ ...document.querySelectorAll( '#popup-content' ) ][ 0 ].addEventListener( 'scroll', () => {
			VcWaypoint.refreshAll();
		} );
	}
} );

LP.Hook.addAction( 'lp-quiz-compatible-builder', () => {
	LP.Hook.removeAction( 'lp-quiz-compatible-builder' );

	LP.Hook.doAction( 'lp-compatible-builder' );

	if ( typeof elementorFrontend !== 'undefined' ) {
		return window.elementorFrontend.init();
	}

	if ( typeof vc_js !== 'undefined' ) {
		vc_round_charts();
		vc_pieChart();
		vc_line_charts();

		return window.vc_js();
	}
} );

LP.Hook.addAction( 'lp-question-compatible-builder', () => {
	LP.Hook.removeAction( 'lp-question-compatible-builder' );
	LP.Hook.removeAction( 'lp-quiz-compatible-builder' );
	LP.Hook.doAction( 'lp-compatible-builder' );

	if ( typeof elementorFrontend !== 'undefined' ) {
		return window.elementorFrontend.init();
	}

	if ( typeof vc_js !== 'undefined' ) {
		vc_round_charts();
		vc_pieChart();
		vc_line_charts();

		return window.vc_js();
	}
} );
