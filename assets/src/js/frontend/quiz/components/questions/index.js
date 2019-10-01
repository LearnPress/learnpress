import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

import Question from './question';

class Questions extends Component {
    startQuiz = (event) => {
        event.preventDefault();

        const {
            startQuiz
        } = this.props;

        startQuiz();
    };

    render() {
        const {
            status,
            currentQuestion,
            questions,
            questionsRendered,
            isReviewing
        } = this.props;

        let viewMode = false, isShow = true;

        if (status === 'completed' && !isReviewing) {
            isShow = false;
        }


        return <React.Fragment>
            <div className="quiz-questions" style={ {display: isShow ? '' : 'none'} }>
                {
                    questions.map((question) => {
                        const isCurrent = currentQuestion === question.id;
                        const isRendered = questionsRendered && questionsRendered.indexOf(question.id) !== -1;

                        return ( isRendered || !isRendered && isCurrent ) ?
                            <Question isCurrent={ isCurrent } key={ `loop-question-${question.id}` }
                                      question={ question }/> : '';
                    })
                }
            </div>
        </React.Fragment>
    }
}

export default compose(
    withSelect((select, a, b) => {
        const {
            getData,
            getQuestions,
            getQuestionAnswered
        } = select('learnpress/quiz');
        return {
            status: getData('status'),
            currentQuestion: getData('currentQuestion'),
            questions: getQuestions(),
            questionsRendered: getData('questionsRendered'),
            isReviewing: getData('mode') === 'reviewing'
        }
    }),
    withDispatch((dispatch) => {
        const {
            startQuiz
        } = dispatch('learnpress/quiz');

        return {
            startQuiz
        }
    })
)(Questions);