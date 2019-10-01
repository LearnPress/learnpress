import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

class Buttons extends Component {

    /**
     * Start or re-take quiz.
     *
     * @param event
     */
    startQuiz = (event) => {
        event.preventDefault();

        const {
            startQuiz
        } = this.props;

        startQuiz();
    };

    /**
     * Callback function for Prev/Next buttons to move question back or next.
     *
     * @param to
     */
    nav = (to) => (event) => {
        const {
            setCurrentQuestion,
            currentQuestion,
            questionIds,
            questionNav
        } = this.props;

        let currentAt = questionIds.indexOf(currentQuestion);

        switch (to) {
            case 'prev':
                currentAt = currentAt > 0 ? currentAt - 1 : (questionNav === 'infinity' ? questionIds.length - 1 : currentAt);
                break;
            default:
                currentAt = currentAt < questionIds.length - 1 ? currentAt + 1 : (questionNav === 'infinity' ? 0 : currentAt);
        }

        setCurrentQuestion(questionIds[currentAt]);
    };

    /**
     * Check current question is in end of list.
     *
     * @return {boolean}
     */
    isLast = () => {
        const {
            currentQuestion,
            questionIds,
        } = this.props;

        return questionIds.indexOf(currentQuestion) === questionIds.length - 1;
    };

    /**
     * Check current question is in begin of list.
     *
     * @return {boolean}
     */
    isFirst = () => {
        const {
            currentQuestion,
            questionIds,
        } = this.props;

        return questionIds.indexOf(currentQuestion) === 0;
    };

    /**
     * Submit question to record results.
     */
    submit = () => {
        const {
            submitQuiz
        } = this.props;

        submitQuiz();
    };

    setQuizMode = (mode) => () => {
        const {
            setQuizMode
        } = this.props;

        setQuizMode(mode);
    };

    isReviewing = () => {
        const {
            isReviewing
        } = this.props;

        return isReviewing
    };

    /**
     * Callback to show hint
     */
    showHint = () => {
        const {
            showHint,
            currentQuestion
        } = this.props;

        showHint(currentQuestion);
    };

    /**
     * Callback to check question answer
     */
    checkAnswer = () => {
        const {
            checkAnswer,
            currentQuestion
        } = this.props;

        checkAnswer(currentQuestion);
    };

    maybeShowButton = (type) => {
        const {
            showHint,
            showCheck,
            currentQuestion,
            checkedQuestions,
            hintedQuestions,
            question,
            status
        } = this.props;

        if (status !== 'started') {
            return false;
        }

        switch (type) {
            case 'hint':
                if (!showHint) {
                    return false;
                }

                if (!hintedQuestions) {
                    return true;
                }

                if (!question.has_hint) {
                    return false;
                }

                return hintedQuestions.indexOf(currentQuestion) === -1;

            case 'check':
                if (!showCheck) {
                    return false;
                }

                if (!checkedQuestions) {
                    return true;
                }

                // if (!question.has_check) {
                //     return false;
                // }

                return checkedQuestions.indexOf(currentQuestion) === -1;
        }
    };

    render() {
        const {
            status,
            questionNav,
            isReviewing,
            showReview
        } = this.props;

        return <div className="quiz-buttons">
            <div className="button-left">

                {
                    -1 !== ['', 'completed'].indexOf(status) && !isReviewing &&
                    <button className="lp-button start"
                            onClick={ this.startQuiz }>{ __('Start', 'learnpress') }</button>
                }

                {
                    ('started' === status || isReviewing) && (
                        <React.Fragment>
                            { ('infinity' === questionNav || !this.isFirst()) &&
                            <button className="lp-button nav prev"
                                    onClick={ this.nav('prev') }>{ __('Prev', 'learnpress') }</button>
                            }

                            {('infinity' === questionNav || !this.isLast()) &&
                            <button className="lp-button nav next"
                                    onClick={ this.nav('next') }>{ __('Next', 'learnpress') }</button>
                            }
                        </React.Fragment>
                    )
                }

            </div>
            <div className="button-right">
                {
                    ('started' === status || isReviewing) && (
                        <React.Fragment>
                            {
                                this.maybeShowButton('hint') && <button className="lp-button hint"
                                                                        onClick={ this.showHint }>{ __('Hint', 'learnpress') }</button>
                            }

                            {
                                this.maybeShowButton('check') && <button className="lp-button check"
                                                                         onClick={ this.checkAnswer }>{ __('Check', 'learnpress') }</button>
                            }

                            { (('infinity' === questionNav || this.isLast()) && !isReviewing) &&
                            <button className="lp-button submit-quiz"
                                    onClick={ this.submit }>{ __('Submit', 'learnpress') }</button>
                            }
                        </React.Fragment>
                    )
                }

                {
                    isReviewing && showReview && (
                        <button className="lp-button back-quiz"
                                onClick={ this.setQuizMode('') }>{ __('Result', 'learnpress') }</button>
                    )
                }

                {
                    ('completed' === status) && showReview && !isReviewing && (
                        <button className="lp-button review-quiz"
                                onClick={ this.setQuizMode('reviewing') }>{ __('Review', 'learnpress') }</button>
                    )
                }
            </div>
        </div>
    }
}

export default compose([
    withSelect((select, a, b) => {
        const {
            getData,
            getCurrentQuestion
        } = select('learnpress/quiz');
        return {
            id: getData('id'),
            status: getData('status'),
            questionIds: getData('questionIds'),
            questionNav: getData('questionNav'),
            currentQuestion: getData('currentQuestion'),
            isReviewing: getData('reviewQuestions') && getData('mode') === 'reviewing',
            showReview: getData('reviewQuestions'),
            showHint: getData('showHint'),
            showCheck: getData('showCheckAnswers'),
            checkedQuestions: getData('checkedQuestions'),
            hintedQuestions: getData('hintedQuestions'),
            question: getCurrentQuestion()
        }
    }),
    withDispatch((dispatch, {id}) => {
        const {
            startQuiz,
            setCurrentQuestion,
            submitQuiz,
            setQuizMode,
            showHint,
            checkAnswer
        } = dispatch('learnpress/quiz');

        return {
            startQuiz,
            setCurrentQuestion,
            setQuizMode,
            submitQuiz: function (id) {
                submitQuiz(id)
            },
            showHint: function (id) {
                showHint(id)
            },
            checkAnswer: function (id) {
                checkAnswer(id)
            }
        }
    })
])(Buttons);