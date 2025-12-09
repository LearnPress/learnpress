/**
 * Quiz Timer - Live Countdown
 * Handles the countdown timer for quiz duration
 * @since 4.2.9.4
 * @version 2.0.0
 */
import { __ } from '@wordpress/i18n';
import SweetAlert from 'sweetalert2';
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
		this.startTimestamp = null; // Timestamp when timer started (milliseconds)
		this.pausedAt = null; // Track when timer was paused

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
			if (!savedData) {
				return;
			}

			let savedTimeInt, lastSaveTimestamp, savedStartTimestamp;

			// Parse saved data (supports both old and new formats)
			try {
				const parsed = JSON.parse(savedData);
				savedTimeInt = parseInt(parsed.time, 10);
				lastSaveTimestamp = parsed.timestamp || 0;
				savedStartTimestamp = parsed.startTimestamp || null;
			} catch {
				// Old format - just a number
				savedTimeInt = parseInt(savedData, 10);
				lastSaveTimestamp = 0;
				savedStartTimestamp = null;
			}

			// Validate saved data
			if (isNaN(savedTimeInt) || savedTimeInt < 0) {
				this.clearFromLocalStorage();
				return;
			}

			// If we have a saved startTimestamp, calculate the ACTUAL elapsed time
			// This is more reliable than using the saved time value
			if (savedStartTimestamp) {
				const actualElapsedMs = Date.now() - savedStartTimestamp;
				const actualElapsedSeconds = Math.floor(actualElapsedMs / 1000);

				// Sanity check: if calculated time is reasonable, use it
				if (actualElapsedSeconds >= 0 && actualElapsedSeconds < 86400) { // Less than 24 hours
					this.timeRemaining = actualElapsedSeconds;
					this.startTimestamp = savedStartTimestamp;
					return;
				}
			}

			// Fallback: use saved time if no startTimestamp available (backward compatibility)
			// But only if the save was recent (within 1 hour to prevent stale data)
			const timeSinceLastSave = Date.now() - lastSaveTimestamp;
			const ONE_HOUR = 3600000; // 1 hour in milliseconds

			if (savedTimeInt >= 0 && (lastSaveTimestamp === 0 || timeSinceLastSave < ONE_HOUR)) {
				this.timeRemaining = savedTimeInt;
			} else {
				// Data is stale or invalid, clear it
				this.clearFromLocalStorage();
			}
		} catch (e) {
			console.error('Error restoring timer from localStorage:', e);
			this.clearFromLocalStorage();
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

		if (!this.isTimeExpired()) {
			this.hiddenInput.value = this.timeRemaining;

			// Save to localStorage to persist across page navigation
			this.saveToLocalStorage();
		}
	}

	/**
	 * Save timer to localStorage
	 */
	saveToLocalStorage() {
		try {
			const data = {
				time: this.timeRemaining,
				timestamp: Date.now(),
				startTimestamp: this.startTimestamp
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
	async handleExpiration() {
		this.timeDisplay.textContent = '00:00';
		this.timerElement.classList.add('expired');

		// Dispatch custom event for other components
		const expiredEvent = new CustomEvent('lp-quiz-timer-expired', {
			detail: { timeSpent: this.timeRemaining }
		});
		document.dispatchEvent(expiredEvent);

		// Show SweetAlert notification (no confirmation needed - time expired)
		await SweetAlert.fire({
			title: __('Time Expired!', 'learnpress'),
			text: __('Your quiz time has expired. The quiz will be submitted automatically.', 'learnpress'),
			icon: 'warning',
			confirmButtonText: __('OK', 'learnpress'),
			confirmButtonColor: '#3085d6',
			allowOutsideClick: false,
			allowEscapeKey: false,
			timer: 3000,
			timerProgressBar: true
		});

		// Trigger auto-submit by dispatching a custom event to bypass confirmation
		const submitEvent = new CustomEvent('lp-quiz-timer-auto-submit', {
			detail: {
				timeSpent: this.timeRemaining,
				skipConfirmation: true
			}
		});
		document.dispatchEvent(submitEvent);

		// Fallback: click submit button if event handler not available
		const submitButton = document.getElementById('button-submit-quiz');
		if (submitButton && !submitButton.disabled) {
			submitButton.click();
		}
	}

	/**
	 * Timer tick - runs every second
	 * Calculates elapsed time based on timestamps to avoid drift
	 */
	tick() {
		if (!this.startTimestamp) {
			// Fallback: if no timestamp, initialize it now
			this.startTimestamp = Date.now() - (this.timeRemaining * 1000);
		}

		// Calculate actual elapsed time in seconds
		const elapsedMs = Date.now() - this.startTimestamp;
		this.timeRemaining = Math.floor(elapsedMs / 1000);

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

		// Initialize start timestamp if not already set (e.g., from localStorage)
		if (!this.startTimestamp) {
			// Subtract already elapsed time to get the actual start point
			this.startTimestamp = Date.now() - (this.timeRemaining * 1000);
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

export default initQuizTimer;
