<?php

namespace LearnPress\Models\Webhook;

use Exception;
use LearnPress\Databases\WebhookDB;
use LearnPress\Filters\WebhookFilter;
use LearnPress\Webhook\WebhookEvents;

defined( 'ABSPATH' ) || exit;

/**
 * LearnPress webhook model.
 */
class WebhookModel {
	const STATUS_ACTIVE = 'active';
	const STATUS_PAUSED = 'paused';

	/**
	 * @var int
	 */
	private $webhook_id = 0;

	/**
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * @var string
	 */
	public $status = self::STATUS_ACTIVE;

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $delivery_url = '';

	/**
	 * @var string
	 */
	public $secret = '';

	/**
	 * @var string[]
	 */
	public $events = array();

	/**
	 * @var string
	 */
	public $created_at = '';

	/**
	 * @var string|null
	 */
	public $updated_at;

	/**
	 * Initialize model from a DB row or payload.
	 *
	 * @param array|object|null $data Webhook data.
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}

	/**
	 * Map data onto the model.
	 *
	 * @param array|object $data Webhook data.
	 *
	 * @return self
	 */
	public function map_to_object( $data ): self {
		foreach ( $data as $key => $value ) {
			if ( ! property_exists( $this, $key ) ) {
				continue;
			}

			if ( 'events' === $key ) {
				$value = $this->decode_events( $value );
			} elseif ( in_array( $key, array( 'webhook_id', 'user_id' ), true ) ) {
				$value = absint( $value );
			}

			$this->{$key} = $value;
		}

		return $this;
	}

	/**
	 * Get webhook ID.
	 *
	 * @return int
	 */
	public function get_webhook_id(): int {
		return $this->webhook_id;
	}

	/**
	 * Find a webhook by ID.
	 *
	 * @param int $webhook_id Webhook ID.
	 *
	 * @return static|false
	 * @throws Exception
	 */
	public static function find( int $webhook_id ) {
		$row = WebhookDB::getInstance()->get_webhook( $webhook_id );

		return $row ? new static( $row ) : false;
	}

	/**
	 * Query webhook models.
	 *
	 * @param WebhookFilter $filter     Webhook query filter.
	 * @param int           $total_rows Total matching rows.
	 *
	 * @return static[]
	 * @throws Exception
	 */
	public static function query( WebhookFilter $filter, int &$total_rows = 0 ): array {
		$rows = WebhookDB::getInstance()->get_webhooks( $filter, $total_rows );

		return is_array( $rows ) ? array_map( static fn( $row ) => new static( $row ), $rows ) : array();
	}

	/**
	 * Query active webhooks for an event key.
	 *
	 * @param string $event_key Event key.
	 *
	 * @return static[]
	 * @throws Exception
	 */
	public static function get_active_webhooks_for_event( string $event_key ): array {
		$event_keys = WebhookEvents::sanitize( array( $event_key ) );
		if ( empty( $event_keys ) ) {
			return array();
		}

		$filter                  = new WebhookFilter();
		$filter->status          = self::STATUS_ACTIVE;
		$filter->event           = reset( $event_keys );
		$filter->limit           = -1;
		$filter->run_query_count = false;
		$filter->order_by        = WebhookFilter::COL_WEBHOOK_ID;
		$filter->order           = WebhookFilter::ORDER_ASC;

		return static::query( $filter );
	}

	/**
	 * Save the webhook.
	 *
	 * @return self
	 * @throws Exception
	 */
	public function save(): self {
		$this->validate();

		$db  = WebhookDB::getInstance();
		$now = current_time( 'mysql', true );

		if ( empty( $this->created_at ) ) {
			$this->created_at = $now;
		}

		if ( $this->webhook_id > 0 ) {
			$this->updated_at = $now;
		}

		$data = array(
			'webhook_id'   => $this->webhook_id,
			'user_id'      => $this->user_id,
			'status'       => $this->status,
			'name'         => $this->name,
			'delivery_url' => $this->delivery_url,
			'secret'       => $this->secret,
			'events'       => wp_json_encode( $this->events ),
			'created_at'   => $this->created_at,
			'updated_at'   => $this->updated_at,
		);

		if ( $this->webhook_id > 0 ) {
			$db->update_webhook( $data );
		} else {
			$this->webhook_id = $db->insert_webhook( $data );
			if ( $this->webhook_id <= 0 ) {
				throw new Exception( __( 'Could not create webhook.', 'learnpress' ) );
			}
		}

		return $this;
	}

	/**
	 * Delete the webhook.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function delete(): bool {
		if ( $this->webhook_id <= 0 ) {
			return false;
		}

		return WebhookDB::getInstance()->delete_webhooks( array( $this->webhook_id ) ) > 0;
	}

	/**
	 * Generate and save a new secret.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function regenerate_secret(): string {
		$this->secret = self::generate_secret();
		$this->save();

		return $this->secret;
	}

	/**
	 * Generate a webhook signing secret.
	 *
	 * @return string
	 */
	public static function generate_secret(): string {
		return wp_generate_password( 50, true, true );
	}

	/**
	 * Return admin-safe model data.
	 *
	 * @param bool $include_secret Include raw secret in response.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array( bool $include_secret = false ): array {
		$data = array(
			'webhook_id'   => $this->webhook_id,
			'user_id'      => $this->user_id,
			'status'       => $this->status,
			'name'         => $this->name,
			'delivery_url' => $this->delivery_url,
			'events'       => $this->events,
			'created_at'   => $this->created_at,
			'updated_at'   => $this->updated_at,
		);

		if ( $include_secret ) {
			$data['secret'] = $this->secret;
		}

		return $data;
	}

	/**
	 * Validate and normalize model values.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function validate(): void {
		$this->user_id = absint( $this->user_id );
		$this->name    = sanitize_text_field( $this->name );
		$this->status  = sanitize_key( $this->status );
		$this->events  = WebhookEvents::sanitize( $this->events );

		if ( '' === $this->name ) {
			throw new Exception( __( 'Webhook name is required.', 'learnpress' ) );
		}

		$name_length = function_exists( 'mb_strlen' ) ? mb_strlen( $this->name ) : strlen( $this->name );
		if ( $name_length > 200 ) {
			throw new Exception( __( 'Webhook name must not exceed 200 characters.', 'learnpress' ) );
		}

		$this->delivery_url = esc_url_raw( $this->delivery_url );
		$scheme             = wp_parse_url( $this->delivery_url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! wp_http_validate_url( $this->delivery_url ) ) {
			throw new Exception( __( 'Webhook callback URL is invalid.', 'learnpress' ) );
		}

		if ( ! in_array( $this->status, array( self::STATUS_ACTIVE, self::STATUS_PAUSED ), true ) ) {
			throw new Exception( __( 'Webhook status is invalid.', 'learnpress' ) );
		}

		if ( empty( $this->events ) ) {
			throw new Exception( __( 'Select at least one webhook event.', 'learnpress' ) );
		}

		if ( '' === trim( $this->secret ) || preg_match( '/[\r\n]/', $this->secret ) || strlen( $this->secret ) > 255 ) {
			throw new Exception( __( 'Webhook secret is invalid.', 'learnpress' ) );
		}
	}

	/**
	 * Decode events stored as JSON.
	 *
	 * @param mixed $events Raw events.
	 *
	 * @return string[]
	 */
	protected function decode_events( $events ): array {
		if ( is_string( $events ) ) {
			$events = json_decode( $events, true );
		}

		return is_array( $events ) ? WebhookEvents::sanitize( $events ) : array();
	}
}
