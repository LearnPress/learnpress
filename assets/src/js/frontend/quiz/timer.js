/**
 * Quiz Timer - Live Countdown
 * Handles the countdown timer for quiz duration
 * @since 4.2.9.4
 * @version 2.0.0
 */
class QuizTimer {
	constructor() {
		this.quizContainer = null;
		this.timerElement = null;
		this.timeDisplay = null;
		this.hiddenInput = null;
		this.timeRemaining = 0;
		this.duration = 0;
		this.totalTime = 0;
		this.isCountdown = false;
		this.timerStorageKey = '';
		this.timerInterval = null;

		this.init();
	}

	/**
	 * Initialize the timer
	 */
	init() {
		this.quizContainer = document.querySelector('#content-item-quiz');
		if (!this.quizContainer) {
			return;
		}

		this.timerElement = this.quizContainer.querySelector('.countdown');
		if (!this.timerElement) {
			return;
		}

		this.timeDisplay = this.timerElement.querySelector('span');
		this.hiddenInput = this.timerElement.querySelector('input[name="lp-quiz-time-spend"]');

		if (!this.timeDisplay || !this.hiddenInput) {
			return;
		}

		// Get quiz and course IDs for storage key
		const quizId = window.lpQuizSettings?.id || 0;
		const courseId = window.lpGlobalSettings?.post_id || 0;
		this.timerStorageKey = `lp_quiz_timer_${courseId}_${quizId}`;

		// Get initial values from data attributes or hidden input
		this.timeRemaining = parseInt(this.hiddenInput.value, 10) || 0;
		this.duration = parseInt(this.timerElement.dataset.duration, 10) || 0;
		this.totalTime = parseInt(this.timerElement.dataset.totalTime, 10) || 0;

		// Determine if counting up or down
		this.isCountdown = this.duration > 0;

		// Try to restore timeRemaining from localStorage to handle page navigation
		this.restoreFromLocalStorage();

		// Bind events
		this.bindEvents();

		// Initial display update
		this.updateDisplay();

		// Start the countdown/countup
		this.start();

		// Store reference for external access
		this.timerElement.lpTimerInterval = this.timerInterval;
	}

	/**
	 * Restore timer from localStorage
	 */
	restoreFromLocalStorage() {
		try {
			const savedData = localStorage.getItem(this.timerStorageKey);
			if (savedData !== null) {
				// Parse the saved data (could be old format or new format)
				let savedTimeInt, lastSaveTimestamp;

				try {
					const parsed = JSON.parse(savedData);
					savedTimeInt = parseInt(parsed.time, 10);
					lastSaveTimestamp = parsed.timestamp || 0;
				} catch {
					// Old format - just a number
					savedTimeInt = parseInt(savedData, 10);
					lastSaveTimestamp = 0;
				}

				if (isNaN(savedTimeInt)) {
					return;
				}

				// Check if this might be a new quiz attempt
				// Only clear if:
				// 1. Server provided time is 0
				// 2. Saved time is significant (> 5 seconds)
				// 3. Last save was more than 2 seconds ago (not a recent page navigation)
				const timeSinceLastSave = Date.now() - lastSaveTimestamp;
				const isLikelyNewAttempt = this.timeRemaining === 0 &&
					savedTimeInt > 5 &&
					(lastSaveTimestamp === 0 || timeSinceLastSave > 2000);

				if (isLikelyNewAttempt) {
					// This looks like a new quiz attempt, clear the old timer
					this.clearFromLocalStorage();
					return;
				}

				// Use saved time if it's valid
				// Priority: Use saved time if it exists, as server might not provide the current time correctly
				if (savedTimeInt >= 0) {
					this.timeRemaining = savedTimeInt;
				}
			}
		} catch (e) {
			console.error('Error restoring timer from localStorage:', e);
		}
	}

	/**
	 * Bind events
	 */
	bindEvents() {
		// Clean up on page unload
		window.addEventListener('beforeunload', () => {
			this.stop();
		});

		// Pause timer when quiz is being submitted
		document.addEventListener('lp-quiz-submitting', () => {
			this.stop();
		});

		// Clear timer from localStorage when quiz is submitted
		document.addEventListener('lp-quiz-submitted', () => {
			this.clearFromLocalStorage();
		});
	}

