import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

import Question from './question';

const {isNumber, chunk} = lodash;

class Questions extends Component {
    constructor(props) {
        super(...arguments);

        this.needToTop = false;
    }

    componentWillReceiveProps(nextProps){
        const checkProps = ['isReviewing', 'currentPage'];

        for(let i = 0; i < checkProps.length; i++){
            if(this.props[checkProps[i]] !== nextProps[checkProps[i]]){
                this.needToTop = true;
                return;
            }
        }

    }

    componentDidUpdate(){
        if(this.needToTop){
            jQuery('#popup-content').animate({scrollTop: 0});
            this.needToTop = false;
        }
    }

    startQuiz = (event) => {
        event.preventDefault();

        const {
            startQuiz
        } = this.props;

        startQuiz();
    };

    isInVisibleRange = (id, index) => {
        const {
            currentPage,
            questionsLayout,
        } = this.props;
        return currentPage === Math.ceil(index / questionsLayout);
    };

    render() {
        const {
            status,
            currentQuestion,
            questions,
            questionsRendered,
            isReviewing,
            questionsLayout
        } = this.props;

        let viewMode = false, isShow = true;

        //if (!showAllQuestions) {
        if (status === 'completed' && !isReviewing) {
            isShow = false;
        }
        //}

        return <React.Fragment>
            <div className="quiz-questions" style={ {display: isShow ? '' : 'none'} }>
                {
                    questions.map((question, index) => {
                        const isCurrent = questionsLayout ? false : currentQuestion === question.id;
                        const isRendered = questionsRendered && questionsRendered.indexOf(question.id) !== -1;
                        const isVisible = this.isInVisibleRange(question.id, index + 1);
                        return ( isRendered || !isRendered /*&& isCurrent*/ ) || isVisible ?
                            <Question isCurrent={ isCurrent } key={ `loop-question-${question.id}` }
                                      isShow={isVisible }
                                      isShowIndex={questionsLayout ? index + 1 : false}
                                      questionsLayout={questionsLayout}
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
        } = select('learnpress/quiz');
        return {
            status: getData('status'),
            currentQuestion: getData('currentQuestion'),
            questions: getQuestions(),
            questionsRendered: getData('questionsRendered'),
            isReviewing: getData('mode') === 'reviewing',
            numPages: getData('numPages'),
            currentPage: getData('currentPage'),
            questionsLayout: getData('questionsLayout') || 1
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