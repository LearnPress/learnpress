import Quiz from './quiz/index';
import './single-curriculum/components/compatible';

const { modal: { default: Modal } } = LP;

export default Quiz;

export const init = ( elem, settings ) => {
	wp.element.render(
		<Modal><Quiz settings={ settings } /></Modal>,
		[ ...document.querySelectorAll( elem ) ][ 0 ]
	);

	LP.Hook.doAction( 'lp-quiz-compatible-builder' );
};
