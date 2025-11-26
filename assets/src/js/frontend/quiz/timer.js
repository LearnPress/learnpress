/**
 * Quiz Timer - Live Countdown
 * Handles the countdown timer for quiz duration
 * @since 4.2.9.4
 * @version 1.0.0
 */

(function() {
	'use strict';

	/**
	 * Initialize the quiz timer
	 */
	function initQuizTimer() {
		const quizContainer = document.querySelector( '#content-item-quiz' );
		if ( ! quizContainer ) {
			return;
		}
		const timerElement = quizContainer.querySelector('.countdown');
		
		if (!timerElement) {
			return;
		}

		const timeDisplay = timerElement.querySelector('span');
		const hiddenInput = timerElement.querySelector('input[name="lp-quiz-time-spend"]');
		
		if (!timeDisplay || !hiddenInput) {
			return;
		}

		// Get initial values from data attributes or hidden input
		let timeRemaining = parseInt(hiddenInput.value, 10) || 0;
		const duration = parseInt(timerElement.dataset.duration, 10) || 0;
		const totalTime = parseInt(timerElement.dataset.totalTime, 10) || 0;
		
		// Determine if counting up or down
		const isCountdown = duration > 0;
		
		/**
		 * Format seconds to HH:MM:SS or MM:SS
		 * @param {number} seconds - Seconds to format
		 * @param {number} total - Total time for format determination
		 * @return {string} Formatted time string
		 */
		function formatTime(seconds, total) {
			let hours, minutes, secs;
			const separator = ':';
			
			if (total >= 3600) {
				// Show HH:MM:SS for durations >= 1 hour
				hours = Math.floor(seconds / 3600);
				const remainder = seconds % 3600;
				minutes = Math.floor(remainder / 60);
				secs = remainder % 60;
				
				return [hours, minutes, secs]
					.map(val => val < 10 ? '0' + val : val)
					.join(separator);
			} else {
				// Show MM:SS for durations < 1 hour
				minutes = Math.floor(seconds / 60);
				secs = seconds % 60;
				
				return [minutes, secs]
					.map(val => val < 10 ? '0' + val : val)
					.join(separator);
			}
		}
		
		/**
		 * Update the timer display
		 */
		function updateDisplay() {
			const displayTime = isCountdown ? (duration - timeRemaining) : timeRemaining;
			timeDisplay.textContent = formatTime(displayTime, totalTime);
			hiddenInput.value = timeRemaining;
		}
		
		/**
		 * Check if quiz time has expired
		 * @return {boolean} True if time expired
		 */
		function isTimeExpired() {
			if (isCountdown && timeRemaining >= duration) {
				return true;
			}
			return false;
		}
		
		/**
		 * Handle timer expiration
		 */
		function handleExpiration() {
			timeDisplay.textContent = '00:00';
			timerElement.classList.add('expired');
			
			// Trigger auto-submit if available
			const submitButton = document.getElementById('button-submit-quiz');
			if (submitButton && !submitButton.disabled) {
				submitButton.click();
			}
			
			// Dispatch custom event for other components
			const expiredEvent = new CustomEvent('lpQuizTimerExpired', {
				detail: { timeSpent: timeRemaining }
			});
			document.dispatchEvent(expiredEvent);
		}
		
		/**
		 * Timer tick - runs every second
		 */
		function tick() {
			timeRemaining++;
			updateDisplay();
			
			if (isTimeExpired()) {
				clearInterval(timerInterval);
				handleExpiration();
			}
		}
		
		// Initial display update
		updateDisplay();
		
		// Start the countdown/countup
		const timerInterval = setInterval(tick, 1000);
		
		// Clean up on page unload
		window.addEventListener('beforeunload', function() {
			clearInterval(timerInterval);
		});
		
		// Pause timer when quiz is being submitted
		document.addEventListener('lpQuizSubmitting', function() {
			clearInterval(timerInterval);
		});
		
		// Store timer reference for external access if needed
		timerElement.lpTimerInterval = timerInterval;
	}
	
	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initQuizTimer);
	} else {
		initQuizTimer();
	}
})();
