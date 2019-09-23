import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__} from '@wordpress/i18n';
const {uniqueId} = lodash;

class Question extends Component {
    getOptionType = (questionType, option) => {
        let type = 'radio';

        switch (questionType) {
            case 'multi_choice':
                type = 'checkbox';
                break;
        }

        return type;
    };

    render() {
        const {
            status,
            question,
            isCurrent
        } = this.props;

        return <div className="question" style={ {display: isCurrent ? '' : 'none'} }>
            <h4>{ question.title }</h4>
            <div dangerouslySetInnerHTML={ {__html: question.content} }>
            </div>
            <ul id={`answer-options-${question.id}`} className="answer-options">
                {
                    question.options.map((option) => {
                        const optionId = uniqueId();

                        return <li className={`answer-option`} key={ `answer-option-${option.question_answer_id}` }>
                            <input type={ this.getOptionType(question.type, option) }
                                   className="option-check"
                                   name={ `learn-press-question-${question.id}` }
                                   id={`learn-press-answer-option-${optionId}`}
                                   value={ option.value }/>

                            <div className="option-title">
                                <label className="option-title-content"
                                       htmlFor={`learn-press-answer-option-${optionId}`}
                                       dangerouslySetInnerHTML={ {__html: option.text} }>
                                </label>
                            </div>
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
            questions: getData('question')
        }
    }),
    withDispatch((dispatch) => {
        const {
            startQuiz
        } = dispatch('learnpress/quiz');

        return {
            startQuiz
        }
    })
])(Question);