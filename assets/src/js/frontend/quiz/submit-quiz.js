import { __ } from '@wordpress/i18n';
import SweetAlert from 'sweetalert2';
import { show as showMessage } from '../../lpToastify.js';
/**
 * Quiz Submit Handler Class
 */
class QuizSubmitHandler {
    constructor() {
        this.submitting = false;
        this.init();
    }

    /**
     * Initialize the handler
     */
    init() {
        this.bindEvents();
    }

    /**
     * Bind click events to submit quiz button
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('#button-submit-quiz') || e.target.closest('.lp-button.submit-quiz')) {
                this.handleSubmitClick(e);
            }
        });

        // Listen for timer auto-submit event (skip confirmation)
        document.addEventListener('lp-quiz-timer-auto-submit', (e) => {
            this.handleAutoSubmit(e.detail);
        });
    }

    /**
     * Handle auto-submit from timer expiration (no confirmation needed)
     * @param {Object} detail - Event detail with timeSpent
     */
    async handleAutoSubmit(detail) {
        // Check if already submitting
        if (this.submitting) {
            return;
        }

        const button = document.getElementById('button-submit-quiz') || document.querySelector('.lp-button.submit-quiz');
        if (!button) {
            console.error('Submit button not found for auto-submit');
            return;
        }

        // Perform submission without confirmation
        await this.performSubmission(button, {
            autoSubmit: true,
            timeSpent: detail?.timeSpent
        });
    }

    /**
     * Handle submit button click
     * @param {Event} e - Click event
     */
    async handleSubmitClick(e) {
        e.preventDefault();

        const button = e.target.closest('#button-submit-quiz') || e.target.closest('.lp-button.submit-quiz');

        // Check if already submitting
        if (this.submitting) {
            return;
        }

        // Confirm submission
        const confirmed = await this.confirmSubmit();
        if (!confirmed) {
            return;
        }

        // Perform submission
        await this.performSubmission(button);
    }

    /**
     * Perform quiz submission with common logic
     * @param {Element} button - Submit button element
     * @param {Object} options - Submission options
     * @param {boolean} options.autoSubmit - Whether this is auto-submit (for event detail)
     * @param {number} options.timeSpent - Optional time spent value to use
     * @returns {Promise<void>}
     */
    async performSubmission(button, options = {}) {
        // Set submitting state
        this.submitting = true;
        button.disabled = true;
        button.textContent = __('Submitting…', 'learnpress');
        button.classList.add('loading');

        // Dispatch custom event to notify other components (e.g., timer)
        const submittingEvent = new CustomEvent('lp-quiz-submitting', {
            detail: {
                timestamp: Date.now(),
                autoSubmit: options.autoSubmit || false
            }
        });
        document.dispatchEvent(submittingEvent);

        // Get all answers
        const answered = this.collectAnswers();

        // Get time spent (use options.timeSpent if provided, otherwise call getTimeSpend())
        const timeSpend = options.timeSpent !== undefined ? options.timeSpent : this.getTimeSpend();

        // Submit quiz
        this.submitQuiz(answered, timeSpend)
            .then((response) => {
                this.handleSubmitSuccess(response, button);
            })
            .catch((error) => {
                this.handleSubmitError(error, button);
            })
            .finally(() => {
                this.submitting = false;
                button.disabled = false;
                button.classList.remove('loading');
            });
    }

