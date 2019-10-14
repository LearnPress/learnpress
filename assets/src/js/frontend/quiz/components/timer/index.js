import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
const {useState} = React;

class Timer extends Component {
    constructor() {
        super(...arguments);
        const t = 60;
        this.state = {
            seconds: t,
            totalTime: 60,
            remainingSeconds: t,
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
            remainingSeconds-=1;

            if (remainingSeconds > 0) {
                this.setState(({seconds}) => ({
                    remainingSeconds: remainingSeconds,
                    percent: (remainingSeconds / totalTime) * 100
                }))
            }

            if (remainingSeconds === 0) {
                clearInterval(this.myInterval)
            }
        }, 1000);
    }

    componentWillUnmount() {
        clearInterval(this.myInterval)
    }

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

        if(percent <= 5){
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
            content: getData('content')
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
])(Timer);