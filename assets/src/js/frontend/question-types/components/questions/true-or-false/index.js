import QuestionBase from '../../question-base';

class QuestionTrueOrFalse extends QuestionBase {
    constructor() {
        super(...arguments);
    }

    componentDidMount() {
        // this.setState({
        //     optionClass: [...this.state.optionClass, "new-class"]
        // })
    }

    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const optionClass = [...this.state.optionClass, "XYZ"];
        if (this.maybeShowCorrectAnswer()) {
            if (option.isTrue === 'yes') {
                optionClass.push('answer-correct');
            }
            if (answered) {
                if (option.isTrue === 'yes') {
                    (answered === option.value) && optionClass.push('answered-correct');
                } else {
                    (answered === option.value) && optionClass.push('answered-wrong');
                }
            } else {

            }
        }

        return optionClass;
    };
}

export default QuestionTrueOrFalse;