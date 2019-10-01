import {Component} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import QuestionBase from '../../question-base';

class QuestionMultipleChoices extends QuestionBase {

    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const optionClass = [...this.state.optionClass];

        if (answered!==undefined && this.maybeShowCorrectAnswer()) {
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