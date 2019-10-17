import {Component} from '@wordpress/element';
import {withDispatch, withSelect} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {_x} from '@wordpress/i18n';

class ButtonCheck extends Component {
    /**
     * Callback to check question answer
     */
    checkAnswer = () => {
        const {
            checkAnswer,
            question
        } = this.props;

        checkAnswer(question.id);
    };

    render() {
        const {
            answered
        } = this.props;

        return <button className="lp-button instant-check"
                       onClick={ this.checkAnswer } disabled={ !answered }>{ _x('Check answer', 'label of button check answer', 'learnpress') }</button>
    }
}

export default compose(
    withSelect((select,{question: {id}})=>{
        const {
            getQuestionAnswered
        } = select('learnpress/quiz');

        return {
            answered: getQuestionAnswered(id),
        }
    }),
    withDispatch((dispatch, {id}) => {
        const {
            checkAnswer,
        } = dispatch('learnpress/quiz');

        return {
            checkAnswer: function (id) {
                checkAnswer(id)
            }
        }
    })
)(ButtonCheck);