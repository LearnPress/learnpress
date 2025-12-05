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
        this.init();
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

        // Don't prevent default if it's a submit button in a form
        if (button.type !== 'submit') {
            event.preventDefault();
        }

        const template = button.dataset.template;
        const targetSelector = button.closest('.lp-target');

        if (template) {
            this.loadTemplate( targetSelector, button);
        }
    }

    /**
     * Load template via AJAX and replace target container content
     * @param {string} url - URL to fetch template from
     * @param {HTMLElement} targetElement - Target element (must be .lp-target)
     */
    loadTemplate( targetElement, button) {
        if (this.loading) {
            console.warn('Already loading');
            return;
        }

        if (!targetElement) {
            console.error('Target element not found');
            return;
        }

        this.loading = true;
        window.lpAJAXG.showHideLoading(targetElement, 1);

        // Get data from element's dataset
        const dataSendJson = targetElement?.dataset?.send || '{}';
        const dataSend = JSON.parse(dataSendJson);

        if ( button?.dataset?.template ) {
        	const template = button.dataset.template;
        	if ( template === 'review' ) {
        		dataSend.args.is_review = true;
        	} else {
        		dataSend.args.is_review = false;
        	}
        }
        targetElement.dataset.send = JSON.stringify( dataSend );

        // Define callbacks
        const callBack = {
            success: (response) => {
                const { data, message, status } = response;

                if ('success' === status) {
                    this.updateContent(targetElement, data.content || '');
                } else {
                    this.showError(targetElement, message || __('Failed to load content','learnpress'));
                }
            },
            error: (error) => {
                console.error('Template loading error:', error);
                this.showError(targetElement, error);
            },
            completed: () => {
                window.lpAJAXG.showHideLoading(targetElement, 0);
                this.loading = false;
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

        // Trigger custom event for other scripts to hook into
        const event = new CustomEvent('lp-quiz-template-loaded', {
            detail: { target: targetElement, html },
        });
        document.dispatchEvent(event);

        // Scroll to top of target element
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
