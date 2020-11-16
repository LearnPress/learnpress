import QuestionBase from '../../question-base';

class QuestionTrueOrFalse extends QuestionBase {
	getOptionClass = ( option ) => {
		const { answered } = this.props;
		const optionClass = [ ...this.state.optionClass ];

		if ( this.maybeShowCorrectAnswer() ) {
			if ( option.isTrue === 'yes' ) {
				optionClass.push( 'answer-correct' );
			}

			if ( answered ) {
				if ( option.isTrue === 'yes' ) {
					( answered === option.value ) && optionClass.push( 'answered-correct' );
				} else {
					( answered === option.value ) && optionClass.push( 'answered-wrong' );
				}
			}
		}

		return optionClass;
	};
}

export default QuestionTrueOrFalse;
