import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
import {__, _x, sprintf} from '@wordpress/i18n';

const {get, debounce} = lodash;

class Result extends Component {
    constructor() {
        super(...arguments);

        this.state = {
            percentage: 0,
            done: false
        }
    }

    /**
     * Get result message.
     *
     * @param results
     * @return {*|string}
     */
    getResultMessage = (results) => {
        return sprintf(__('Your grade is <strong>%s</strong>', 'learnpress'), results.graduationText);
    };

    /**
     * Get result percentage.
     *
     * @param results
     * @return {string}
     */
    getResultPercentage = (results) => {
        // const {
        //     percent
        // } = this.state;
        //
        // const maxPercent = results.result;


        return results.result === 100 ? results.result : parseFloat(results.result).toFixed(2);
    };

    componentDidMount() {
        this.animate();
    }

    componentDidUpdate(prevProps) {
        const {
            results
        } = this.props;

        if (prevProps.results.result === results.result) {
            return;
        }

        this.animate();
    }

    animate() {
        const {
            results
        } = this.props;

        this.setState({
            percentage: 0,
            done: false
        });

        jQuery.easing['_customEasing'] = function (e, f, a, h, g) {
            return h * Math.sqrt(1 - (f = f / g - 1) * f) + a
        }
        /*function(e, f, a, h, g) {
         return (f == g) ? a + h : h * (-Math.pow(2, -10 * f / g) + 1) + a
         }*/

        debounce(() => {
            var $el = jQuery('<span />').css({
                width: 1,
                height: 1
            }).appendTo(document.body);
            $el.css('left', 0).animate({left: results.result}, {
                duration: 1500,
                step: (now, fx) => {
                    this.setState({percentage: now})
                },
                done: () => {
                    this.setState({done: true});
                    $el.remove();
                    jQuery('#quizResultGrade').css({
                        transform: 'scale(1.3)',
                        transition: 'all 0.25s'
                    });

                    debounce(() => {
                        jQuery('#quizResultGrade').css({
                            transform: 'scale(1)'
                        });
                    }, 500)()
                },
                easing: '_customEasing'
            })
        }, results.result > 0 ? 1000 : 10)();
    }

    /**
     * Render HTML elements.
     *
     * @return {XML}
     */
    render() {
        const {
            results,
            passingGrade
        } = this.props;

        let {
            percentage,
            done
        } = this.state;

        if (percentage < 100) {
            percentage = parseFloat(percentage).toFixed(2)
        }

        const classNames = ['quiz-result', results.graduation];
        const border = 10;
        const width = 200;
        const percent = this.getResultPercentage(results);

        const radius = width / 2;
        const r = ( width - border ) / 2;
        const circumference = r * 2 * Math.PI;
        const offset = circumference - percentage / 100 * circumference;
        const styles = {
            strokeDasharray: `${circumference} ${circumference}`,
            strokeDashoffset: offset
        }
        const passingGradeValue = results.passingGrade || passingGrade;

        return <div className={ classNames.join(' ') }>
            <h3 className="result-heading">{ __('Your Result', 'learnpress') }</h3>
            <div id="quizResultGrade" className="result-grade">
                <svg className="circle-progress-bar" width={width} height={width}>
                    <circle className="circle-progress-bar__circle" stroke="" strokeWidth={border} style={styles}
                            fill="transparent" r={r} cx={radius} cy={radius}></circle>
                </svg>

                <span className="result-achieved">{ percentage }%</span>
                <span
                    className="result-require">{ passingGradeValue ? passingGradeValue : _x('-', 'unknown passing grade value', 'learnpress') }</span>

            </div>

            { done && <p className="result-message">{results.graduationText}</p> }

            <ul className="result-statistic">
                <li className="result-statistic-field result-time-spend">
                    <label>{ __('Time spend', 'learnpress') }</label>
                    <p>{results.timeSpend}</p>
                </li>
                <li className="result-statistic-field result-point">
                    <label>{ __('Point', 'learnpress') }</label>
                    <p>{ results.userMark } / { results.mark }</p>
                </li>
                <li className="result-statistic-field result-questions">
                    <label>{ __('Questions', 'learnpress') }</label>
                    <p>{ results.questionCount }</p>
                </li>
                <li className="result-statistic-field result-questions-correct">
                    <label>{ __('Correct', 'learnpress') }</label>
                    <p>{ results.questionCorrect }</p>
                </li>
                <li className="result-statistic-field result-questions-wrong">
                    <label>{ __('Wrong', 'learnpress') }</label>
                    <p>{ results.questionWrong }</p>
                </li>
                <li className="result-statistic-field result-questions-skipped">
                    <label>{ __('Skipped', 'learnpress') }</label>
                    <p>{ results.questionEmpty }</p>
                </li>
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
            results: getData('results'),
            passingGrade: getData('passingGrade')
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
])(Result);