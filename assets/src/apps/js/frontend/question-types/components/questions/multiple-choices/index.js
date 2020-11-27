import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import QuestionBase from '../../question-base';

const { isBoolean } = lodash;

class QuestionMultipleChoices extends QuestionBase {
	isCorrect = () => {
		const { answered } = this.props;

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
		const { answered } = this.props;
		const optionClass = [ ...this.state.optionClass ];

		if ( this.maybeShowCorrectAnswer() ) {
			if ( option.isTrue === 'yes' ) {
				optionClass.push( 'answer-correct' );
			}

			if ( answered ) {
				if ( option.isTrue === 'yes' ) {
					answered.indexOf( option.value ) !== -1 && optionClass.push( 'answered-correct' );
				} else {
					answered.indexOf( option.value ) !== -1 && optionClass.push( 'answered-wrong' );
				}
			}
		}

		return optionClass;
	};
}

export default QuestionMultipleChoices;
