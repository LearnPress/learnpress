import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import QuestionBase from '../../question-base';

const { isBoolean } = lodash;

class QuestionMultipleChoices extends QuestionBase {
	isCorrect = () => {
		const { answered, question } = this.props;

		if ( typeof question.correct !== 'undefined' ) {
			return question.correct;
		}

		if ( isBoolean( answered ) || ! answered ) {
			return false;
		}

		let i, option, options;

		for ( i = 0, options = this.getOptions(); i < options.length; i++ ) {
			option = options[ i ];

			if ( option.isTrue === 'yes' ) {
				if ( answered.indexOf( option.value ) === -1 ) {
					return false;
				}
			} else if ( answered.indexOf( option.value ) !== -1 ) {
				return false;
			}
		}

		return true;
	};

	getOptionClass = ( option ) => {
		const { answered, question } = this.props;
		const optionClass = [ ...this.state.optionClass ];

		if ( this.maybeShowCorrectAnswer() ) {
			if ( option.isTrue === 'yes' ) {
				optionClass.push( 'answer-correct' );
			}

			if ( answered ) {
				const isSelected = answered.indexOf( option.value ) !== -1;

				if (
					option.isTrue === 'yes' ||
					( isSelected && question.correct )
				) {
					isSelected && optionClass.push( 'answered-correct' );
				} else {
					isSelected && optionClass.push( 'answered-wrong' );
				}
			}
		}

		return optionClass;
	};
}

export default QuestionMultipleChoices;
