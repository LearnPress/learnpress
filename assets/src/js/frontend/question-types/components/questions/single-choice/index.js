import QuestionBase from '../../question-base';

class QuestionSingleChoice extends QuestionBase {
    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const optionClass = [...this.state.optionClass];

        if (answered!==undefined && this.maybeShowCorrectAnswer()) {
            if (option.is_true === 'yes') {
                optionClass.push('answer-correct');
                (answered === option.value) && optionClass.push('answered-correct');
            } else {
                (answered === option.value) && optionClass.push('answered-wrong');
            }
        }

        return optionClass;
    };
}

export default QuestionSingleChoice;