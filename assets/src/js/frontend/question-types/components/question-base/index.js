import {Component} from '@wordpress/element';
import {withSelect} from '@wordpress/data';
import {compose} from '@wordpress/compose';

import {__} from '@wordpress/i18n';

const {isArray, get, set} = lodash;

class QuestionBase extends Component {
    constructor(props) {
        super(...arguments);

        const {question} = props;

        this.state = {
            optionClass: ['answer-option'],
            options: question ? this.parseOptions(question.options) : []
        };

        if (props.$wrap) {
            this.$wrap = props.$wrap;
        }
    }

    componentDidMount() {
        this.componentWillReceiveProps(this.props);
    }

    componentWillReceiveProps(nextProps) {
        if (nextProps.$wrap) {
            this.$wrap = nextProps.$wrap;
        }

        if (nextProps.question) {
            this.setState({
                options: this.parseOptions(nextProps.question.options)
            });
        }
    }

    maybeShowCorrectAnswer = () => {
        const {
            answered,
            status,
            isCheckedAnswer
        } = this.props;

        return (answered && (status === 'completed')) || isCheckedAnswer;
    };

    maybeDisabledOption = (option) => {
        const {
            answered,
            status,
            isCheckedAnswer
        } = this.props;

        return isCheckedAnswer || (status !== 'started');
    };

    setAnswerChecked = () => (event) => {
        const {
            updateUserQuestionAnswers,
            question,
            status
        } = this.props;

        if (status !== 'started') {
            return 'can not set answers'
        }

        const $options = this.$wrap.find('.option-check');
        const answered = [];
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

    maybeCheckedAnswer = (value) => {
        const {
            answered
        } = this.props;

        if (isArray(answered)) {
            return !!answered.find((a) => {
                return a == value;
            })
        }

        return value == answered;
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

    isDefaultType = () => {
        return this.props.supportOptions;
    };

    getWarningMessage = () => {
        return <React.Fragment>
            {__('Render function should be overwritten from base.', 'learnpress')}
        </React.Fragment>
    };

    getOptionClass = (option) => {
        const {
            answered
        } = this.props;

        const classes = ['answer-option'];

        // if (answered) {
        //     if (option.is_true === 'yes') {
        //         classes.push('answer-correct');
        //         answered.indexOf(option.value) !== -1 && classes.option[option.question_answer_id].push('answered-correct');
        //     } else {
        //         answered.indexOf(option.value) !== -1 && classes.option[option.question_answer_id].push('answered-wrong');
        //     }
        // }

        return classes;
    };

    parseOptions = (options) => {
        if (options) {
            options = !isArray(options) ? JSON.parse(CryptoJS.AES.decrypt(options.data, options.key, {format: CryptoJSAesJson}).toString(CryptoJS.enc.Utf8)) : options;
            options = !isArray(options) ? JSON.parse(options) : options;
        }

        return options || [];
    };

    getOptions = () => {
        return this.state.options || [];
    };

    isCorrect = () => {
        const {
            answered
        } = this.props;

        if (answered === undefined || answered === '') {
            return false;
        }

        let i, option, options;

        for (i = 0, options = this.getOptions(); i < options.length; i++) {
            option = options[i];

            if (option.is_true === 'yes') {
                if (answered == option.value) {
                    return true;
                }
            }
        }

        return false;
    };

    getChecker = () => {
        //const checker = LP['questionChecker'][]
    }

    render() {
        const {
            question,
            status
        } = this.props;

        const checker = LP['config']['isQuestionCorrect'][question.type] || this.isCorrect;
        const isCorrect = checker.call(this);

        return <div>
            {this.isDefaultType() &&
            <ul id={`answer-options-${question.id}`} className="answer-options">
                {
                    this.getOptions().map((option) => {
                        const ID = `learn-press-answer-option-${option.uid}`;

                        return <li className={ this.getOptionClass(option).join(' ') }
                                   key={ `answer-option-${option.uid}` }>
                            <input type={ this.getOptionType(question.type, option) }
                                   className="option-check"
                                   name={ status === 'started' ? `learn-press-question-${question.id}` : '' }
                                   id={ ID }
                                   onChange={ this.setAnswerChecked() }
                                   disabled={ this.maybeDisabledOption(option) }
                                   checked={ this.maybeCheckedAnswer(option.value) }
                                   value={ status === 'started' ? option.value : '' }/>
                            <label htmlFor={ ID } className="option-title"
                                   dangerouslySetInnerHTML={ {__html: option.text || option.value} }>
                            </label>
                        </li>
                        // return <li className={ this.getOptionClass(option).join(' ') }
                        //            key={ `answer-option-${option.question_answer_id}` }>
                        //     <label>
                        //         <input type={ this.getOptionType(question.type, option) }
                        //                className="option-check"
                        //                name={ `learn-press-question-${question.id}` }
                        //                id={ ID }
                        //                onChange={ this.setAnswerChecked() }
                        //                disabled={ this.maybeDisabledOption(option) }
                        //                checked={ this.maybeCheckedAnswer(option.value) }
                        //                value={ option.value }/>
                        //
                        //         <div className="option-title">
                        //             <div className="option-title-content"
                        //                  htmlFor={ ID }
                        //                  dangerouslySetInnerHTML={ {__html: option.text} }>
                        //             </div>
                        //         </div>
                        //     </label>
                        // </li>
                    })
                }
            </ul>
            }

            { !this.isDefaultType() && this.getWarningMessage()}

            { status === 'completed' &&
            <div className={ `question-response` + (isCorrect ? ' correct' : ' incorrect') }>
                <span>{ isCorrect ? __('Correct', 'learnpress') : __('Incorrect', 'learnpress')}</span>
            </div>
            }
        </div>
    }
}
export default QuestionBase;