import {Component} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withDispatch, withSelect} from '@wordpress/data';
import {__, sprintf} from '@wordpress/i18n';

export * from './components';

class QuestionTypes extends Component {
    getQuestion = () => {
        const {
            question
        } = this.props;

        let types = LP.Hook.applyFilters('question-types', {
            single_choice: LP.questionTypes.SingleChoice,
            multi_choice: LP.questionTypes.MultipleChoices,
            true_or_false: LP.questionTypes.TrueOrFalse,
            fill_in_blanks: LP.questionTypes.FillInBlanks
        });

        let questionComponent = types[question.type];

        return questionComponent
    }

    render() {
        const {
            question,
            supportOptions
        } = this.props;

        const childProps = {...this.props};
        childProps.supportOptions = supportOptions.indexOf(question.type) !== -1;

        const TheQuestion = this.getQuestion() || function () {
                return <div className="question-types"
                    dangerouslySetInnerHTML={ {__html: sprintf(__('Question <code>%s</code> invalid!', 'learnpress'), question.type)} }>
                </div>
            };

        return <React.Fragment>
            <TheQuestion {...childProps}/>
        </React.Fragment>
    }
}

export default compose(
    withSelect((select, {question: {id}}) => {
        const {
            getData,
            isCheckedAnswer
        } = select('learnpress/quiz');

        return {
            supportOptions: getData('supportOptions'),
            isCheckedAnswer: isCheckedAnswer(id)
        }
    }),
    withDispatch(() => {
        return {}
    })
)(QuestionTypes);