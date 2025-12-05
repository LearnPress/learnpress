import { lpShowHideEl } from '../../utils.js';
import { __ } from '@wordpress/i18n';
/**
 * Instant Check Handler Class
 */
class QuestionActionHandler {
    constructor() {
        this.init();
    }

    /**
     * Initialize the handler
     */
    init() {
        this.bindEvents();
        this.bindInputEvents();
        this.bindTemplateLoadedEvent();
        this.restoreAllSavedAnswers();
    }

    /**
     * Bind event listener for template loaded
     * Restore saved answers when new content is loaded via AJAX
     */
    bindTemplateLoadedEvent() {
        document.addEventListener('lp-quiz-template-loaded', () => {
            // Restore saved answers for newly loaded questions
            this.restoreAllSavedAnswers();
        });
        if (typeof wp !== 'undefined' && wp.hooks) {
            wp.hooks.addAction('lp-ajax-pagination-completed', 'learnpress/quiz/restore-question-answers', (element, dataSend, response) => {
                // Wait for the next animation frame to ensure DOM has been updated
                requestAnimationFrame(() => {
                    // Restore saved answers for newly loaded questions
                    this.restoreAllSavedAnswers();
                });
            });
        }
    }

    /**
     * Bind click events to instant-check buttons
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.instant-check')) {
                this.handleCheckClick(e);
            } else if (e.target.closest('.btn-show-hint')) {
                e.preventDefault();
                this.handleHintToggle(e);
            }
        });
    }

    /**
     * Bind input change events to save answers
     */
    bindInputEvents() {
        // Use event delegation for better performance
        document.addEventListener('change', (e) => {
            const target = e.target;

            // Check if the changed element is a quiz input
            if (target.matches('.question input[type="radio"], .question input[type="checkbox"]')) {
                const questionEl = target.closest('.question');
                if (questionEl) {
                    this.saveAnswerForQuestion(questionEl);
                }
            }
        });

        // For text inputs (fill in the blanks), use input event with debouncing
        let inputTimeout;
        document.addEventListener('input', (e) => {
            const target = e.target;

            if (target.matches('.lp-fib-input > input')) {
                const questionEl = target.closest('.question');
                if (questionEl) {
                    // Debounce to avoid excessive localStorage writes
                    clearTimeout(inputTimeout);
                    inputTimeout = setTimeout(() => {
                        this.saveAnswerForQuestion(questionEl);
                    }, 500); // 500ms debounce
                }
            }
        });
    }

    /**
     * Get localStorage key for storing quiz answers
     * @returns {string} Storage key
     */
    getAnswerStorageKey() {
        const quizId = window.lpQuizSettings?.id || 0;
        const courseId = window.lpGlobalSettings?.post_id || 0;
        return `lp_quiz_answers_${courseId}_${quizId}`;
    }

    /**
     * Save answer for a specific question
     * @param {Element} questionEl - Question element
     */
    saveAnswerForQuestion(questionEl) {
        const questionId = questionEl.dataset.id;
        if (!questionId) {
            return;
        }

        const questionType = this.getQuestionType(questionEl);
        const answered = this.getAnsweredValue(questionEl, questionType);

        // Only save if there's an answer
        if (this.hasAnswer(answered)) {
            this.saveAnswer(questionId, answered, questionType);
        } else {
            // If no answer, clear the saved answer
            this.clearSavedAnswer(questionId);
        }
    }

    /**
     * Check if there's an actual answer
     * @param {*} answered - Answered value
     * @returns {boolean} True if has answer
     */
    hasAnswer(answered) {
        if (Array.isArray(answered)) {
            return answered.length > 0;
        } else if (typeof answered === 'object') {
            return Object.keys(answered).length > 0;
        } else {
            return answered !== '' && answered !== null && answered !== undefined;
        }
    }

    /**
     * Save answer to localStorage
     * @param {number} questionId - Question ID
     * @param {*} answered - Answered value
     * @param {string} questionType - Question type
     */
    saveAnswer(questionId, answered, questionType) {
        try {
            const storageKey = this.getAnswerStorageKey();
            const savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');

            savedAnswers[questionId] = {
                answer: answered,
                type: questionType,
                timestamp: Date.now()
            };

            localStorage.setItem(storageKey, JSON.stringify(savedAnswers));
        } catch (e) {
            console.error('Error saving answer to localStorage:', e);
        }
    }

    /**
     * Clear saved answer for a question
     * @param {number} questionId - Question ID
     */
    clearSavedAnswer(questionId) {
        try {
            const storageKey = this.getAnswerStorageKey();
            const savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');

            if (savedAnswers[questionId]) {
                delete savedAnswers[questionId];
                localStorage.setItem(storageKey, JSON.stringify(savedAnswers));
            }
        } catch (e) {
            console.error('Error clearing saved answer:', e);
        }
    }

