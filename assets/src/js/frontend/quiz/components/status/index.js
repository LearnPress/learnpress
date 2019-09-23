import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import Timer from '../timer';
const {useState} = React;

class Status extends Component {
    constructor() {
        super(...arguments);
    }

    componentDidMount() {

    }

    componentWillUnmount() {
    }

    render() {
        const {
            content
        } = this.props;

        const result = {
            timeSpend: 123,
            marks: [],
            questionsCount: 5,
            questionsCorrect: [],
            questionsWrong: [],
            questionsSkipped: []
        }

        return <div className="quiz-status">
            <Timer />
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

        return {
            content: getData('content')
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