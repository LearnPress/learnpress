import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';
import {default as ButtonCheck} from '../buttons/button-check';
import {default as ButtonHint} from '../buttons/button-hint';

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
        let {
            questionNav,
            currentPage,
            numPages,
            setCurrentPage
        } = this.props;

        switch (to) {
            case 'prev':
                currentPage = currentPage > 1 ? currentPage - 1 : (questionNav === 'infinity' ? numPages : 1);
                break;
            default:
                currentPage = currentPage < numPages ? currentPage + 1 : (questionNav === 'infinity' ? 1 : numPages);
        }

        setCurrentPage(currentPage);
    };

    /**
     * Check current question is in end of list.
     *
     * @return {boolean}
     */
    isLast = () => {
        const {
            currentPage,
            numPages
        } = this.props;

        return currentPage === numPages;
    };

    /**
     * Check current question is in begin of list.
     *
     * @return {boolean}
     */
    isFirst = () => {
        const {
            currentPage
        } = this.props;

        return currentPage === 1;
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

    /**
     * Set viewing mode for quiz e.q: reviewing
     *
     * @param mode
     */
    setQuizMode = (mode) => () => {
        const {
            setQuizMode
        } = this.props;

        setQuizMode(mode);
    };

    /**
     * Return TRUE if is reviewing mode
     *
     * @return {Component.props.isReviewing}
     */
    isReviewing = () => {
        const {
            isReviewing
        } = this.props;

        return isReviewing
    };

    /**
     * Render buttons
     *
     * @return {XML}
     */
    render() {
        const {
            status,
            questionNav,
            isReviewing,
            showReview,
            numPages,
            question,
            questionsLayout
        } = this.props;

        return <div className="quiz-buttons">
            <div className="button-left">

                {
                    -1 !== ['', 'completed'].indexOf(status) && !isReviewing &&
                    <button className="lp-button start"
                            onClick={ this.startQuiz }>{ __('Start', 'learnpress') }</button>
                }

                {
                    ('started' === status || isReviewing) && (numPages > 1) && (
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
                    ('started' === status /*|| isReviewing*/) && (
                        <React.Fragment>

                            {
                                questionsLayout === 1 && [
                                    <MaybeShowButton key="button-hint" type="hint" Button={ ButtonHint }
                                                     question={question}/>,
                                    <MaybeShowButton key="button-check" type="check" Button={ ButtonCheck }
                                                     question={question}/>
                                ]
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

/**
 * Helper function to check a button should be show or not.
 *
 * Buttons [hint, check]
 */
export const MaybeShowButton = compose(
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');
        return {
            status: getData('status'),
            showHint: getData('showHint'),
            showCheck: getData('showCheckAnswers'),
            checkedQuestions: getData('checkedQuestions'),
            hintedQuestions: getData('hintedQuestions'),
            questionsLayout: getData('questionsLayout')
        }
    })
)((props) => {
    const {
        showHint,
        showCheck,
        checkedQuestions,
        hintedQuestions,
        question,
        status,
        type,
        Button
    } = props;

    if (status !== 'started') {
        return false;
    }

    const theButton = <Button question={question}/>;

    switch (type) {
        case 'hint':

            if (!showHint) {
                return false;
            }

            if (!hintedQuestions) {
                return theButton;
            }

            if (!question.has_hint) {
                return false;
            }

            return hintedQuestions.indexOf(question.id) === -1 && theButton;

        case 'check':
            if (!showCheck) {
                return false;
            }

            if (!checkedQuestions) {
                return theButton;
            }

            return checkedQuestions.indexOf(question.id) === -1 && theButton;
    }
});

export default compose([
    withSelect((select) => {
        const {
            getData,
            getCurrentQuestion
        } = select('learnpress/quiz');

        const data = {
            id: getData('id'),
            status: getData('status'),
            questionIds: getData('questionIds'),
            questionNav: getData('questionNav'),
            isReviewing: getData('reviewQuestions') && getData('mode') === 'reviewing',
            showReview: getData('reviewQuestions'),
            showHint: getData('showHint'),
            showCheck: getData('showCheckAnswers'),
            checkedQuestions: getData('checkedQuestions'),
            hintedQuestions: getData('hintedQuestions'),
            numPages: getData('numPages'),
            currentPage: getData('currentPage'),
            questionsLayout: getData('questionsLayout')
        }

        if (data.questionsLayout === 1) {
            data.question = getCurrentQuestion('object');
        }

        return data;
    }),
    withDispatch((dispatch, {id}) => {
        const {
            startQuiz,
            setCurrentQuestion,
            submitQuiz,
            setQuizMode,
            showHint,
            checkAnswer,
            setCurrentPage
        } = dispatch('learnpress/quiz');

        return {
            startQuiz,
            setCurrentQuestion,
            setQuizMode,
            setCurrentPage,
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