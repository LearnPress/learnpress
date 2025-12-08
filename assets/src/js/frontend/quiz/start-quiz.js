import { __ } from '@wordpress/i18n';
import { show as showMessage } from '../../lpToastify.js';
import SweetAlert from 'sweetalert2';
/**
 * Quiz Start Handler Class
 */
class QuizStartHandler {
    constructor() {
        this.starting = false;
        this.init();
    }

    /**
     * Initialize the handler
     */
    init() {
        this.bindEvents();
    }

    /**
     * Bind click events to start quiz button
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.lp-button.start') || e.target.closest('#button-start-quiz')) {
                this.handleStartClick(e);
            }
        });
    }

    /**
     * Handle start button click
     * @param {Event} e - Click event
     */
    async handleStartClick(e) {
        e.preventDefault();

        const button = e.target.closest('.lp-button.start') || e.target.closest('#button-start-quiz');

        // Check if already starting
        if (this.starting) {
            return;
        }

        // Check if this is a retake button and confirm with user
        if (button.classList.contains('retake')) {
            const confirmed = await this.confirmRetake();
            if (!confirmed) {
                return;
            }
        }

        // Apply filter hook before starting quiz
        // Deprecated 4.3.1
        /*if (window.LP && window.LP.Hook) {
            const itemId = window.lpQuizSettings?.id || 0;
            const courseId = window.lpGlobalSettings?.post_id || 0;
            const doStart = window.LP.Hook.applyFilters('before-start-quiz', true, itemId, courseId);

            if (doStart !== true) {
                return;
            }
        }*/

        // Set starting state
        this.starting = true;
        button.classList.add('loading');
        button.disabled = true;

        // Start quiz
        this.startQuiz()
            .then((response) => {
                this.handleStartSuccess(response, button);
            })
            .catch((error) => {
                this.handleStartError(error, button);
            })
            .finally(() => {
                this.starting = false;
                button.classList.remove('loading');
                button.disabled = false;
            });
    }

    /**
     * Confirm quiz retake with user using SweetAlert
     * @returns {Promise<boolean>} True if user confirms
     */
    async confirmRetake() {
        const result = await SweetAlert.fire({
            title: __('Retake Quiz?', 'learnpress'),
            text: __('Are you sure you want to retake the quiz?', 'learnpress'),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: __('Retake', 'learnpress'),
            cancelButtonText: __('Cancel', 'learnpress'),
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        });

        return result.isConfirmed;
    }

    /**
     * Start quiz via API
     * @returns {Promise} API request promise
     */
    startQuiz() {
        const itemId = window.lpQuizSettings?.id || 0;
        const courseId = window.lpGlobalSettings?.post_id || 0;

        const url = (window.lpData?.lp_rest_url || '') + 'lp/v1/users/start-quiz';

        const body = new FormData();
        body.append('item_id', itemId);
        body.append('course_id', courseId);

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
     * Handle successful quiz start
     * @param {Object} response - API response
     * @param {Element} button - Start button element
     */
    handleStartSuccess(response, button) {
        if (response.status !== 'error') {
            // console.log('Quiz started successfully:', response);

            // Apply filter hook after start response
            let filteredResponse = response;
            // Deprecated 4.3.1
            /*if (window.LP && window.LP.Hook) {
                const itemId = window.lpQuizSettings?.id || 0;
                const courseId = window.lpGlobalSettings?.post_id || 0;
                filteredResponse = window.LP.Hook.applyFilters('request-start-quiz-response', response, itemId, courseId);
            }*/

            const { results } = filteredResponse;

            if (results) {
                const { duration, status, question_ids, questions } = results;

                // Handle non-enrolled quiz (offline mode)
                if (window.lpQuizSettings?.checkNorequizenroll === 1) {
                    const keyQuizOff = 'quiz_off_' + window.lpQuizSettings.id;
                    window.localStorage.removeItem(keyQuizOff);

                    const quizDataOff = {
                        endTime: (Date.now() + (duration * 1000)),
                        status,
                        question_ids,
                        questions
                    };

                    window.localStorage.setItem(keyQuizOff, JSON.stringify(quizDataOff));

                    // Set retake count
                    const keyQuizOffRetaken = 'quiz_off_retaken_' + window.lpQuizSettings.id;
                    let quizOffRetaken = window.localStorage.getItem(keyQuizOffRetaken);

                    if (quizOffRetaken === null) {
                        quizOffRetaken = 0;
                    } else {
                        quizOffRetaken = parseInt(quizOffRetaken) + 1;
                    }

                    window.localStorage.setItem(keyQuizOffRetaken, quizOffRetaken);
                }
            }

            // Trigger custom event
            // Deprecated 4.3.1
            /*if (window.LP && window.LP.Hook) {
                const itemId = window.lpQuizSettings?.id || 0;
                const courseId = window.lpGlobalSettings?.post_id || 0;
                window.LP.Hook.doAction('quiz-started', results, itemId, courseId);
            }*/

            // Clear general localStorage
            window.localStorage.removeItem('LP');

            // Clear saved current page for fresh start
            const quizId = window.lpQuizSettings?.id || 0;
            const pageStorageKey = `lp_quiz_current_page_${quizId}`;
            window.localStorage.removeItem(pageStorageKey);

            // Reload page to show quiz
            window.location.reload();
        } else {
            // Handle error response
            const elButtons = document.querySelector('.quiz-buttons');
            if (elButtons) {
                const message = `<div class="learn-press-message error">${response.message}</div>`;
                elButtons.insertAdjacentHTML('afterend', message);
            }
            button.classList.remove('loading');
        }
    }

    /**
     * Handle quiz start error
     * @param {Object} error - Error object
     * @param {Element} button - Start button element
     */
    handleStartError(error, button) {
        console.error('Quiz start error:', error);
        showMessage(__('Failed to start quiz. Please try again.', 'learnpress'), 'error');
        button.classList.remove('loading');
    }
}

export default QuizStartHandler;