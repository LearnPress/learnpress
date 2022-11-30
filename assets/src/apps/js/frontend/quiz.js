import Quiz from './quiz/index';
import './single-curriculum/components/compatible';

const { modal: { default: Modal } } = LP;

export default Quiz;

export const init = ( elem, settings ) => {
	// For no require enroll
	if ( lpQuizSettings.checkNorequizenroll === 1 ) {
		const keyQuizOff = 'quiz_off_' + lpQuizSettings.id;
		const quizDataOffStr = window.localStorage.getItem( keyQuizOff );

		if ( null !== quizDataOffStr ) {
			const quizDataOff = JSON.parse( quizDataOffStr );
			settings.status = quizDataOff.status;
			settings.questions = quizDataOff.questions;

			if ( 'started' === quizDataOff.status ) {
				const now = Date.now();

				settings.total_time = Math.floor( ( quizDataOff.endTime - now ) / 1000 );
			} else if ( 'completed' === quizDataOff.status ) {
				settings.results = quizDataOff.results;
				settings.answered = quizDataOff.results.answered;
				settings.questions = quizDataOff.results.questions;
			}

			if ( undefined !== quizDataOff.checked_questions ) {
				settings.checked_questions = quizDataOff.checked_questions;
			}

			if ( undefined !== quizDataOff.question_options ) {
				//settings.checked_questions = quizDataOff.checked_questions;

				for ( const i in settings.questions ) {
					const question = settings.questions[ i ];

					if ( undefined !== quizDataOff.question_options[ question.id ] ) {
						question.options = quizDataOff.question_options[ question.id ];
					}

					settings.questions[ i ] = question;
				}
			}
		}
	}

	//console.log(settings);

	wp.element.render(
		<Modal><Quiz settings={ settings } /></Modal>,
		[ ...document.querySelectorAll( elem ) ][ 0 ]
	);

	LP.Hook.doAction( 'lp-quiz-compatible-builder' );
};
