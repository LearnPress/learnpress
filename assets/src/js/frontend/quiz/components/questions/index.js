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
            questions
        } = this.props;

        let viewMode = false, isShow = true;

        if (status === 'completed' && viewMode !== 'review') {
            isShow = false;
        }


        return <div className="quiz-questions" style={ {display: isShow ? '' : 'none'} }>
            {
                questions.map((question) => {
                    const isCurrent = currentQuestion === question.id;
                    return <Question isCurrent={ isCurrent } key={ `loop-question-${question.id}` }
                                     question={ question }/>
                })
            }
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData,
            getQuestions
        } = select('learnpress/quiz');

        return {
            status: getData('status'),
            currentQuestion: getData('currentQuestion'),
            questions: getQuestions()
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
])(Questions);