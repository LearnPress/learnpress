import HTTP from './http';
import Store from './store/question';
import './fill-in-blanks';

window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;

const $ = window.jQuery;

/**
 * Init app.
 *
 * @since 3.0.0
 */
$( document ).ready( function() {
	window.LP_Question_Store = new $Vuex.Store( Store( lp_question_editor ) );

	HTTP( { ns: 'LPQuestionEditorRequest', store: LP_Question_Store } );

	setTimeout( () => {
		if ( $( '#admin-editor-lp_question' ).length ) {
			window.LP_Question_Editor = new $Vue( {
				el: '#admin-editor-lp_question',
				template: '<lp-question-editor></lp-question-editor>',
			} );
		}
	}, 100 );
} );
