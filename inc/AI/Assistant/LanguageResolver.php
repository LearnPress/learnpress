<?php

namespace LearnPress\AI\Assistant;

/**
 * LanguageResolver — detects learner language and generates instruction for the model.
 *
 * Completely stateless utility; can be reused across Agent and QuickQuizEngine.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class LanguageResolver {

	/**
	 * Build strict language guidance so the model replies in the learner's language.
	 *
	 * @param string $user_message Current learner input.
	 * @param array  $history      Conversation history.
	 * @param int    $user_id      Current user ID.
	 *
	 * @return string
	 */
	public function build_instruction( string $user_message, array $history, int $user_id ): string {

		$sample      = $this->resolve_language_sample( $user_message, $history );
		$locale_hint = $this->resolve_locale_hint( $user_id );

		if ( $sample !== '' ) {
			return sprintf(
				/* translators: 1: learner message sample, 2: locale hint. */
				__( 'Language policy: reply strictly in the same language as the learner. Do not default to English. Learner sample: "%1$s". Locale fallback: %2$s.', 'learnpress' ),
				$sample,
				$locale_hint !== '' ? $locale_hint : 'n/a'
			);
		}

		return sprintf(
			/* translators: %s: locale hint. */
			__( 'Language policy: reply strictly in the learner language and do not default to English. If the latest learner message is language-neutral, use locale fallback: %s.', 'learnpress' ),
			$locale_hint !== '' ? $locale_hint : 'n/a'
		);
	}

	// ----------------------------------------------------------------
	// Private helpers
	// ----------------------------------------------------------------

	/**
	 * Pick the best text sample to infer learner language.
	 *
	 * Prefers the current message; falls back to previous user turns.
	 *
	 * @param string $user_message Current learner input.
	 * @param array  $history      Conversation history.
	 *
	 * @return string
	 */
	private function resolve_language_sample( string $user_message, array $history ): string {

		$current = $this->trim_text_for_prompt( $user_message );
		if ( $this->has_letters( $current ) && ! $this->is_language_neutral_command( $current ) ) {
			return $current;
		}

		for ( $index = count( $history ) - 1; $index >= 0; $index-- ) {
			$item = $history[ $index ] ?? array();
			if ( ( $item['role'] ?? '' ) !== 'user' || ! isset( $item['content'] ) ) {
				continue;
			}

			$content = $this->trim_text_for_prompt( (string) $item['content'] );
			if ( ! $this->has_letters( $content ) || $this->is_language_neutral_command( $content ) ) {
				continue;
			}

			return $content;
		}

		return '';
	}

	/**
	 * Resolve locale hint from WordPress user/site settings.
	 *
	 * @param int $user_id Current user ID.
	 *
	 * @return string
	 */
	private function resolve_locale_hint( int $user_id ): string {
		$locale = (string) get_user_locale( $user_id );
		if ( $locale !== '' ) {
			return $locale;
		}

		$locale = (string) determine_locale();
		if ( $locale !== '' ) {
			return $locale;
		}

		return (string) get_locale();
	}

	/**
	 * Determine if input is a language-neutral command (e.g. /quick-quiz, "1", "A", ...).
	 *
	 * @param string $text Input text.
	 *
	 * @return bool
	 */
	private function is_language_neutral_command( string $text ): bool {

		$normalized = trim( $text );
		if ( $normalized === '' ) {
			return true;
		}

		if ( str_starts_with( $normalized, '/' ) ) {
			return true;
		}

		return preg_match( '/^[\d\W_]+$/u', $normalized ) === 1;
	}

	/**
	 * Check whether input contains at least one letter character.
	 *
	 * @param string $text Input text.
	 *
	 * @return bool
	 */
	private function has_letters( string $text ): bool {
		return preg_match( '/\p{L}/u', $text ) === 1;
	}

	/**
	 * Trim and cap text length for safe system prompt injection.
	 *
	 * @param string $text Input text.
	 *
	 * @return string
	 */
	private function trim_text_for_prompt( string $text ): string {

		$clean = trim( preg_replace( '/\s+/', ' ', $text ) ?? '' );
		if ( $clean === '' ) {
			return '';
		}

		return mb_substr( $clean, 0, 220, 'UTF-8' );
	}
}
