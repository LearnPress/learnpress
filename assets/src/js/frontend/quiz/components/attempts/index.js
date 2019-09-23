import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

const {uniqueId} = lodash;

class Attempts extends Component {

    render() {
        const {
            status,
            attempts,
            questionNav,
            attemptsCount
        } = this.props;

        const hasAttempts = attempts && !!attempts.length;

        return <React.Fragment>
            <h4>{ __('Attempts', 'learnpress') } ( {attempts.length || 0} / {attemptsCount} )</h4>
            {
                hasAttempts &&
                <table className="quiz-attempts">
                    <thead>
                    <tr>
                        <th>{ __('Date', 'learnpress') }</th>
                        <th>{ __('Questions', 'learnpress') }</th>
                        <th>{ __('Spend', 'learnpress') }</th>
                        <th>{ __('Marks', 'learnpress') }</th>
                        <th>{ __('Passing Grade', 'learnpress') }</th>
                        <th>{ __('Result', 'learnpress') }</th>
                    </tr>
                    </thead>
                    <tbody>
                    {
                        attempts.map((row) => {
                            const rowId = 'attempts-' + uniqueId();
                            return <tr key={ rowId }>
                                <td>{row.time}</td>
                                <td>{row.questions}</td>
                                <td>{row.spendTime[0]} / {row.spendTime[1]}</td>
                                <td>{row.marks[0]} / {row.marks[1]}</td>
                                <td>{row.passingGrade}</td>
                                <td>{row.result}</td>
                            </tr>
                        })
                    }
                    </tbody>
                </table>
            }

            {
                !hasAttempts &&
                <p>{ __('There is no attempt now.', 'learnpress') }</p>
            }
        </React.Fragment>
    }
}

export default compose([
    withSelect((select, a, b) => {
        const {
            getData
        } = select('learnpress/quiz');
        console.log(a, b)
        return {
            id: getData('id'),
            attempts: getData('attempts'),
            attemptsCount: getData('attemptsCount'),
            status: getData('status'),
            questionIds: getData('questionIds'),
            questionNav: getData('questionNav'),
            currentQuestion: getData('currentQuestion')
        }
    }),
    withDispatch((dispatch, {id}) => {
        const {
            startQuiz,
            setCurrentQuestion,
            submitQuiz
        } = dispatch('learnpress/quiz');

        return {
            startQuiz,
            setCurrentQuestion,
            submitQuiz: function () {
                submitQuiz(id)
            }
        }
    })
])(Attempts);