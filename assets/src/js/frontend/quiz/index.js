import {Component} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withDispatch, withSelect} from '@wordpress/data';
import {
    Title,
    Content,
    Meta,
    Buttons,
    Questions,
    Attempts,
    Timer,
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
    }

    startQuiz = (event) => {
        this.props.startQuiz();
    }

    render() {
        const {
            status
        } = this.props;

        const isA = -1 !== ['', 'completed'].indexOf(status);

        return <React.Fragment>
            { 'completed' === status && <Result/> }
            { isA && <Meta /> }
            { isA && <Content /> }

            { 'started' === status && <Status /> }

            { -1 !== ['completed', 'started'].indexOf(status) && <Questions />}

            <Buttons />

            {
                isA &&
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
            store: getData()
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