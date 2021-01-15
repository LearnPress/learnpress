import QuizStore from './store/quiz';
import HTTP from './http';
import './fill-in-blanks';

window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;

/**
 * Init app.
 *
 * @since 3.0.0
 */

window.jQuery( document ).ready( function() {
	window.LP_Quiz_Store = new $Vuex.Store( QuizStore( lp_quiz_editor ) );
	HTTP( { ns: 'LPListQuizQuestionsRequest', store: LP_Quiz_Store } );

	setTimeout( () => {
		window.LP_Quiz_Editor = new $Vue( {
			el: '#admin-editor-lp_quiz',
			template: '<lp-quiz-editor></lp-quiz-editor>',
		} );
	}, 100 );
} );
