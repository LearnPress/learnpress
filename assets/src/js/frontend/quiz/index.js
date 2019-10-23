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

const {chunk, isNumber} = lodash;
const $ = jQuery;

class Quiz extends Component {
    constructor(props) {
        super(...arguments);


        this.state = {
            currentPage: 1,
            numPages: 0,
            pages: []
        };
    }

    componentDidMount() {
        console.time('Quiz.componentDidMount');
        const {
            settings,
            setQuizData
        } = this.props;

        const {
            question_ids,
            questions_per_page
        } = settings;

        const chunks = chunk(question_ids, questions_per_page);

        settings.currentPage = 1;
        settings.numPages = chunks.length;
        settings.pages = chunks;

        console.timeEnd('Quiz.componentDidMount');
        setQuizData(settings);

        console.log(wp.data.select('learnpress/quiz').getData())

    }


    componentWillReceiveProps(nextProps) {
        console.time('QUIZ');

        const {
            questionIds,
            questionsPerPage,
            setQuizData
        } = nextProps;

        const chunks = chunk(questionIds, questionsPerPage);

        // setQuizData({
        //     numPages: chunks.length,
        //     pages: chunks
        // });
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
            isReviewing
        } = this.props;

        const isA = -1 !== ['', 'completed'].indexOf(status);

        // Just render content if status !== undefined (meant all data loaded)
        return undefined !== status && (
                <React.Fragment>

                    { !isReviewing && 'completed' === status && <Result/> }
                    { !isReviewing && !status && <Meta /> }
                    { !isReviewing && isA && <Content /> }

                    { 'started' === status && <Status /> }

                    { ((-1 !== ['completed', 'started'].indexOf(status)) || isReviewing) &&
                    <Questions/>}

                    <Buttons />

                    {
                        isA && !isReviewing &&
                        <Attempts />
                    }

                </React.Fragment>
            )
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
            //hintCount: getData('showHint'),
            questionIds: getData('questionIds'),
            checkCount: getData('instantCheck'),
            questionsPerPage: getData('questionsPerPage') || 1
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