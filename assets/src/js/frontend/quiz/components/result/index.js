import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

const {useState} = React;

class Result extends Component {
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

        return <div className="quiz-result">

            <h3>{ __('Your Result', 'learnpress') }</h3>

            <div className="result-grade">
                <span className="result-achieved">0%</span>
                <span className="result-require">60%</span>
                <p className="result-message">Your grade is <strong>failed</strong> </p>
            </div>

            <ul className="result-statistic">
                <li className="result-statistic-field">
                    <label>Time spend</label>
                    <p>02:16:26:39</p>
                </li>
                <li className="result-statistic-field">
                    <label>Point</label>
                    <p>0 / 5</p>
                </li>
                <li className="result-statistic-field">
                    <label>Questions</label>
                    <p>5</p>
                </li>
                <li className="result-statistic-field">
                    <label>Correct</label>
                    <p>0</p>
                </li>
                <li className="result-statistic-field">
                    <label>Wrong</label>
                    <p>0</p>
                </li>
                <li className="result-statistic-field">
                    <label>Skipped</label>
                    <p>5</p>
                </li>
                <li className="result-statistic-field">
                    <label>Attempt</label>
                    <p>5/10</p>
                </li>
            </ul>

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
])(Result);