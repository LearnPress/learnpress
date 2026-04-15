<?php

namespace LearnPress\AI\Assistant;

/**
 * ResponseNormalizer — decodes JSON model output and extracts human-readable text.
 *
 * Handles the common pattern where OpenAI returns JSON-wrapped text such as
 * {"message":"..."} and extracts the best text candidate for frontend rendering.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class ResponseNormalizer {

	/**
	 * Decode JSON content safely without throwing exceptions.
	 *
	 * Accepts either pure JSON or content containing a JSON object substring.
	 * Returns empty array when content is plain text or invalid JSON.
	 *
	 * @param string $content Raw model content.
	 *
	 * @return array
	 */
	public function decode_json( string $content ): array {
		$content = trim( $content );
		if ( '' === $content ) {
			return array();
		}

		$decoded = json_decode( $content, true );
		if ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) {
			return $decoded;
		}

		$first_brace = strpos( $content, '{' );
		$last_brace  = strrpos( $content, '}' );
		if ( false === $first_brace || false === $last_brace || $last_brace <= $first_brace ) {
			return array();
		}

		$json_slice = substr( $content, $first_brace, $last_brace - $first_brace + 1 );
		if ( ! is_string( $json_slice ) || '' === $json_slice ) {
			return array();
		}

		$decoded = json_decode( $json_slice, true );

		return ( is_array( $decoded ) && JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
	}

	/**
	 * Convert model JSON output into readable assistant text.
	 *
	 * @param string $content Raw model content.
	 *
	 * @return string
	 */
	public function normalize( string $content ): string {
		$content = trim( $content );
		if ( '' === $content ) {
			return '';
		}

		$decoded = $this->decode_json( $content );
		if ( empty( $decoded ) ) {
			if ( $this->looks_like_metadata_value( $content ) ) {
				return __( 'I could not generate a complete answer. Please try rephrasing your request.', 'learnpress' );
			}

			return $content;
		}

		$extracted = $this->extract_text_from_array( $decoded );

		if ( '' !== $extracted ) {
			return $extracted;
		}

		return $this->looks_like_metadata_value( $content )
			? __( 'I could not generate a complete answer. Please try rephrasing your request.', 'learnpress' )
			: $content;
	}

	/**
	 * Build normalized response from assistant content.
	 *
	 * @param string $content The raw assistant text content.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 */
	public function build_response( string $content ): array {
		return array(
			'type'    => 'text',
			'message' => $this->normalize( $content ),
			'quiz'    => null,
		);
	}

	/**
	 * Extract first meaningful text value from a decoded JSON object.
	 *
	 * @param array $data Decoded JSON.
	 *
	 * @return string
	 */
	private function extract_text_from_array( array $data ): string {
		$preferred_keys = array(
			'message',
			'answer',
			'content',
			'summary',
			'explanation',
			'text',
			'response',
			'result',
			'key_points',
			'main_points',
			'bullet_points',
		);

		foreach ( $preferred_keys as $key ) {
			if ( empty( $data[ $key ] ) ) {
				continue;
			}

			$value = $data[ $key ];
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}

			if ( is_array( $value ) ) {
				$nested = $this->extract_text_from_array( $value );
				if ( '' !== $nested ) {
					return $nested;
				}
			}
		}

		foreach ( $data as $key => $value ) {
			if ( $this->is_metadata_text_key( (string) $key ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$nested = $this->extract_text_from_array( $value );
				if ( '' !== $nested ) {
					return $nested;
				}
			}
		}

		foreach ( $data as $key => $value ) {
			if ( $this->is_metadata_text_key( (string) $key ) || ! is_string( $value ) ) {
				continue;
			}

			$candidate = trim( $value );
			if ( '' === $candidate || $this->looks_like_metadata_value( $candidate ) ) {
				continue;
			}

			if ( $this->is_substantial_text( $candidate ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Determine whether a decoded JSON key is metadata and should not be shown as answer text.
	 *
	 * @param string $key JSON key.
	 *
	 * @return bool
	 */
	private function is_metadata_text_key( string $key ): bool {

		$normalized = strtolower( trim( $key ) );
		if ( $normalized === '' ) {
			return false;
		}

		return in_array(
			$normalized,
			array(
				'language',
				'locale',
				'intent',
				'action',
				'type',
				'status',
				'model',
				'provider',
				'metadata',
			),
			true
		);
	}

	/**
	 * Check whether a text candidate looks like metadata output.
	 *
	 * @param string $value Text candidate.
	 *
	 * @return bool
	 */
	private function looks_like_metadata_value( string $value ): bool {

		$trimmed = trim( $value );
		if ( $trimmed === '' ) {
			return true;
		}

		if ( preg_match( '/^(language|locale|intent|action|type|status)\s*[:=]\s*[\w\-]+$/ui', $trimmed ) ) {
			return true;
		}

		return preg_match( '/^[a-z]{2}[-_][a-z]{2}$/i', $trimmed ) === 1;
	}

	/**
	 * Determine whether candidate text is substantial enough for user-facing response.
	 *
	 * @param string $value Text candidate.
	 *
	 * @return bool
	 */
	private function is_substantial_text( string $value ): bool {

		$trimmed = trim( $value );
		if ( $trimmed === '' ) {
			return false;
		}

		if ( preg_match( '/[.!?。！？]/u', $trimmed ) ) {
			return true;
		}

		$length = mb_strlen( $trimmed, 'UTF-8' );
		if ( $length >= 20 ) {
			return true;
		}

		return $length >= 14 && preg_match( '/\s/u', $trimmed ) === 1;
	}
}
