import {Component} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';

const {Hook} = LP;

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
                                <label dangerouslySetInnerHTML={{__html: field.title}}>
                                </label>
                                <span dangerouslySetInnerHTML={{__html: field.content}}>
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

        const {
            singleCourse
        } = LP;
        return {
            metaFields: Hook.applyFilters('quiz-meta-fields', {
                // attemptsCount: {
                //     label: __('Attempts allowed', 'learnpress'),
                //     content: getData('attemptsCount')
                // },
                duration: {
                    title: __('Duration', 'learnpress'),
                    content: singleCourse.formatDuration(getData('duration'))
                },
                passingGrade: {
                    title: __('Passing grade', 'learnpress'),
                    content: getData('passingGrade')
                },
                questionsCount: {
                    title: __('Questions', 'learnpress'),
                    content: (function () {
                        const ids = getData('questionIds');
                        return ids ? ids.length : 0;
                    })()
                }
            })
        };
    })
)(Meta);