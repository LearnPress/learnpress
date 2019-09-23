import {Component} from '@wordpress/element';
import {withSelect, withDispatch} from '@wordpress/data';
import {compose} from '@wordpress/compose';
const {useState} = React;

class Timer extends Component {
    constructor() {
        super(...arguments);
        const t = 1800;
        this.state = {
            seconds: t,
            totalTime: 3600,
            remainingSeconds: t,
            currentTime: parseInt(new Date().getTime() / 1000)
        }
    }

    componentDidMount() {
        this.myInterval = setInterval(() => {
            const {seconds, currentTime} = this.state;
            const offset = parseInt(new Date().getTime() / 1000) - currentTime;

            let remainingSeconds = seconds - offset;

            if (remainingSeconds > 0) {
                this.setState(({seconds}) => ({
                    remainingSeconds: remainingSeconds
                }))
            }

            if (remainingSeconds === 0) {
                clearInterval(this.myInterval)
            }
        }, 500);
    }

    componentWillUnmount(){
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

    render() {
        const {
            content
        } = this.props;

        return <div>
            {this.formatTime()}
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