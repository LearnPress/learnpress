/**
 * Circle Progress Bar Animation
 * Animates the quiz result circle progress bar from 0% to the actual result percentage
 *
 * @since 4.2.9.4
 * @version 1.0.0
 */

class CircleProgressBarAnimation {
    constructor() {
        this.init();
    }

    /**
     * Initialize the animation
     */
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.animateAll());
        } else {
            this.animateAll();
        }

        // Also listen for AJAX content updates (when quiz results are loaded dynamically)
        document.addEventListener('lp-quiz-template-loaded', () => this.animateAll());
    }

    /**
     * Animate all circle progress bars on the page
     */
    animateAll() {
        const circles = document.querySelectorAll('.circle-progress-bar');
        circles.forEach((svg) => this.animate(svg));
    }

    /**
     * Animate a single circle progress bar using CSS transitions for smooth hardware acceleration
     *
     * @param {SVGElement} svg The SVG element containing the circle
     */
    animate(svg) {
        // Get the circle element
        const circle = svg.querySelector('.circle-progress-bar__circle');
        if (!circle) {
            return;
        }

        // Get animation data from SVG data attributes
        const result = parseFloat(svg.dataset.result || 0);
        const circumference = parseFloat(svg.dataset.circumference || 0);
        const targetOffset = parseFloat(svg.dataset.offset || 0);

        // Validate data
        if (!circumference) {
            return;
        }

        // Find the result-achieved element to animate the percentage text
        const resultAchieved = svg.parentElement.querySelector('.result-achieved');
        if (resultAchieved) {
            this.animatePercentageText(resultAchieved, result);
        }

        // Add will-change hint for better performance (tells browser to optimize this property)
        circle.style.willChange = 'stroke-dashoffset';

        // Add CSS transition for ultra-smooth, hardware-accelerated animation
        // Using cubic-bezier(0.25, 0.46, 0.45, 0.94) for gentle easing (easeOutCubic)
        circle.style.transition = 'stroke-dashoffset 1800ms cubic-bezier(0.25, 0.46, 0.45, 0.94)';

        // Force a reflow to ensure the transition applies
        void circle.offsetHeight;

        // Trigger the animation by setting the target offset
        // Use requestAnimationFrame to ensure smooth start
        requestAnimationFrame(() => {
            circle.style.strokeDashoffset = targetOffset;
        });

        // Clean up will-change after animation completes to free up resources
        setTimeout(() => {
            circle.style.willChange = 'auto';
        }, 1800);

        // Add scale animation on completion (like the reference implementation)
        const gradeWrapper = document.getElementById('quizResultGrade');
        if (gradeWrapper) {
            setTimeout(() => {
                gradeWrapper.style.transform = 'scale(1.3)';
                gradeWrapper.style.transition = 'all 0.25s';

                setTimeout(() => {
                    gradeWrapper.style.transform = 'scale(1)';
                }, 250);
            }, 1800);
        }
    }

    /**
     * Animate the percentage text from 0 to target value
     *
     * @param {HTMLElement} element The element containing the percentage text
     * @param {number} targetValue The target percentage value
     */
    animatePercentageText(element, targetValue) {
        const duration = 1800; // Match the circle animation duration (slower for smoother feel)
        const startTime = performance.now();
        const startValue = 0;

        // Check if target is an integer or decimal
        const isInteger = Number.isInteger(targetValue);

        const animateStep = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Use easeOutCubic for gentler, smoother deceleration
            const easedProgress = 1 - Math.pow(1 - progress, 3);
            const currentValue = startValue + (targetValue - startValue) * easedProgress;

            // Format the value
            let displayValue;
            if (isInteger) {
                displayValue = Math.round(currentValue);
            } else {
                displayValue = currentValue.toFixed(2);
            }

            element.textContent = `${displayValue}%`;

            if (progress < 1) {
                requestAnimationFrame(animateStep);
            } else {
                // Ensure final value is exact
                element.textContent = `${isInteger ? targetValue : targetValue.toFixed(2)}%`;
            }
        };

        requestAnimationFrame(animateStep);
    }
}

// Initialize the animation
new CircleProgressBarAnimation();