	/**
	 * Format seconds to HH:MM:SS or MM:SS
	 * @param {number} seconds - Seconds to format
	 * @param {number} total - Total time for format determination
	 * @return {string} Formatted time string
	 */
	formatTime(seconds, total) {
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
	updateDisplay() {
		const displayTime = this.isCountdown ? (this.duration - this.timeRemaining) : this.timeRemaining;
		this.timeDisplay.textContent = this.formatTime(displayTime, this.totalTime);
		this.hiddenInput.value = this.timeRemaining;

		// Save to localStorage to persist across page navigation
		this.saveToLocalStorage();
	}

	/**
	 * Save timer to localStorage
	 */
	saveToLocalStorage() {
		try {
			const data = {
				time: this.timeRemaining,
				timestamp: Date.now()
			};
			localStorage.setItem(this.timerStorageKey, JSON.stringify(data));
		} catch (e) {
			console.error('Error saving timer to localStorage:', e);
		}
	}

	/**
	 * Clear timer from localStorage
	 */
	clearFromLocalStorage() {
		try {
			localStorage.removeItem(this.timerStorageKey);
		} catch (e) {
			console.error('Error clearing timer from localStorage:', e);
		}
	}

	/**
	 * Check if quiz time has expired
	 * @return {boolean} True if time expired
	 */
	isTimeExpired() {
		if (this.isCountdown && this.timeRemaining >= this.duration) {
			return true;
		}
		return false;
	}

	/**
	 * Handle timer expiration
	 */
	handleExpiration() {
		this.timeDisplay.textContent = '00:00';
		this.timerElement.classList.add('expired');

		// Trigger auto-submit if available
		const submitButton = document.getElementById('button-submit-quiz');
		if (submitButton && !submitButton.disabled) {
			submitButton.click();
		}

		// Dispatch custom event for other components
		const expiredEvent = new CustomEvent('lp-quiz-timer-expired', {
			detail: { timeSpent: this.timeRemaining }
		});
		document.dispatchEvent(expiredEvent);
	}

	/**
	 * Timer tick - runs every second
	 */
	tick() {
		this.timeRemaining++;
		this.updateDisplay();

		if (this.isTimeExpired()) {
			this.stop();
			this.handleExpiration();
		}
	}

	/**
	 * Start the timer
	 */
	start() {
		if (this.timerInterval) {
			this.stop();
		}
		this.timerInterval = setInterval(() => this.tick(), 1000);
	}

	/**
	 * Stop the timer
	 */
	stop() {
		if (this.timerInterval) {
			clearInterval(this.timerInterval);
			this.timerInterval = null;
		}
	}

	/**
	 * Destroy the timer instance
	 */
	destroy() {
		this.stop();
		this.clearFromLocalStorage();

		if (this.timerElement) {
			this.timerElement.lpTimerInterval = null;
		}
	}
}

/**
 * Initialize quiz timer (factory function for backward compatibility)
 */
function initQuizTimer() {
	return new QuizTimer();
}

/**
 * Reload timer when template is loaded via AJAX
 * This ensures timer works after pagination or navigation
 */
document.addEventListener('lp-quiz-template-loaded', function (event) {
	// Use requestAnimationFrame to ensure DOM has been updated
	requestAnimationFrame(() => {
		// Clear any existing timer interval to prevent multiple timers
		const existingTimer = document.querySelector('.countdown');
		if (existingTimer && existingTimer.lpTimerInterval) {
			clearInterval(existingTimer.lpTimerInterval);
		}

		// Reinitialize the timer with new content
		initQuizTimer();
	});
});

/**
 * Reload timer after lpAJAX.clickNumberPage fires
 * Hook into the lp-ajax-pagination-completed action
 * Note: Uses requestAnimationFrame because the hook fires BEFORE innerHTML is updated
 */
if (typeof wp !== 'undefined' && wp.hooks) {
	wp.hooks.addAction('lp-ajax-pagination-completed', 'learnpress/quiz/timer', function (element, dataSend, response) {
		// Wait for the next animation frame to ensure DOM has been updated
		requestAnimationFrame(() => {
			// Check if this is a quiz-related AJAX call
			const countdown = document.querySelector('.countdown');
			if (countdown) {
				// Clear any existing timer interval
				if (countdown.lpTimerInterval) {
					clearInterval(countdown.lpTimerInterval);
				}

				// Reinitialize the timer
				initQuizTimer();
			}
		});
	});
}

export default initQuizTimer;
