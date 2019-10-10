import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, sprintf} from '@wordpress/i18n';
import Buttons from "./buttons";

const $ = window.jQuery;
const {uniqueId, isArray, isNumber} = lodash;

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
        if (options.length && options[0].is_true !== undefined) {
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
            questionsLayout,
            status
        } = this.props;

        const QuestionTypes = LP.questionTypes.default;
        const editPermalink = this.getEditLink();

        if (editPermalink) {
            jQuery('#wp-admin-bar-edit-lp_question').find('.ab-item').attr('href', editPermalink);
        }

        return <React.Fragment>
            <div className={ this.getWrapperClass().join(' ') } style={ {display: isShow ? '' : 'none'} }
                 ref={ this.setRef }>
                <h4 className="question-title">
                    { isShowIndex ? <span className="question-index">{isShowIndex}.</span> : ''}
                    { question.title }
                    {
                        editPermalink && <span dangerouslySetInnerHTML={ {__html: this.editPermalink(editPermalink)} }
                                               className="edit-link">
                        </span>
                    }
                </h4>

                <div className="question-content" dangerouslySetInnerHTML={ {__html: question.content} }>
                </div>

                <QuestionTypes {...{...this.props, $wrap: this.$wrap}}/>

                {
                    question.explanation && <React.Fragment>
                        <div className="question-explanation-content">
                            <strong className="explanation-title">{ __('Explanation:', 'learnpress') }</strong>
                            <div dangerouslySetInnerHTML={ {__html: question.explanation} }>
                            </div>
                        </div>
                    </React.Fragment>
                }

                {
                    question.hint && <React.Fragment>
                        <div className="question-hint-content">
                            <strong className="hint-title">{ __('Hint:', 'learnpress') }</strong>
                            <div dangerouslySetInnerHTML={ {__html: question.hint} }>
                            </div>
                        </div>
                    </React.Fragment>

                }
                { ('started' === status) && (questionsLayout > 1) && <Buttons question={question}/> }

            </div>
        </React.Fragment>
    }
}

export default compose([
    withSelect((select, {question: {id}}) => {
        const {
            getData,
            getQuestionAnswered,
            isCorrect,
        } = select('learnpress/quiz');

        return {
            status: getData('status'),
            questions: getData('question'),
            answered: getQuestionAnswered(id),
            questionsRendered: getData('questionsRendered'),
            editPermalink: getData('editPermalink'),
            isCorrect: isCorrect(id),
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