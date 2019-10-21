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
    window.onbeforeunload = function () {
        return 'Warning!';
    }
});

Hook.addAction('quiz-started', function (results, id) {
    $(`.course-item-${id}`).removeClass('status-completed failed passed').addClass('has-status status-started');
});

Hook.addAction('quiz-submitted', (results) => {
    $(`.course-item-${id}`).removeClass('status-started').addClass(`has-status status-completed ${results.grade}`);
    window.onbeforeunload = null;
});