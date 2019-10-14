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

        const sanitizedSettings = {};

        function camelCaseDash(string) {
            return string.replace(
                /[-_]([a-z])/g,
                (match, letter) => letter.toUpperCase()
            );
        }

        for (let prop in settings) {
            if (!settings.hasOwnProperty(prop)) {
                continue;
            }

            sanitizedSettings[camelCaseDash(prop)] = settings[prop];
        }

        const {
            questionIds,
            questionsPerPage
        } = sanitizedSettings;

        const chunks = chunk(questionIds, questionsPerPage);

        sanitizedSettings.currentPage = 1;
        sanitizedSettings.numPages = chunks.length;
        sanitizedSettings.pages = chunks;

        console.timeEnd('Quiz.componentDidMount');
        console.log(sanitizedSettings)
        setQuizData(sanitizedSettings);
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
            isReviewing,
            // numPages,
            // currentPage
        } = this.props;

        // const {
        //     numPages,
        //     currentPage,
        //     pages
        // } = this.state;

        const isA = -1 !== ['', 'completed'].indexOf(status);

        // Just render content if status !== undefined (meant all data loaded)
        return undefined !== status && (
                <React.Fragment>
                    {/*<div>ANSWERS: [{JSON.stringify(answered)}]</div>*/}
                    {/*<div>HINT: [{hintCount}]</div>*/}
                    {/*<div>Explanation: [{checkCount}]</div>*/}

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