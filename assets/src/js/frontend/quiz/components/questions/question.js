import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, sprintf} from '@wordpress/i18n';
import Buttons from "./buttons";
import {MaybeShowButton} from '../buttons';
import {default as ButtonHint} from '../buttons/button-hint';

const $ = window.jQuery;
const {uniqueId, isArray, isNumber, bind} = lodash;

class Question extends Component {

    constructor() {
        super(...arguments);
        this.state = {
            time: null,
            showHint: false
        }
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

        // Refresh render function to pass $wrap to child
        if (!this.state.time) {
            this.setState({
                time: new Date()
            })
        }

        return a;
    }

    setRef = (el) => {
        this.$wrap = $(el);
    };

    parseOptions = (options) => {
        if (options) {
            options = !isArray(options) ? JSON.parse(CryptoJS.AES.decrypt(options.data, options.key, {format: CryptoJSAesJson}).toString(CryptoJS.enc.Utf8)) : options;
            options = !isArray(options) ? JSON.parse(options) : options;
        }

        return options || [];
    };

    getWrapperClass = () => {
        const {
            question,
            answered
        } = this.props;

        const classes = ['question', 'question-' + question.type];
        const options = this.parseOptions(question.options);

        if (options.length && options[0].isTrue !== undefined) {
            classes.push('question-answered');
        }

        return classes;
    };

    getEditLink = () => {
        const {
            question,
            editPermalink
        } = this.props;

        return editPermalink ? editPermalink.replace(/[0-9]+/, question.id) : '';
    };

    editPermalink = (editPermalink) => {
        return sprintf('<a href="%s">%s</a>', editPermalink, __('Edit', 'learnpress'))
    };

    render() {
        const {
            question,
            isShow,
            isShowIndex,
            isShowHint,
            status
        } = this.props;

        const QuestionTypes = LP.questionTypes.default;
        const editPermalink = this.getEditLink();

        if (editPermalink) {
            jQuery('#wp-admin-bar-edit-lp_question').find('.ab-item').attr('href', editPermalink);
        }

        const titleParts = {
            'index': () => {
                return isShowIndex ? <span className="question-index">{isShowIndex}.</span> : ''
            },

            'title': () => {
                return question.title
            },

            'hint': () => {
                return <ButtonHint question={ question }></ButtonHint>
            },

            'edit-permalink': () => {
                return editPermalink && <span dangerouslySetInnerHTML={ {__html: this.editPermalink(editPermalink)} }
                                              className="edit-link">
                        </span>
            }
        };

        const blocks = {
            title: () => {
                return <h4 className="question-title">
                    {
                        LP.config.questionTitleParts().map((name) => {
                            return <React.Fragment key={ `title-part-${name}` }>{ titleParts[name] && titleParts[name]() }</React.Fragment>
                        })
                    }
                </h4>
            },

            content: () => {
                return <div className="question-content" dangerouslySetInnerHTML={ {__html: question.content} }>
                </div>
            },

            'answer-options': () => {
                return this.$wrap && <QuestionTypes {...{...this.props, $wrap: this.$wrap}}/>
            },

            explanation: () => {
                return question.explanation && <React.Fragment>
                        <div className="question-explanation-content">
                            <strong className="explanation-title">{ __('Explanation:', 'learnpress') }</strong>
                            <div dangerouslySetInnerHTML={ {__html: question.explanation} }>
                            </div>
                        </div>
                    </React.Fragment>
            },

            hint: () => {
                return question.hint && !question.explanation && question.showHint && <React.Fragment>
                        <div className="question-hint-content">
                            <strong className="hint-title">{ __('Hint:', 'learnpress') }</strong>
                            <div dangerouslySetInnerHTML={ {__html: question.hint} }>
                            </div>
                        </div>
                    </React.Fragment>
            },

            buttons: () => {
                return ('started' === status) /*&& (questionsPerPage > 1)*/ && <Buttons question={question}/>
            }
        };

        const configBlocks = LP.config.questionBlocks();

        return <React.Fragment>
            <div className={ this.getWrapperClass().join(' ') } style={ {display: isShow ? '' : 'none'} }
                 data-id={ question.id }
                 ref={ this.setRef }>

                {
                    configBlocks.map((name) => {
                        return <React.Fragment
                            key={ `block-${name}` }>{ blocks[name] ? blocks[name]() : '' }</React.Fragment>
                    })
                }

            </div>
        </React.Fragment>
    }
}

export default compose([
    withSelect((select, {question: {id}}) => {
        const {
            getData,
            getQuestionAnswered,
            //isCorrect,
        } = select('learnpress/quiz');

        return {
            status: getData('status'),
            questions: getData('question'),
            answered: getQuestionAnswered(id),
            questionsRendered: getData('questionsRendered'),
            editPermalink: getData('editPermalink'),
            //isCorrect: isCorrect(id),
            numPages: getData('numPages')
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