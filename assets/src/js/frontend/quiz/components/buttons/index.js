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

    render() {
        const {
            status,
            questionNav
        } = this.props;

        return <div className="quiz-buttons">
            {
                -1 !== ['', 'completed'].indexOf(status) &&
                <button className="lp-button start" onClick={ this.startQuiz }>{ __('Start', 'learnpress') }</button>
            }

            {
                'started' === status && (
                    <React.Fragment>
                        { ('infinity' === questionNav || !this.isFirst()) &&
                        <button className="lp-button nav prev" onClick={ this.nav('prev') }>{ __('Prev', 'learnpress') }</button>
                        }

                        {('infinity' === questionNav || !this.isLast()) &&
                        <button className="lp-button nav next" onClick={ this.nav('next') }>{ __('Next', 'learnpress') }</button>
                        }

                        { ('infinity' === questionNav || this.isLast()) &&
                        <button className="lp-button submit" onClick={ this.submit }>{ __('Submit', 'learnpress') }</button>
                        }
                    </React.Fragment>
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
])(Buttons);