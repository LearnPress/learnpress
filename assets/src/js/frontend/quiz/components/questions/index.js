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
        this.state = {
            isReviewing: null,
            currentPage: 0,
            self: this
        };
    }

    static getDerivedStateFromProps(props, state) {
        const checkProps = ['isReviewing', 'currentPage'];
        const changedProps = {};

        for (let i = 0; i < checkProps.length; i++) {
            if (props[checkProps[i]] !== state[checkProps[i]]) {
                changedProps[checkProps[i]] = props[checkProps[i]];
            }
        }

        // If has prop changed then update state and re-render UI
        if (Object.values(changedProps).length) {
            state.self.needToTop = true;
            return changedProps;
        }

        // No state update necessary
        return null;
    }

    // componentWillReceiveProps(nextProps){
    //     const checkProps = ['isReviewing', 'currentPage'];
    //
    //     for(let i = 0; i < checkProps.length; i++){
    //         if(this.props[checkProps[i]] !== nextProps[checkProps[i]]){
    //             this.needToTop = true;
    //             return;
    //         }
    //     }
    //
    // }

    // componentWillUpdate() {
    //     this.needToTop = this.state.needToTop;
    //     this.setState({needToTop: false});
    // }

    componentDidUpdate() {
        if (this.needToTop) {
            jQuery('#popup-content')
                .animate({scrollTop: 0})
                .find('.content-item-scrollable:last')
                .animate({scrollTop: 0});
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
            questionsPerPage,
        } = this.props;
        return currentPage === Math.ceil(index / questionsPerPage);
    };

    nav = (event) => {
        const {
            sendKey
        } = this.props;
        console.log(event.keyCode)

        switch (event.keyCode) {
            case 37: // left
                return sendKey('left');
            case 38: // up
                return;
            case 39: // right
                return sendKey('right');
            case 40: // down
                return;
            default:
                // 1 ... 9
                if (event.keyCode >= 49 && event.keyCode <= 57) {
                    sendKey(event.keyCode - 48);
                }
        }

    }

    render() {
        const {
            status,
            currentQuestion,
            questions,
            questionsRendered,
            isReviewing,
            questionsPerPage
        } = this.props;

        let viewMode = false, isShow = true;

        //if (!showAllQuestions) {
        if (status === 'completed' && !isReviewing) {
            isShow = false;
        }
        //}

        return <React.Fragment>
            <div tabIndex={100} onKeyUp={ this.nav }>
                <div className="quiz-questions" style={ {display: isShow ? '' : 'none'} }>
                    {
                        questions.map((question, index) => {
                            const isCurrent = questionsPerPage ? false : currentQuestion === question.id;
                            const isRendered = questionsRendered && questionsRendered.indexOf(question.id) !== -1;
                            const isVisible = this.isInVisibleRange(question.id, index + 1);
                            return ( isRendered || !isRendered /*&& isCurrent*/ ) || isVisible ?
                                <Question isCurrent={ isCurrent } key={ `loop-question-${question.id}` }
                                          isShow={isVisible }
                                          isShowIndex={questionsPerPage ? index + 1 : false}
                                          questionsPerPage={questionsPerPage}
                                          question={ question }/> : '';
                        })
                    }
                </div>
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
            questionsPerPage: getData('questionsPerPage') || 1
        }
    }),
    withDispatch((dispatch) => {
        const {
            startQuiz,
            sendKey
        } = dispatch('learnpress/quiz');

        return {
            startQuiz,
            sendKey
        }
    })
)(Questions);