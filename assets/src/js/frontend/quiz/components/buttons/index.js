import {Component} from '@wordpress/element';
import {withSelect, withDispatch, select} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, _x} from '@wordpress/i18n';
import {default as ButtonCheck} from '../buttons/button-check';
import {default as ButtonHint} from '../buttons/button-hint';

class Buttons extends Component {

    /**
     * Start or re-take quiz.
     *
     * @param event
     */
    startQuiz = (event) => {
        event && event.preventDefault();

        const {
            startQuiz,
            status
        } = this.props;

        if (status === 'completed') {
            const {confirm} = select('learnpress/modal');

            if ('no' === confirm('Are you sure you want to retry quiz?', this.startQuiz)) {
                return;
            }
        }

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
     * Move to a specific page
     *
     * @param pageNum
     */
    moveTo = (pageNum) => (event) => {
        event.preventDefault();

        const {
            numPages,
            setCurrentPage
        } = this.props;

        if (pageNum < 1 || pageNum > numPages) {
            return;
        }

        setCurrentPage(pageNum);
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
            questionsPerPage,
            pageNumbers,
            pages,
            currentPage
        } = this.props;

        const classNames = ['quiz-buttons align-center'];

        if (questionNav === 'questionNav') {
            classNames.push('infinity');
        }

        if (this.isFirst()) {
            classNames.push('is-first');
        }

        if (this.isLast()) {
            classNames.push('is-last');
        }

        return <div className={ classNames.join(' ') }>
            <div className="button-left">

                {
                    -1 !== ['', 'completed'].indexOf(status) && !isReviewing &&
                    <button className="lp-button start"
                            onClick={ this.startQuiz }>{ status === 'completed' ? _x('Retry', 'label button retry quiz', 'learnpress') : _x('Start', 'label button start quiz', 'learnpress') }</button>
                }

                {
                    ('started' === status || isReviewing) && (numPages > 1) && (
                        <React.Fragment>
                            {
                                pageNumbers ? <React.Fragment>
                                    <div className="questions-pagination">
                                        <div className="page-numbers">
                                            {pages.map((ids, pageNum) => {
                                                return pageNum + 1 === currentPage ?
                                                    <span key={`page-number-${pageNum}`}>{pageNum + 1}</span> :
                                                    <a key={`page-number-${pageNum}`}
                                                       onClick={ this.moveTo(pageNum + 1) }>{pageNum + 1}</a>
                                            })}
                                        </div>
                                    </div>
                                </React.Fragment> : <React.Fragment>
                                    <button className="lp-button nav prev" data-type="question-nav"
                                            onClick={ this.nav('prev') }>{__('', 'learnpress') }</button>
                                    <button className="lp-button nav next" data-type="question-nav"
                                            onClick={ this.nav('next') }>{ __('', 'learnpress') }</button>
                                </React.Fragment>
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
                                questionsPerPage === 1 && [
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
            showCheck: getData('instantCheck'),
            checkedQuestions: getData('checkedQuestions'),
            hintedQuestions: getData('hintedQuestions'),
            questionsPerPage: getData('questionsPerPage')
        }
    })
)((props) => {
    const {
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

            if (!hintedQuestions) {
                return theButton;
            }

            if (!question.hasHint) {
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
            showCheck: getData('instantCheck'),
            checkedQuestions: getData('checkedQuestions'),
            hintedQuestions: getData('hintedQuestions'),
            numPages: getData('numPages'),
            pages: getData('pages'),
            currentPage: getData('currentPage'),
            questionsPerPage: getData('questionsPerPage'),
            pageNumbers: getData('pageNumbers'),
        };

        if (data.questionsPerPage === 1) {
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