    /**
     * Confirm quiz submission with user using SweetAlert
     * @returns {Promise<boolean>} True if user confirms
     */
    async confirmSubmit() {
        const result = await SweetAlert.fire({
            title: __('Submit Quiz?', 'learnpress'),
            text: __('Are you sure you want to submit the quiz? You cannot change your answers after submission.', 'learnpress'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: __('Submit', 'learnpress'),
            cancelButtonText: __('Cancel', 'learnpress'),
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        });

        return result.isConfirmed;
    }

    /**
     * Collect all answers from the quiz
     * Combines answers from current page (DOM) and saved answers from all pages (localStorage)
     * @returns {Object} Answers object with question_id => answer format
     */
    collectAnswers() {
        const answers = {};

        // First, get saved answers from localStorage (includes answers from all pages)
        const savedAnswers = this.getSavedAnswers();

        // Add saved answers to the collection
        Object.keys(savedAnswers).forEach((questionId) => {
            const savedData = savedAnswers[questionId];
            answers[questionId] = savedData.answer;
        });

        // Then, get current page answers from DOM
        const questions = document.querySelectorAll('.question');

        questions.forEach((questionEl) => {
            const questionId = questionEl.dataset.id;
            const questionType = this.getQuestionType(questionEl);
            const answer = this.getQuestionAnswer(questionEl, questionType);

            // Override saved answer with current answer if present
            if (answer !== null && answer !== '' && answer !== undefined) {
                answers[questionId] = answer;
            }
        });

        return answers;
    }

    /**
     * Get saved answers from localStorage
     * @returns {Object} Saved answers object
     */
    getSavedAnswers() {
        try {
            const quizId = window.lpQuizSettings?.id || 0;
            const courseId = window.lpGlobalSettings?.post_id || 0;
            const storageKey = `lp_quiz_answers_${courseId}_${quizId}`;
            return JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch (e) {
            console.error('Error reading saved answers:', e);
            return {};
        }
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
     * Get answer for a specific question
     * @param {Element} questionEl - Question element
     * @param {string} questionType - Question type
     * @returns {*} Answer value
     */
    getQuestionAnswer(questionEl, questionType) {
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

            return Object.keys(answers).length > 0 ? answers : null;
        } else if (questionType === 'multi_choice') {
            const values = [];
            const checkboxes = questionEl.querySelectorAll('input[type="checkbox"]:checked');

            checkboxes.forEach((checkbox) => {
                values.push(checkbox.value);
            });

            return values.length > 0 ? values : null;
        } else if (questionType === 'single_choice' || questionType === 'true_or_false') {
            const radio = questionEl.querySelector('input[type="radio"]:checked');
            return radio ? radio.value : null;
        }

        return null;
    }

    /**
     * Get time spent on quiz
     * @returns {number} Time spent in seconds
     */
    getTimeSpend() {
        // Try to get from hidden input first
        const timeInput = document.querySelector('input[name="lp-quiz-time-spend"]');
        if (timeInput) {
            return parseInt(timeInput.value) || 0;
        }

        // Calculate from start time if available
        const startTime = window.lpQuizSettings?.start_time || 0;
        if (startTime) {
            const currentTime = Math.floor(Date.now() / 1000);
            return currentTime - startTime;
        }

        // Fallback: use total_time if available
        return window.lpQuizSettings?.total_time || 0;
    }

    /**
     * Submit quiz to server
     * @param {Object} answered - Answers object
     * @param {number} timeSpend - Time spent in seconds
     * @returns {Promise} API request promise
     */
    submitQuiz(answered, timeSpend) {
        const itemId = window.lpQuizSettings?.id || 0;
        const courseId = window.lpGlobalSettings?.post_id || 0;

        const url = (window.lpData?.lp_rest_url || '') + 'lp/v1/users/submit-quiz';

        const body = new FormData();
        body.append('item_id', itemId);
        body.append('course_id', courseId);
        body.append('time_spend', timeSpend);

        // Add answers
        Object.keys(answered).forEach((questionId) => {
            const answer = answered[questionId];
            const fieldName = `answered[${questionId}]`;

            if (typeof answer === 'object' && !Array.isArray(answer)) {
                // For FIB answers (object)
                body.append(fieldName, JSON.stringify(answer));
            } else if (Array.isArray(answer)) {
                // For multi-choice answers (array)
                body.append(fieldName, JSON.stringify(answer));
            } else {
                // For single-choice/true-false (string)
                body.append(fieldName, answer);
            }
        });

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
     * Handle successful quiz submission
     * @param {Object} response - API response
     * @param {Element} button - Submit button element
     */
    handleSubmitSuccess(response, button) {
        if (response.status === 'success') {
            // console.log('Quiz submitted successfully:', response);

            // Update button text
            button.textContent = __('Submitted', 'learnpress');

            // Clear localStorage answers
            this.clearStoredAnswers();

            // Trigger custom event
            const event = new CustomEvent('lp-quiz-submitted', {
                detail: {
                    response: response
                }
            });
            document.dispatchEvent(event);

            // Reload page to show results
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('Quiz submission failed:', response.message || 'Unknown error');
            showMessage(__('Failed to submit quiz: ', 'learnpress') + (response.message || 'Unknown error'), 'error');
            button.textContent = __('Submit Quiz', 'learnpress');
        }
    }

    /**
     * Handle quiz submission error
     * @param {Object} error - Error object
     * @param {Element} button - Submit button element
     */
    handleSubmitError(error, button) {
        console.error('Quiz submission error:', error);
        showMessage(__('Failed to submit quiz. Please try again.', 'learnpress'), 'error');
        button.textContent = __('Submit Quiz', 'learnpress');
    }

    /**
     * Clear stored answers from localStorage
     */
    clearStoredAnswers() {
        try {
            const quizId = window.lpQuizSettings?.id || 0;
            const courseId = window.lpGlobalSettings?.post_id || 0;
            const storageKey = `lp_quiz_answers_${courseId}_${quizId}`;
            localStorage.removeItem(storageKey);

            // Also clear saved current page
            const pageStorageKey = `lp_quiz_current_page_${quizId}`;
            localStorage.removeItem(pageStorageKey);
        } catch (e) {
            console.error('Error clearing stored answers:', e);
        }
    }
}
export default QuizSubmitHandler;