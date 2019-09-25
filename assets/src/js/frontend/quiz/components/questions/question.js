import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';

const $ = window.jQuery;
const {uniqueId} = lodash;

class Question extends Component {

    constructor() {
        super(...arguments);

        this.$wrap = null;
    }


    componentDidMount(a) {
        const {
            question,
            isCurrent,
            markQuestionRendered
        } = this.props;

        if (isCurrent) {
            markQuestionRendered(question.id)
        }
        return a;
    }

    setAnswerChecked = () => (event) => {
        const $options = this.$wrap.find('.option-check');
        const answered = [];
        const {
            updateUserQuestionAnswers,
            question
        } = this.props;
        const isSingle = question.type !== 'multi_choice';

        $options.each((i, option) => {
            if (option.checked) {
                answered.push(option.value);

                if (isSingle) {
                    return false;
                }
            }
        });

        updateUserQuestionAnswers(question.id, isSingle ? answered[0] : answered)

    };

    getOptionType = (questionType, option) => {
        let type = 'radio';

        switch (questionType) {
            case 'multi_choice':
                type = 'checkbox';
                break;
        }

        return type;
    };

    setRef = (el) => {
        this.$wrap = $(el);
    }

    render() {
        const {
            status,
            question,
            isCurrent,
            markQuestionRendered,
            questionsRendered
        } = this.props;

        return <div className="question" style={ {display: isCurrent ? '' : 'none'} } ref={ this.setRef }>
            <h4>{ question.title }</h4>
            <div dangerouslySetInnerHTML={ {__html: question.content} }>
            </div>
            [{JSON.stringify(question.answered)}]
            <ul id={`answer-options-${question.id}`} className="answer-options">
                {
                    question.options.map((option) => {
                        const optionId = uniqueId();

                        return <li className={`answer-option`} key={ `answer-option-${option.question_answer_id}` }>
                            <label>
                                <input type={ this.getOptionType(question.type, option) }
                                       className="option-check"
                                       name={ `learn-press-question-${question.id}` }
                                       id={`learn-press-answer-option-${optionId}`}
                                       onChange={ this.setAnswerChecked() }
                                       value={ option.value }/>

                                <div className="option-title">
                                    <div className="option-title-content"
                                         htmlFor={`learn-press-answer-option-${optionId}`}
                                         dangerouslySetInnerHTML={ {__html: option.text} }>
                                    </div>
                                </div>
                            </label>
                        </li>
                    })
                }
            </ul>
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

        return {
            status: getData('status'),
            questions: getData('question'),
            answered: getData('answered'),
            questionsRendered: getData('questionsRendered')
        }
    }),
    withDispatch((dispatch) => {
        const {
            updateUserQuestionAnswers,
            markQuestionRendered
        } = dispatch('learnpress/quiz');

        return {
            markQuestionRendered,
            updateUserQuestionAnswers
        }
    })
])(Question);