import {Component} from '@wordpress/element';
import {withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

class ButtonHint extends Component {
    /**
     * Callback to show hint of question
     */
    showHint = () => {
        const {
            showHint,
            question
        } = this.props;

        showHint(question.id);
    };

    render() {
        return <button className="btn-show-hint"
                       onClick={ this.showHint }>
        </button>
    }
}

export default compose(
    withDispatch((dispatch, {id}) => {
        const {
            showHint,
        } = dispatch('learnpress/quiz');

        return {
            showHint: function (id) {
                showHint(id)
            }
        }
    })
)(ButtonHint);