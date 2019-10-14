import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import Timer from '../timer';
import {__, sprintf} from '@wordpress/i18n';
const $ = jQuery;
const {debounce} = lodash;

class Status extends Component {
    constructor() {
        super(...arguments);
    }

    componentDidMount() {
        const $pc = $('#popup-content');
        const $sc = $pc.find('.content-item-scrollable:eq(1)');
        const $ciw = $pc.find('.content-item-wrap');
        const $qs = $pc.find('.quiz-status');
        const pcTop = $qs.offset().top - 92;

        let isFixed = false;
        let marginLeft = '-' + $ciw.css('margin-left');

        $(window).resize(debounce(function () {
            marginLeft = '-' + $ciw.css('margin-left');

            $qs.css({
                'margin-left': marginLeft,
                'margin-right': marginLeft
            });
        }, 100)).trigger('resize');

        $sc.scroll(() => {

            if ($sc.scrollTop() >= pcTop) {
                if (isFixed) {
                    return;
                }
                isFixed = true;
            } else {
                if (!isFixed) {
                    return;
                }
                isFixed = false;
            }

            if (isFixed) {
                $pc.addClass('fixed-quiz-status');
            } else {
                $pc.removeClass('fixed-quiz-status');
            }
        })
    };

    render() {
        const {
            currentPage,
            numPages,
            questionsPerPage,
            questionsCount
        } = this.props;

        const result = {
            timeSpend: 123,
            marks: [],
            questionsCount: 5,
            questionsCorrect: [],
            questionsWrong: [],
            questionsSkipped: []
        };

        let start = (currentPage - 1) * questionsPerPage + 1;
        let end = start + questionsPerPage - 1;

        end = Math.min(end, questionsCount);


        return <div className="quiz-status">
            <div>
                <div className="questions-index">
                    {
                        end < questionsCount && (
                            questionsPerPage > 1
                                ? sprintf(__('Question %d to %d of %d', 'learnpress'), start, end, questionsCount)
                                : sprintf(__('Question %d of %d', 'learnpress'), start, questionsCount)
                        )
                    }

                    {
                        end === questionsCount && sprintf(__('Question %d to %d', 'learnpress'), start, end)
                    }
                </div>

                <div className="submit-quiz">
                    <button className="lp-button" id="button-submit-quiz">{ __('Submit', 'learnpress') }</button>
                </div>

                <Timer />

            </div>
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

        return {
            currentPage: getData('currentPage'),
            numPages: getData('numPages'),
            questionsPerPage: getData('questionsPerPage'),
            questionsCount: getData('questionIds').length
        }
    }),
    withDispatch((dispatch) => {
        const {
            setQuizData,
            startQuiz
        } = dispatch('learnpress/quiz');

        return {
            setQuizData,
            startQuiz
        }
    })
])(Status);