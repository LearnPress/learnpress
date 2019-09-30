import {Component} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withDispatch, withSelect} from '@wordpress/data';
import {
    Content,
    Meta,
    Buttons,
    Questions,
    Attempts,
    Result,
    Status
} from './components';

import store from './store';

class Quiz extends Component {
    componentDidMount() {
        const {
            settings,
            setQuizData
        } = this.props;

        setQuizData(settings);

        console.log(settings)
    }

    componentWillReceiveProps() {
        console.time('QUIZ');
    }

    componentDidUpdate() {
        console.timeEnd('QUIZ')
    }

    startQuiz = (event) => {
        this.props.startQuiz();
    };

    render() {
        const {
            status,
            isReviewing,
            answered,
            hintCount,
            checkCount
        } = this.props;

        const isA = -1 !== ['', 'completed'].indexOf(status);

        return <React.Fragment>
            <div>ANSWERS: [{JSON.stringify(answered)}]</div>
            <div>HINT: [{hintCount}]</div>
            <div>Explanation: [{checkCount}]</div>

            { !isReviewing && 'completed' === status && <Result/> }
            { !isReviewing && !status && <Meta /> }
            { !isReviewing && isA && <Content /> }

            { 'started' === status && <Status /> }

            { ((-1 !== ['completed', 'started'].indexOf(status)) || isReviewing) && <Questions />}

            <Buttons />

            {
                isA && !isReviewing &&
                <Attempts />
            }

        </React.Fragment>
    }
}

export default compose([
    withSelect((select) => {
        const {
            getQuestions,
            getData
        } = select('learnpress/quiz');

        return {
            questions: getQuestions(),
            status: getData('status'),
            store: getData(),
            answered: getData('answered'),
            isReviewing: getData('mode') === 'reviewing',
            hintCount: getData('show_hint'),
            checkCount: getData('show_check_answers')
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
])(Quiz);