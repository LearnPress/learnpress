import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, _x, sprintf} from '@wordpress/i18n';

const {get} = lodash;

class Result extends Component {
    constructor() {
        super(...arguments);
    }

    getResultMessage(results) {
        return sprintf(__('Your grade is <strong>%s</strong>', 'learnpress'), results.grade_text);
    }

    getResultPercentage(results) {
        return parseFloat(results.result).toFixed(2);
    }

    render() {
        const {
            results
        } = this.props;

        return <div className="quiz-result">

            <h3>{ __('Your Result', 'learnpress') }</h3>
            <div className="result-grade">
                <span className="result-achieved">{ this.getResultPercentage(results) }%</span>
                <span
                    className="result-require">{ undefined !== results.passing_grade ? results.passing_grade : _x('-', 'unknown passing grade value', 'learnpress') }</span>
                <p className="result-message" dangerouslySetInnerHTML={ {__html: this.getResultMessage(results)} }>
                </p>
            </div>

            <ul className="result-statistic">
                <li className="result-statistic-field">
                    <label>{ __('Time spend', 'learnpress') }</label>
                    <p>{results.time_spend}</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Point', 'learnpress') }</label>
                    <p>{ results.user_mark } / { results.mark }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Questions', 'learnpress') }</label>
                    <p>{ results.question_count }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Correct', 'learnpress') }</label>
                    <p>{ results.question_correct }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Wrong', 'learnpress') }</label>
                    <p>{ results.question_wrong }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Skipped', 'learnpress') }</label>
                    <p>{ results.question_empty }</p>
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
            results: get(getData('attempts'), '0')
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