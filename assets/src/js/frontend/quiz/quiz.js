import QuizStartHandler from './start-quiz.js';
import QuizSubmitHandler from './submit-quiz.js';
import QuestionActionHandler from './question-actions.js';
import initQuizTimer from './timer.js';
import TemplateLoader from './template-loader.js';
import './circle-progress-bar.js';
(function () {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new QuizStartHandler();
            new QuizSubmitHandler();
            new QuestionActionHandler();
            new TemplateLoader();
            initQuizTimer();
        });
    } else {
        new QuizStartHandler();
        new QuizSubmitHandler();
        new QuestionActionHandler();
        new TemplateLoader();
        initQuizTimer();
    }

})();
