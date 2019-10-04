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
        if (!attempt.expiration_time) {
            return __('Unlimited', 'learnpress');
        }

        const {formatDuration} = LP.singleCourse;
        const milliseconds = new Date(attempt.expiration_time).getTime() - new Date(attempt.start_time).getTime();

        return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }

    getTimeSpendLabel(attempt){
        const {formatDuration} = LP.singleCourse;
        const milliseconds = new Date(attempt.end_time).getTime() - new Date(attempt.start_time).getTime();
        return milliseconds ? formatDuration(milliseconds / 1000) : '';
    }

    render() {
        const {
            attempts,
            attemptsCount
        } = this.props;

        const hasAttempts = attempts && !!attempts.length;

        return <React.Fragment>
            <div className="quiz-attempts">
            <h4 className="attempts-heading">{ __('Attempts', 'learnpress') } ( {attempts.length || 0} / {attemptsCount} )</h4>
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
                                <td>{row.start_time}</td>
                                <td>{row.question_correct} / {row.question_count}</td>
                                <td>{ this.getTimeSpendLabel(row) } / {this.getDurationLabel(row)}</td>
                                <td>{row.user_mark} / {row.mark}</td>
                                <td>{row.passing_grade || _x('-', 'unknown passing grade value', 'learnpress')}</td>
                                <td>{parseFloat(row.result).toFixed(2)}% <label>{row.grade_text}</label></td>
                            </tr>
                        })
                    }
                    </tbody>
                </table>
            }

            {
                !hasAttempts &&
                <p className="no-attempts-message">{ __('There is no attempt now.', 'learnpress') }</p>
            }
            </div>
        </React.Fragment>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

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