    /**
     * Restore all saved answers from localStorage
     */
    restoreAllSavedAnswers() {
        try {
            const storageKey = this.getAnswerStorageKey();
            const savedAnswers = JSON.parse(localStorage.getItem(storageKey) || '{}');

            // Restore each saved answer
            Object.keys(savedAnswers).forEach((questionId) => {
                const savedData = savedAnswers[questionId];
                this.restoreQuestionAnswer(questionId, savedData.answer, savedData.type);
            });
        } catch (e) {
            console.error('Error restoring saved answers:', e);
        }
    }

    /**
     * Restore answer for a specific question
     * @param {number} questionId - Question ID
     * @param {*} answer - Answer to restore
     * @param {string} questionType - Question type
     */
    restoreQuestionAnswer(questionId, answer, questionType) {
        const questionEl = this.getQuestionElement(questionId);
        if (!questionEl) {
            return;
        }

        // Don't restore if already answered (checked)
        if (questionEl.classList.contains('question-answered')) {
            return;
        }

        if (questionType === 'fill_in_blanks') {
            // Restore FIB answers
            if (typeof answer === 'object') {
                Object.keys(answer).forEach((inputId) => {
                    const input = questionEl.querySelector(`.lp-fib-input > input[data-id="${inputId}"]`);
                    if (input) {
                        input.value = answer[inputId];
                    }
                });
            }
        } else if (questionType === 'multi_choice') {
            // Restore multi-choice answers
            if (Array.isArray(answer)) {
                answer.forEach((value) => {
                    const checkbox = questionEl.querySelector(`input[type="checkbox"][value="${value}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        } else if (questionType === 'single_choice' || questionType === 'true_or_false') {
            // Restore single-choice/true-false answer
            const radio = questionEl.querySelector(`input[type="radio"][value="${answer}"]`);
            if (radio) {
                radio.checked = true;
            }
        }
    }


    /**
     * Handle instant check button click
     * @param {Event} e - Click event
     */
    handleCheckClick(e) {
        e.preventDefault();

        const button = e.target.closest('.instant-check');
        const questionId = button.dataset.questionId;

        if (!questionId) {
            console.warn('No question ID found');
            return;
        }

        // Check if button is already loading
        if (button.classList.contains('loading')) {
            return;
        }

        // Get question element
        const questionEl = this.getQuestionElement(questionId);
        if (!questionEl) {
            console.warn(`Question element not found for ID: ${questionId}`);
            return;
        }

        // Get question type
        const questionType = this.getQuestionType(questionEl);

        // Check if question is answered
        const isAnswered = this.isQuestionAnswered(questionEl, questionType);

        if (!isAnswered) {
            // Show info message if not answered
            this.showInfoMessage(button);
            return;
        }

        // Hide info message if shown
        this.hideInfoMessage(button);

        // Add loading state
        button.classList.add('loading');

        // Get the answered value
        const answered = this.getAnsweredValue(questionEl, questionType);

        // Send check answer request
        this.checkAnswer(questionId, answered, questionType)
            .then((response) => {
                this.handleCheckSuccess(questionId, response, button);
            })
            .catch((error) => {
                this.handleCheckError(error, button);
            })
            .finally(() => {
                button.classList.remove('loading');
            });
    }

    /**
     * Get question element by ID
     * @param {number} questionId - Question ID
     * @returns {Element|null} Question element
     */
    getQuestionElement(questionId) {
        return document.querySelector(`.question[data-id="${questionId}"]`);
    }

    /**
     * Get question type from element
     * @param {Element} questionEl - Question element
     * @returns {string} Question type
     */
    getQuestionType(questionEl) {
        const classList = questionEl.className;

        if (classList.includes('question-fill_in_blanks')) {
            return 'fill_in_blanks';
        } else if (classList.includes('question-multi_choice')) {
            return 'multi_choice';
        } else if (classList.includes('question-single_choice')) {
            return 'single_choice';
        } else if (classList.includes('question-true_or_false')) {
            return 'true_or_false';
        }

        return 'unknown';
    }

    /**
     * Check if question is answered
     * @param {Element} questionEl - Question element
     * @param {string} questionType - Question type
     * @returns {boolean} True if answered
     */
    isQuestionAnswered(questionEl, questionType) {
        if (questionType === 'fill_in_blanks') {
            // Check if any FIB input has a value
            const inputs = questionEl.querySelectorAll('.lp-fib-input > input');

            for (let i = 0; i < inputs.length; i++) {
                if (inputs[i].value.length > 0) {
                    return true;
                }
            }

            return false;
        } else if (questionType === 'multi_choice') {
            // Check if any checkbox is checked
            return questionEl.querySelectorAll('input[type="checkbox"]:checked').length > 0;
        } else if (questionType === 'single_choice' || questionType === 'true_or_false') {
            // Check if any radio is checked
            return questionEl.querySelectorAll('input[type="radio"]:checked').length > 0;
        }

        return false;
    }

    /**
     * Get answered value from question element
     * @param {Element} questionEl - Question element
     * @param {string} questionType - Question type
     * @returns {*} Answered value
     */
    getAnsweredValue(questionEl, questionType) {
        if (questionType === 'fill_in_blanks') {
            const answers = {};
            const inputs = questionEl.querySelectorAll('.lp-fib-input > input');

            inputs.forEach((input) => {
                const id = input.dataset.id;
                const value = input.value;

                if (id && value) {
                    answers[id] = value;
                }
            });

            return answers;
        } else if (questionType === 'multi_choice') {
            // Get all checked checkbox values
            const values = [];
            const checkboxes = questionEl.querySelectorAll('input[type="checkbox"]:checked');

            checkboxes.forEach((checkbox) => {
                values.push(checkbox.value);
            });

            return values;
        } else if (questionType === 'single_choice' || questionType === 'true_or_false') {
            // Get checked radio value
            const radio = questionEl.querySelector('input[type="radio"]:checked');
            return radio ? radio.value : '';
        }

        return '';
    }

    /**
     * Show info message on button
     * @param {Element} button - Button element
     */
    showInfoMessage(button) {
        const info = button.querySelector('.instant-check__info');
        if (info) {
            info.style.display = 'block';
        }
    }

    /**
     * Hide info message on button
     * @param {Element} button - Button element
     */
    hideInfoMessage(button) {
        const info = button.querySelector('.instant-check__info');
        if (info) {
            info.style.display = 'none';
        }
    }

    /**
     * Send check answer API request
     * @param {number} questionId - Question ID
     * @param {*} answered - Answered value
     * @param {string} questionType - Question type
     * @returns {Promise} API request promise
     */
    checkAnswer(questionId, answered, questionType) {
        // Get quiz and course IDs from global settings
        const itemId = window.lpQuizSettings?.id || 0;
        const courseId = window.lpGlobalSettings?.post_id || 0;

        const url = (window.lpData?.lp_rest_url || '') + 'lp/v1/users/check-answer';

        const body = new FormData();
        body.append('item_id', itemId);
        body.append('course_id', courseId);
        body.append('question_id', questionId);

        // Handle different answer formats
        if (typeof answered === 'object' && !Array.isArray(answered)) {
            // For FIB answers (object)
            body.append('answered', JSON.stringify(answered));
        } else if (Array.isArray(answered)) {
            // For multi-choice answers (array)
            body.append('answered', JSON.stringify(answered));
        } else {
            // For single-choice/true-false (string)
            body.append('answered', answered);
        }

        const headers = {};

        // Add nonce if available
        if (window.lpData?.nonce) {
            headers['X-WP-Nonce'] = window.lpData.nonce;
        }

        return fetch(url, {
            method: 'POST',
            headers: headers,
            body: body
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    }

    /**
     * Handle successful check answer response
     * @param {number} questionId - Question ID
     * @param {Object} response - API response
     * @param {Element} button - Button element
     */
    handleCheckSuccess(questionId, response, button) {
        if (response.status === 'success') {
            // Update question display with results
            this.updateQuestionDisplay(questionId, response);

            // Display question response (correct/incorrect label with points)
            this.displayQuestionResponse(questionId, response);

            // Store checked question in localStorage if using offline mode
            this.storeCheckedQuestion(questionId, response);

            // Disable the question inputs
            this.disableQuestionInputs(questionId);

            // Hide the check button
            button.style.display = 'none';

            // Clear saved answer from localStorage since it's now checked
            this.clearSavedAnswer(questionId);

            // Trigger custom event for other scripts
            const event = new CustomEvent('lp-question-checked', {
                detail: {
                    questionId: questionId,
                    response: response
                }
            });
            document.dispatchEvent(event);
        } else {
            console.error('Check answer failed:', response.message || 'Unknown error');
        }
    }

    /**
     * Handle check answer error
     * @param {Object} error - Error object
     * @param {Element} button - Button element
     */
    handleCheckError(error, button) {
        console.error('Check answer error:', error);
    }

    /**
     * Display question response (correct/incorrect label with points)
     * Similar to getCorrectLabel() in React component
     * @param {number} questionId - Question ID
     * @param {Object} response - API response
     */
    displayQuestionResponse(questionId, response) {
        const questionEl = this.getQuestionElement(questionId);

        if (!questionEl) {
            return;
        }

        // Check if response HTML is provided from backend
        if (response.response_html) {
            // Use the server-rendered response HTML from QuestionTemplate->question_response_html
            const answersDiv = questionEl.querySelector('.question-answers');
            if (answersDiv) {
                // Remove existing response if any
                const existingResponse = answersDiv.querySelector('.question-response');
                if (existingResponse) {
                    existingResponse.remove();
                }

                // Insert the response HTML
                answersDiv.insertAdjacentHTML('beforeend', response.response_html);
            }
        } else {
            // Fallback: Create response HTML manually if not provided by backend
            this.createQuestionResponseManually(questionEl, response);
        }
    }

    /**
     * Create question response HTML manually (fallback)
     * @param {Element} questionEl - Question element
     * @param {Object} response - API response
     */
    createQuestionResponseManually(questionEl, response) {
        const isCorrect = response?.result?.correct || false;
        const point = response?.result?.mark || 0;
        const earnedPoint = isCorrect ? point : 0;

        const responseClass = isCorrect ? 'correct' : 'incorrect';
        const labelText = isCorrect ? __('Correct', 'learnpress') : __('Incorrect', 'learnpress');

        const responseHTML = `
            <div class="question-response ${responseClass}">
                <span class="label">${labelText}</span>
                <span class="point">${earnedPoint}/${point} point</span>
            </div>
        `;

        const answersDiv = questionEl.querySelector('.question-answers');
        if (answersDiv) {
            // Remove existing response if any
            const existingResponse = answersDiv.querySelector('.question-response');
            if (existingResponse) {
                existingResponse.remove();
            }

            answersDiv.insertAdjacentHTML('beforeend', responseHTML);
        }
    }

    /**
     * Update question display with check results
     * @param {number} questionId - Question ID
     * @param {Object} response - API response
     */
    updateQuestionDisplay(questionId, response) {
        const questionEl = this.getQuestionElement(questionId);

        if (!questionEl) {
            return;
        }

        // Add checked class
        questionEl.classList.add('question-answered');

        // Update question options with results (if provided)
        if (response.options) {
            // The backend should return updated option data
            // Update will depend on implementation
            // This is where you'd update the visual display
            // to show correct/incorrect answers
        }
    }

    /**
     * Store checked question in localStorage
     * @param {number} questionId - Question ID
     * @param {Object} response - API response
     */
    storeCheckedQuestion(questionId, response) {
        // Only for offline quiz mode
        if (window.lpQuizSettings?.checkNorequizenroll !== 1) {
            return;
        }

        const keyQuizOff = 'quiz_off_' + window.lpQuizSettings.id;
        const quizDataOffStr = window.localStorage.getItem(keyQuizOff);

        if (!quizDataOffStr) {
            return;
        }

        try {
            const quizDataOff = JSON.parse(quizDataOffStr);
            const questionOptions = response.options;

            // Initialize checked questions array if needed
            if (typeof quizDataOff.checked_questions === 'undefined') {
                quizDataOff.checked_questions = [];
            }

            // Add question ID to checked questions if not already there
            if (quizDataOff.checked_questions.indexOf(questionId) === -1) {
                quizDataOff.checked_questions.push(questionId);
            }

            // Initialize question options object if needed
            if (typeof quizDataOff.question_options === 'undefined') {
                quizDataOff.question_options = {};
            }

            // Store question options
            if (questionOptions) {
                quizDataOff.question_options[questionId] = questionOptions;
            }

            // Save back to localStorage
            window.localStorage.setItem(keyQuizOff, JSON.stringify(quizDataOff));
        } catch (e) {
            console.error('Error storing checked question:', e);
        }
    }

    /**
     * Disable question inputs after checking
     * @param {number} questionId - Question ID
     */
    disableQuestionInputs(questionId) {
        const questionEl = this.getQuestionElement(questionId);

        if (!questionEl) {
            return;
        }

        // Disable all inputs
        const inputs = questionEl.querySelectorAll('input');
        inputs.forEach((input) => {
            input.disabled = true;
        });
    }
    handleHintToggle(e) {
        const hintButton = e.target.closest('.btn-show-hint');
        // Find the question container
        const questionContainer = hintButton.closest('.question');

        if (!questionContainer) {
            return;
        }

        // Find the hint content element
        const hintContent = questionContainer.querySelector('.question-hint-content');

        if (!hintContent) {
            return;
        }

        // Check current state
        const isHidden = hintContent.classList.contains('lp-hidden');

        // Toggle visibility using lpShowHideEl
        // status 0: hide, 1: show
        lpShowHideEl(hintContent, isHidden ? 1 : 0);
    }
}
export default QuestionActionHandler;
