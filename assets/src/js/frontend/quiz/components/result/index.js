import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, _x, sprintf} from '@wordpress/i18n';

const {get} = lodash;

class Result extends Component {
    constructor() {
        super(...arguments);
    }

    /**
     * Get result message.
     *
     * @param results
     * @return {*|string}
     */
    getResultMessage = (results) => {
        return sprintf(__('Your grade is <strong>%s</strong>', 'learnpress'), results.gradeText);
    };

    /**
     * Get result percentage.
     *
     * @param results
     * @return {string}
     */
    getResultPercentage = (results) => {
        return results.result === 100 ? results.result : parseFloat(results.result).toFixed(2);
    };

    /**
     * Render HTML elements.
     *
     * @return {XML}
     */
    render() {
        const {
            results
        } = this.props;

        const classNames = ['quiz-result', results.grade];

        return <div className={ classNames.join(' ') }>
            <h3 className="result-heading">{ __('Your Result', 'learnpress') }</h3>
            <div className="result-grade">
                <span className="result-achieved">{ this.getResultPercentage(results) }%</span>
                <span
                    className="result-require">{ undefined !== results.passingGrade ? results.passingGrade : _x('-', 'unknown passing grade value', 'learnpress') }</span>
                <p className="result-message" dangerouslySetInnerHTML={ {__html: this.getResultMessage(results)} }>
                </p>
            </div>

            <ul className="result-statistic">
                <li className="result-statistic-field">
                    <label>{ __('Time spend', 'learnpress') }</label>
                    <p>{results.timeSpend}</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Point', 'learnpress') }</label>
                    <p>{ results.userMark } / { results.mark }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Questions', 'learnpress') }</label>
                    <p>{ results.questionCount }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Correct', 'learnpress') }</label>
                    <p>{ results.questionCorrect }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Wrong', 'learnpress') }</label>
                    <p>{ results.questionWrong }</p>
                </li>
                <li className="result-statistic-field">
                    <label>{ __('Skipped', 'learnpress') }</label>
                    <p>{ results.questionEmpty }</p>
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
            results: getData('results')
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