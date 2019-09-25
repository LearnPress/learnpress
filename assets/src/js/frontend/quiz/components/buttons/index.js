import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

class Buttons extends Component {
    startQuiz = (event) => {
        event.preventDefault();

        const {
            startQuiz
        } = this.props;

        startQuiz();
    };

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

    isLast = () => {
        const {
            currentQuestion,
            questionIds,
        } = this.props;

        return questionIds.indexOf(currentQuestion) === questionIds.length - 1;
    };

    isFirst = () => {
        const {
            currentQuestion,
            questionIds,
        } = this.props;

        return questionIds.indexOf(currentQuestion) === 0;
    };

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

    render() {
        const {
            status,
            questionNav,
            isReviewing
        } = this.props;

        return <div className="quiz-buttons">
            {
                -1 !== ['', 'completed'].indexOf(status) && !isReviewing &&
                <button className="lp-button start" onClick={ this.startQuiz }>{ __('Start', 'learnpress') }</button>
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

                        { (('infinity' === questionNav || this.isLast()) && !isReviewing) &&
                        <button className="lp-button submit-quiz"
                                onClick={ this.submit }>{ __('Submit', 'learnpress') }</button>
                        }
                    </React.Fragment>
                )
            }

            {
                isReviewing && (
                    <button className="lp-button back-quiz"
                            onClick={ this.setQuizMode('') }>{ __('Back', 'learnpress') }</button>
                )
            }

            {
                ('completed' === status) && !isReviewing && (
                    <button className="lp-button review-quiz"
                            onClick={ this.setQuizMode('reviewing') }>{ __('Review', 'learnpress') }</button>
                )
            }

        </div>
    }
}

export default compose([
    withSelect((select, a, b) => {
        const {
            getData
        } = select('learnpress/quiz');
        return {
            id: getData('id'),
            status: getData('status'),
            questionIds: getData('questionIds'),
            questionNav: getData('questionNav'),
            currentQuestion: getData('currentQuestion'),
            isReviewing: getData('mode') === 'reviewing'
        }
    }),
    withDispatch((dispatch, {id}) => {
        const {
            startQuiz,
            setCurrentQuestion,
            submitQuiz,
            setQuizMode
        } = dispatch('learnpress/quiz');

        return {
            startQuiz,
            setCurrentQuestion,
            setQuizMode,
            submitQuiz: function () {
                submitQuiz(id)
            }
        }
    })
])(Buttons);