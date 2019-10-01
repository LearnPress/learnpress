import {Component} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';

class Meta extends Component {
    render() {
        const {
            metaFields
        } = this.props;

        return metaFields && <React.Fragment>
                <ul className="quiz-intro">
                    {
                        Object.values(metaFields).map((field, i) => {
                            return <li key={`quiz-intro-field-${i}`}>
                                <label dangerouslySetInnerHTML={{__html: field.label}}>
                                </label>
                                <span dangerouslySetInnerHTML={{__html: field.value}}>
                            </span>
                            </li>
                        })
                    }
                </ul>
            </React.Fragment>
    }
}

export default compose(
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

        return {
            metaFields: LP.Hook.applyFilters('quiz-meta-fields', {
                attemptsCount: {
                    label: __('Attempts allowed', 'learnpress'),
                    content: getData('attemptsCount')
                },
                duration: {
                    label: __('Duration', 'learnpress'),
                    content: getData('duration')
                },
                passingGrade: {
                    label: __('Passing grade', 'learnpress'),
                    content: getData('passingGrade')
                },
                questionsCount: {
                    label: __('Questions', 'learnpress'),
                    content: (function () {
                        const ids = getData('questionsIds');
                        return ids ? ids.length : 0;
                    })()
                }
            })
        };
    })
)(Meta);