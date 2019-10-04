import {Component} from '@wordpress/element';
import {withDispatch} from '@wordpress/data';
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
        return <button className="lp-button check"
                       onClick={ this.checkAnswer }>{ _x('Check answer', 'label of button check answer', 'learnpress') }</button>
    }
}

export default compose(
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