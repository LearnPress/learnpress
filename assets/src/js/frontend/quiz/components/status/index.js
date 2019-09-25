import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import Timer from '../timer';
const {useState} = React;

class Status extends Component {
    constructor() {
        super(...arguments);
    }

    getCurrentQuestionIndex = () => {
        const {
            questionIds,
            currentQuestion
        } = this.props;

        const at = questionIds.indexOf(currentQuestion)

        return at !== false ? at + 1 : 0;
    }

    render() {
        const {
            content,
            questionIds
        } = this.props;

        const result = {
            timeSpend: 123,
            marks: [],
            questionsCount: 5,
            questionsCorrect: [],
            questionsWrong: [],
            questionsSkipped: []
        };

        const c = this.getCurrentQuestionIndex();

        return <div className="quiz-status">
            <div>
                <div>{`${c} of ${questionIds.length}`}</div>
                <Timer />
            </div>
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

        return {
            questionIds: getData('questionIds'),
            currentQuestion: getData('currentQuestion')
        }
    }),
    withDispatch((dispatch) => {
        const {
            setQuizData,
            startQuiz
        } = dispatch('learnpress/quiz');

        return {
            setQuizData,
            startQuiz
        }
    })
])(Status);