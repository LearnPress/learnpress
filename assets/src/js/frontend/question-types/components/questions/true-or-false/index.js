import QuestionBase from '../../question-base';

class QuestionTrueOrFalse extends QuestionBase {
    constructor(){
        super(...arguments);
    }

    componentDidMount(){
        // this.setState({
        //     optionClass: [...this.state.optionClass, "new-class"]
        // })
    }

    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const optionClass = [...this.state.optionClass, "XYZ"];
        if (!answered && this.maybeShowCorrectAnswer()) {

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

export default QuestionTrueOrFalse;