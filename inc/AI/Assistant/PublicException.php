<?php

namespace LearnPress\AI\Assistant;

use Exception;

/**
 * Exception whose message is safe to return in a public AJAX response.
 *
 * Used for request validation and access denial only. Everything else thrown
 * inside the assistant stack — OpenAI transport failures, provider payload
 * errors, PHP errors — is logged server-side and replaced with a fixed generic
 * message, so provider detail and internal state never reach the browser.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.4.2
 */
class PublicException extends Exception {
}
