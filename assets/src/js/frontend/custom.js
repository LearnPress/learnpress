/**
 * Custom functions for frontend quiz.
 */

const {
    Hook
} = LP;

const $ = jQuery;

Hook.addFilter('question-blocks', function (blocks) {
    return blocks; ///[ 'answer-options', 'title', 'content', 'hint', 'explanation'];
});

Hook.addAction('before-start-quiz', function () {
});

Hook.addAction('quiz-started', function (results, id) {
    $(`.course-item-${id}`).removeClass('status-completed failed passed').addClass('has-status status-started');
    window.onbeforeunload = function () {
        return 'Warning!';
    }
});

Hook.addAction('quiz-submitted', function (response, id) {
    $(`.course-item-${id}`).removeClass('status-started passed failed').addClass(`has-status status-completed ${response.results.graduation}`);
    window.onbeforeunload = null;
});

$(document).ready(() => {
    const CustomComponent = function () {
        const [time, setTime] = React.useState(0);
        let [t, setT] = React.useState();

        if (!t) {
            t = setInterval(() => {
                setTime(new Date().toString())
            }, 1000);

            setT(t)
        }

        return <div>
            <LP.quiz.MyContext.Consumer>
                {
                    (a) => {
                        return a.status;
                    }
                }
            </LP.quiz.MyContext.Consumer>

            {time}
        </div>;
    }

    function CustomComponent2() {
        const [time, setTime] = React.useState(0);

        let [t, setT] = React.useState();

        if (!t) {
            t = setInterval(() => {
                setTime(time+1);
                console.log(time)
            }, 1000);

            setT(t)
        }

        return <div>
            <LP.quiz.MyContext.Consumer>
                {
                    (a) => {
                        return a.status;
                    }
                }
            </LP.quiz.MyContext.Consumer>

            {time}
        </div>;
    }

    // Hook.addAction('xxxx', () => {
    //     return <CustomComponent key="1"/>
    // })
    // Hook.addAction('xxxx', () => {
    //     return <CustomComponent2 key="2"/>
    // })
    // setTimeout(() => {
    //     //wp.element.render(<CustomComponent />, jQuery('#test-element')[0])
    //
    // }, 1000)
})