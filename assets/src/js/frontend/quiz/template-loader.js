import { __ } from '@wordpress/i18n';
/**
 * Template Loader for Quiz
 * Handles loading HTML templates via AJAX when pagination links or buttons are clicked
 * 
 * @since 4.2.8.2
 * @version 1.0.0
 */
class TemplateLoader {
    constructor() {
        this.loading = false;
        this.quizId = this.getQuizId();
        this.storageKey = `lp_quiz_current_page_${this.quizId}`;
        this.init();
        this.restoreCurrentPage();
    }

    /**
     * Get the quiz ID from the page
     */
    getQuizId() {
        return window.lpQuizSettings?.id || 0;
    }

    /**
     * Initialize the template loader
     * Bind click events to pagination links and buttons
     */
    init() {
        document.addEventListener('click', (e) => {
            const target = e.target;
            // Handle button clicks with data-template
            if (target.closest('[data-template]')) {
                this.handleButtonClick(e);
            }
            if (target.closest('.quiz-page-numbers:not(.dots):not(.disable):not(.current)')) {
                this.handlePagination(e);
            }
        });
    }

    /**
     * Handle button clicks with data-template attribute
     * @param {Event} event - Click event
     */
    handleButtonClick(event) {
        const button = event.target.closest('[data-template]');
        if (!button) {
            return;
        }
        event.preventDefault();

        const template = button.dataset.template;
        const targetSelector = button.closest('.lp-target');

        if (template) {
            this.loadTemplate(targetSelector, button);
        }
    }
    /**
     * Handle pagination clicks
     * @param {Event} event - Click event
     */
    handlePagination(event) {
        const button = event.target.closest('.quiz-page-numbers:not(.dots):not(.disable):not(.current)');
        if (!button) {
            return;
        }
        event.preventDefault();

        const targetSelector = button.closest('.lp-target');

        if (targetSelector) {
            this.loadTemplate(targetSelector, button);
        }
    }

    /**
     * Load template via AJAX and replace target container content
     * @param {string} url - URL to fetch template from
     * @param {HTMLElement} targetElement - Target element (must be .lp-target)
     */
    loadTemplate(targetElement, button) {
        if (this.loading) {
            console.warn('Already loading');
            return;
        }

        if (!targetElement) {
            console.error('Target element not found');
            return;
        }

        this.loading = true;
        // window.lpAJAXG.showHideLoading(targetElement, 1);
        const loader = document.createElement('div');
        loader.className = 'lp-quiz-loader';
        loader.innerHTML = `<div class="dot"></div> <div class="dot"></div> <div class="dot"></div>`;
        targetElement.appendChild(loader);

        // Get data from element's dataset
        const dataSendJson = targetElement?.dataset?.send || '{}';
        const dataSend = JSON.parse(dataSendJson);

        if (button?.dataset?.template) {
            const template = button.dataset.template;
            if (template === 'review') {
                dataSend.args.is_review = true;
            } else {
                dataSend.args.is_review = false;
            }
        }

        if (button?.dataset?.paged) {
            const paged = button?.dataset?.paged;
            const currentPage = targetElement.querySelector('.quiz-page-numbers.current');
            if (paged === 'next') {
                dataSend.args.paged = parseInt(currentPage?.dataset?.paged) + 1;
            } else if (paged === 'prev') {
                dataSend.args.paged = parseInt(currentPage?.dataset?.paged) - 1;
            } else {
                dataSend.args.paged = parseInt(paged);
            }
        }
        targetElement.dataset.send = JSON.stringify(dataSend);

        // Define callbacks
        const callBack = {
            success: (response) => {
                const { data, message, status } = response;

                if ('success' === status) {
                    this.updateContent(targetElement, data.content || '');
                } else {
                    this.showError(targetElement, message || __('Failed to load content', 'learnpress'));
                }
            },
            error: (error) => {
                console.error('Template loading error:', error);
                this.showError(targetElement, error);
            },
            completed: () => {
                window.lpAJAXG.showHideLoading(targetElement, 0);
                this.loading = false;
                loader.remove();
            },
        };

        // Load template using lpAJAXG
        window.lpAJAXG.fetchAJAX(dataSend, callBack);
    }

    /**
     * Update target element content
     * @param {HTMLElement} targetElement - Target element
     * @param {string} html - HTML content
     */
    updateContent(targetElement, html) {
        targetElement.innerHTML = html;

        // Save current page to localStorage
        this.saveCurrentPage(targetElement);

        // Trigger custom event for other scripts to hook into
        const event = new CustomEvent('lp-quiz-template-loaded', {
            detail: { target: targetElement, html },
        });
        document.dispatchEvent(event);

        // Scroll to top of target element
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /**
     * Save current page to localStorage
     * @param {HTMLElement} targetElement - Target element
     */
    saveCurrentPage(targetElement) {
        try {
            const currentPageElement = targetElement.querySelector('.quiz-page-numbers.current');
            if (currentPageElement && currentPageElement.dataset.paged) {
                const currentPage = parseInt(currentPageElement.dataset.paged);
                localStorage.setItem(this.storageKey, currentPage);
            }
        } catch (error) {
            console.warn('Failed to save current page:', error);
        }
    }

    /**
     * Restore current page from localStorage
     * Loads the saved page if user returns to the quiz
     */
    restoreCurrentPage() {
        // Wait for DOM to be ready and attempt restore
        const attemptRestore = () => {
            try {
                const savedPage = localStorage.getItem(this.storageKey);
                if (!savedPage) {
                    return;
                }

                const targetElement = document.querySelector('.lp-target');
                if (!targetElement) {
                    return;
                }

                // Check quiz status - only restore if quiz is started
                const dataSendJson = targetElement?.dataset?.send || '{}';
                const dataSend = JSON.parse(dataSendJson);
                const quizStatus = dataSend?.args?.status || '';

                // Only restore page if quiz is in "started" status
                if (quizStatus !== 'started') {
                    return;
                }

                // Check if pagination exists
                const currentPageElement = targetElement.querySelector('.quiz-page-numbers.current');
                if (!currentPageElement) {
                    return;
                }

                const currentPage = parseInt(currentPageElement.dataset.paged || 1);
                const pageToLoad = parseInt(savedPage);

                // Only load if saved page is different from current page
                if (pageToLoad !== currentPage && pageToLoad > 0) {

                    // Find the button for the saved page
                    const pageButton = targetElement.querySelector(`.quiz-page-numbers[data-paged="${pageToLoad}"]`);
                    if (pageButton && !pageButton.classList.contains('current')) {
                        // Simulate click on the page button with delay
                        setTimeout(() => {
                            pageButton.click();
                        }, 300);
                    }
                }
            } catch (error) {
                console.warn('Failed to restore current page:', error);
            }
        };

        // Wait for DOM to be ready before attempting restore
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(attemptRestore, 500);
            });
        } else {
            setTimeout(attemptRestore, 500);
        }
    }

    /**
     * Clear saved page from localStorage
     * Call this when quiz is submitted or reset
     */
    clearSavedPage() {
        try {
            localStorage.removeItem(this.storageKey);
        } catch (error) {
            console.warn('Failed to clear saved page:', error);
        }
    }



    /**
     * Show error message
     * @param {HTMLElement} targetElement - Target element
     * @param {Error} error - Error object
     */
    showError(targetElement, error) {
        const errorMessage = `
            <div class="learn-press-message error">
                <p>${__('Failed to load content. Please try again.', 'learnpress')}</p>
            </div>
        `;
        targetElement.insertAdjacentHTML('afterbegin', errorMessage);
    }
}

export default TemplateLoader;
