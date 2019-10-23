import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, _x} from '@wordpress/i18n';

const {uniqueId} = lodash;

/**
 * Displays list of all attempt from a quiz.
 */
class Attempts extends Component {
    getDurationLabel(attempt) {
        if (!attempt.expirationTime) {
            return __('Unlimited', 'learnpress');
        }

        const {formatDuration} = LP.singleCourse;
        const milliseconds = new Date(attempt.expirationTime).getTime() - new Date(attempt.startTime).getTime();

        return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }

    getTimeSpendLabel(attempt){
        const {formatDuration} = LP.singleCourse;
        const milliseconds = new Date(attempt.endTime).getTime() - new Date(attempt.startTime).getTime();
        return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }

    render() {
        const {
            attempts
        } = this.props;

        const hasAttempts = attempts && !!attempts.length;

        return !hasAttempts ? false : <React.Fragment>
            <div className="quiz-attempts">
            <h4 className="attempts-heading">{ __('Last Attempted', 'learnpress') }</h4>
            {
                hasAttempts &&
                <table>
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
                            return <tr key={ `attempt-${row.id}` }>
                                <td>{row.startTime}</td>
                                <td>{row.questionCorrect} / {row.questionCount}</td>
                                <td>{ this.getTimeSpendLabel(row) } / {this.getDurationLabel(row)}</td>
                                <td>{row.userMark} / {row.mark}</td>
                                <td>{row.passingGrade || _x('-', 'unknown passing grade value', 'learnpress')}</td>
                                <td>{parseFloat(row.result).toFixed(2)}% <label>{row.gradeText}</label></td>
                            </tr>
                        })
                    }
                    </tbody>
                </table>
            }

            {/*{*/}
                {/*!hasAttempts &&*/}
                {/*<p className="no-attempts-message">{ __('There is no attempt now.', 'learnpress') }</p>*/}
            {/*}*/}
            </div>
        </React.Fragment>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');
        const lastAttempted = getData('attempts[0]');

        return {
            id: getData('id'),
            attempts: lastAttempted ? [lastAttempted] : [],
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