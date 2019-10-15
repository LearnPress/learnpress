import {Component} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import QuestionBase from '../../question-base';

const {isBoolean} = lodash;

class QuestionMultipleChoices extends QuestionBase {

    isCorrect = () => {
        const {
            answered
        } = this.props;

        if (isBoolean(answered) || !answered ) {
            return false;
        }

        let i, option, options;

        for (i = 0, options = this.getOptions(); i < options.length; i++) {
            option = options[i];

            if (option.is_true === 'yes') {
                if (answered.indexOf(option.value) === -1) {
                    return false;
                }
            } else {
                if (answered.indexOf(option.value) !== -1) {
                    return false;
                }
            }
        }

        console.log(this.getOptions())

        return true;
    };

    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const optionClass = [...this.state.optionClass];

        if (answered && this.maybeShowCorrectAnswer()) {
            if (option.is_true === 'yes') {
                optionClass.push('answer-correct');
                answered.indexOf(option.value) !== -1 && optionClass.push('answered-correct');
            } else {
                answered.indexOf(option.value) !== -1 && optionClass.push('answered-wrong');
            }
        }

        return optionClass;
    };

    // render(){
    //     {super.render()}
    // }
}

export default QuestionMultipleChoices;