import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
const {useState} = React;

class Timer extends Component {
    constructor(props) {
        super(...arguments);

        this.init(props)
    }

    init = (props) => {
        const {
            endTime,
            totalTime
        } = props;

        const d1 = new Date(endTime);
        const d2 = new Date();
        const tz = new Date().getTimezoneOffset();
        const t = parseInt(d1.getTime() / 1000 - (d2.getTime() / 1000 + (tz * 60 ) ));

        this.state = {
            seconds: t,
            totalTime: totalTime,
            remainingSeconds: t > 0 ? t : 0,
            currentTime: parseInt(new Date().getTime() / 1000),
            percent: 100
        }
    }

    componentDidMount() {
        this.myInterval = setInterval(() => {
            const {seconds, currentTime, totalTime} = this.state;
            //const offset = parseInt(new Date().getTime() / 1000) - currentTime;
            //let remainingSeconds = seconds - offset;

            let {remainingSeconds} = this.state;
            remainingSeconds -= 1;

            if (remainingSeconds > 0) {
                this.setState(({seconds}) => ({
                    remainingSeconds: remainingSeconds,
                    percent: (remainingSeconds / totalTime) * 100
                }))
            }

            if (remainingSeconds <= 0) {
                clearInterval(this.myInterval);
                this.submit();
            }
        }, 1000);
    }

    componentWillUnmount() {
        clearInterval(this.myInterval)
    }

    /**
     * Submit question to record results.
     */
    submit = () => {
        const {
            submitQuiz
        } = this.props;

        submitQuiz();
    };

    formatTime = (separator = ':') => {
        const {remainingSeconds: seconds, totalTime} = this.state;
        const t = [];
        var m;

        if (totalTime < 3600) {
            t.push((seconds - seconds % 60) / 60);
            t.push(seconds % 60);
        } else if (totalTime) {
            t.push((seconds - seconds % 3600) / 3600);
            m = seconds % 3600;
            t.push((m - m % 60) / 60);
            t.push(m % 60);
        }

        return t.map((a) => {
            return a < 10 ? `0${a}` : a;
        }).join(separator);
    }

    getCircle = () => {
        const {percent} = this.state;

        const width = 40;
        const border = 4;
        const radius = width / 2;
        const r = ( width - border ) / 2;
        const circumference = r * 2 * Math.PI;
        const offset = circumference - percent / 100 * circumference;
        const styles = {
            strokeDasharray: `${circumference} ${circumference}`,
            strokeDashoffset: offset
        };

        const className = ['clock'];

        if (percent <= 5) {
            className.push('x')
        }

        return <div className={ className.join(' ') }>
            <svg className="circle-progress-bar" width={ width } height={ width }>
                <circle className="circle-progress-bar__circle" strokeWidth={ border } style={ styles }
                        fill="transparent" r={ r } cx={ radius } cy={ radius }>
                </circle>
            </svg>
        </div>
    }

    render() {
        const {
            content
        } = this.props;


        return <div className="countdown">
            <span>{ this.formatTime() }</span>

            { this.getCircle() }
        </div>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getData
        } = select('learnpress/quiz');

        return {
            submitting: getData('submitting'),
            totalTime: getData('totalTime') ? getData('totalTime') : getData('duration'),
            endTime: getData('endTime')
        }
    }),
    withDispatch((dispatch) => {
        const {
            setQuizData,
            submitQuiz
        } = dispatch('learnpress/quiz');

        return {
            setQuizData,
            submitQuiz
        }
    })
])(Timer);