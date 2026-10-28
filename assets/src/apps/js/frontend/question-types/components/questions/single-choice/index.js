/* eslint-disable no-mixed-spaces-and-tabs */
import QuestionBase from '../../question-base';

class QuestionSingleChoice extends QuestionBase {
	getOptionClass = ( option ) => {
		const { answered, question } = this.props;
		const optionClass = [ ...this.state.optionClass ];

		if ( this.maybeShowCorrectAnswer() && option.isTrue === 'yes' ) {
			optionClass.push( 'answer-correct' );
		}

		if ( this.maybeShowResult() && answered ) {
			const isSelected = answered === option.value;
			if ( isSelected ) {
				if ( option.isTrue === 'yes' || question.correct ) {
					optionClass.push( 'answered-correct' );
				} else {
					optionClass.push( 'answered-wrong' );
				}
			}
		}

		return optionClass;
	};
}

export default QuestionSingleChoice;
