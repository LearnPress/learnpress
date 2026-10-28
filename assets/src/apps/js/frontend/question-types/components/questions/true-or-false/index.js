import QuestionBase from '../../question-base';

class QuestionTrueOrFalse extends QuestionBase {
	getOptionClass = ( option ) => {
		const { answered, question } = this.props;
		const optionClass = [ ...this.state.optionClass ];

		if ( this.maybeShowCorrectAnswer() && option.isTrue === 'yes' ) {
			optionClass.push( 'answer-correct' );
		}

		// isTrue not send when option "Show the correct answer" off, use result of server.
		if ( this.maybeShowResult() && answered && answered === option.value ) {
			if ( option.isTrue === 'yes' || question.correct ) {
				optionClass.push( 'answered-correct' );
			} else {
				optionClass.push( 'answered-wrong' );
			}
		}

		return optionClass;
	};
}

export default QuestionTrueOrFalse;
