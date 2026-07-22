<?php

namespace LearnPress\Webhook;

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use LP_User_Items_Result_DB;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Builds versioned, enriched webhook payload data while keeping legacy fields intact.
 */
class WebhookResourceSerializer {
	const API_VERSION = 'v1';

	/**
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected $post_snapshots = array();

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected $user_snapshots = array();

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected $order_snapshots = array();

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Build the final webhook envelope before filters and HMAC signing.
	 *
	 * @param string $event_key Event key.
	 * @param string $delivery_id Delivery ID.
	 * @param array  $data Legacy event data.
	 *
	 * @return array<string, mixed>
	 */
	public function build_payload( string $event_key, string $delivery_id, array $data ): array {
		return array(
			'id'          => $delivery_id,
			'event'       => $event_key,
			'api_version' => self::API_VERSION,
			'created_at'  => gmdate( 'c' ),
			'site_url'    => home_url( '/' ),
			'data'        => $this->enrich_data( $event_key, $data ),
		);
	}

	/**
	 * Enrich legacy data with human-readable names and compact resource snapshots.
	 *
	 * @param string $event_key Event key.
	 * @param array  $data Legacy event data.
	 *
	 * @return array<string, mixed>
	 */
	public function enrich_data( string $event_key, array $data ): array {
		unset( $event_key );

		$data = $this->enrich_primary_ids( $data );
		$data = $this->enrich_order_data( $data );
		$data = $this->enrich_user_item_data( $data );
		$data = $this->enrich_announcement_data( $data );
		$data = $this->enrich_membership_data( $data );
		$data = $this->enrich_instructor_data( $data );

		return $data;
	}

	/**
	 * Add common resource aliases for top-level *_id fields.
	 *
	 * @param array $data Payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function enrich_primary_ids( array $data ): array {
		if ( array_key_exists( 'user_id', $data ) ) {
			$this->add_user_aliases( $data, $data['user_id'] );
		}

		if ( array_key_exists( 'course_id', $data ) ) {
			$this->add_post_aliases( $data, 'course', $data['course_id'], $this->course_post_type() );
		}

		if ( array_key_exists( 'course_ids', $data ) && is_array( $data['course_ids'] ) ) {
			$course_ids = $this->normalize_ids( $data['course_ids'] );
			$courses    = array();
			$names      = array();

			foreach ( $course_ids as $course_id ) {
				$course = $this->get_post_snapshot( $course_id, $this->course_post_type() );
				if ( $course['name'] !== '' ) {
					$names[] = $course['name'];
				}
				$courses[] = $course;
			}

			$this->set_if_missing( $data, 'course_names', $names );
			$this->set_if_missing( $data, 'courses', $courses );
		}

		if ( array_key_exists( 'item_id', $data ) ) {
			$this->add_post_aliases( $data, 'item', $data['item_id'], (string) ( $data['item_type'] ?? '' ) );
		}

		if ( array_key_exists( 'ref_id', $data ) ) {
			$ref_type = (string) ( $data['ref_type'] ?? '' );
			if ( $this->is_order_type( $ref_type ) ) {
				$ref = $this->get_order_snapshot( absint( $data['ref_id'] ) );
				$this->set_if_missing( $data, 'ref_name', $ref['title'] );
				$this->set_if_missing( $data, 'ref', $ref );
			} else {
				$this->add_post_aliases( $data, 'ref', $data['ref_id'], $ref_type );
			}
		}

		if ( array_key_exists( 'assignment_id', $data ) ) {
			$this->add_post_aliases( $data, 'assignment', $data['assignment_id'], 'lp_assignment' );
		}

		if ( array_key_exists( 'announcement_id', $data ) ) {
			$this->add_post_aliases( $data, 'announcement', $data['announcement_id'], 'lp_announcement' );
		}

		if ( array_key_exists( 'plan_id', $data ) ) {
			$this->add_post_aliases( $data, 'plan', $data['plan_id'], '' );
		}

		if ( array_key_exists( 'order_id', $data ) ) {
			$order = $this->get_order_snapshot( absint( $data['order_id'] ) );
			$this->set_if_missing( $data, 'order_number', $order['number'] );
			$this->set_if_missing( $data, 'order_title', $order['title'] );
			$this->set_if_missing( $data, 'order', $order );
		}

		if ( array_key_exists( 'renew_order_id', $data ) ) {
			$renew_order = $this->get_order_snapshot( absint( $data['renew_order_id'] ) );
			$this->set_if_missing( $data, 'renew_order_number', $renew_order['number'] );
			$this->set_if_missing( $data, 'renew_order', $renew_order );
		}

		if ( array_key_exists( 'author_id', $data ) ) {
			$user = $this->get_user_snapshot( absint( $data['author_id'] ) );
			$this->set_if_missing( $data, 'author_name', $user['name'] );
			$this->set_if_missing( $data, 'author', $user );
		}

		return $data;
	}

	/**
	 * Add order item names and order/customer snapshots.
	 *
	 * @param array $data Payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function enrich_order_data( array $data ): array {
		if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
			$order_item_names = array();

			foreach ( $data['items'] as $index => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$item_id   = absint( $item['item_id'] ?? 0 );
				$item_type = (string) ( $item['item_type'] ?? '' );
				$snapshot  = $this->get_post_snapshot( $item_id, $item_type );
				$name      = (string) ( $item['order_item_name'] ?? '' );
				if ( '' === $name ) {
					$name = $snapshot['name'];
				}

				$this->set_if_missing( $item, 'order_item_name', $name );
				$this->set_if_missing( $item, 'item_name', $snapshot['name'] );
				$this->set_if_missing( $item, 'item', $snapshot );

				if ( '' !== $name ) {
					$order_item_names[] = $name;
				}

				$data['items'][ $index ] = $item;
			}

			$this->set_if_missing( $data, 'order_item_names', $order_item_names );
		}

		if ( isset( $data['order'] ) && is_array( $data['order'] ) ) {
			$order = $data['order'];

			if ( isset( $order['users'] ) && is_array( $order['users'] ) ) {
				foreach ( $order['users'] as $index => $user ) {
					if ( ! is_array( $user ) ) {
						continue;
					}

					$user_id  = absint( $user['id'] ?? $user['user_id'] ?? 0 );
					$snapshot = $this->get_user_snapshot( $user_id );
					$this->set_if_missing( $user, 'name', $snapshot['name'] );
					$this->set_if_missing( $user, 'display_name', $snapshot['display_name'] );
					$order['users'][ $index ] = $user;
				}
			}

			if ( isset( $order['customer'] ) && is_array( $order['customer'] ) ) {
				$customer = $order['customer'];
				$user_id  = absint( $customer['id'] ?? $customer['user_id'] ?? 0 );
				if ( $user_id > 0 ) {
					$snapshot = $this->get_user_snapshot( $user_id );
					$this->set_if_missing( $customer, 'name', $snapshot['name'] );
					$this->set_if_missing( $customer, 'display_name', $snapshot['display_name'] );
				}
				$order['customer'] = $customer;
			}

			$data['order'] = $order;
		}

		return $data;
	}

	/**
	 * Add user item names, snapshots, result, and course progress when applicable.
	 *
	 * @param array $data Payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function enrich_user_item_data( array $data ): array {
		if ( ! isset( $data['user_item'] ) || ! is_array( $data['user_item'] ) ) {
			return $data;
		}

		$user_item = $data['user_item'];
		$user_id   = absint( $user_item['user_id'] ?? $data['user_id'] ?? 0 );
		$item_id   = absint( $user_item['item_id'] ?? $data['item_id'] ?? 0 );
		$item_type = (string) ( $user_item['item_type'] ?? $data['item_type'] ?? '' );
		$ref_id    = absint( $user_item['ref_id'] ?? $data['ref_id'] ?? 0 );
		$ref_type  = (string) ( $user_item['ref_type'] ?? $data['ref_type'] ?? '' );
		$course_id = absint( $data['course_id'] ?? $user_item['course_id'] ?? 0 );

		if ( ! $course_id ) {
			if ( $item_type === $this->course_post_type() ) {
				$course_id = $item_id;
			} elseif ( $ref_type === $this->course_post_type() ) {
				$course_id = $ref_id;
			}
		}

		if ( $course_id > 0 ) {
			$this->set_if_missing( $data, 'course_id', $course_id );
			$this->set_if_missing( $user_item, 'course_id', $course_id );
		}

		$user_snapshot   = $this->get_user_snapshot( $user_id );
		$item_snapshot   = $this->get_post_snapshot( $item_id, $item_type );
		$course_snapshot = $this->get_post_snapshot( $course_id, $this->course_post_type() );
		$ref_snapshot    = $this->is_order_type( $ref_type ) ? $this->get_order_snapshot( $ref_id ) : $this->get_post_snapshot( $ref_id, $ref_type );

		$this->set_if_missing( $data, 'user_name', $user_snapshot['name'] );
		$this->set_if_missing( $data, 'item_name', $item_snapshot['name'] );
		$this->set_if_missing( $data, 'course_name', $course_snapshot['name'] );
		$this->set_if_missing( $data, 'ref_name', (string) ( $ref_snapshot['name'] ?? $ref_snapshot['title'] ?? '' ) );

		$this->set_if_missing( $user_item, 'user_name', $user_snapshot['name'] );
		$this->set_if_missing( $user_item, 'item_name', $item_snapshot['name'] );
		$this->set_if_missing( $user_item, 'course_name', $course_snapshot['name'] );
		$this->set_if_missing( $user_item, 'ref_name', (string) ( $ref_snapshot['name'] ?? $ref_snapshot['title'] ?? '' ) );
		$this->set_if_missing( $user_item, 'learning_status', sanitize_key( (string) ( $user_item['status'] ?? '' ) ) );
		$this->set_if_missing( $user_item, 'started_at', $this->sanitize_text( (string) ( $user_item['start_time'] ?? '' ) ) );
		$this->set_if_missing( $user_item, 'ended_at', $this->sanitize_text( (string) ( $user_item['end_time'] ?? '' ) ) );
		$this->set_if_missing( $user_item, 'duration_seconds', $this->duration_seconds( (string) ( $user_item['start_time'] ?? '' ), (string) ( $user_item['end_time'] ?? '' ) ) );
		$this->set_if_missing( $user_item, 'user', $user_snapshot );
		$this->set_if_missing( $user_item, 'item', $item_snapshot );
		$this->set_if_missing( $user_item, 'course', $course_snapshot );
		$this->set_if_missing( $user_item, 'ref', $ref_snapshot );
		if ( isset( $user_item['result'] ) && is_array( $user_item['result'] ) ) {
			$user_item['result'] = $this->strip_sensitive_result_fields( $user_item['result'] );
		} else {
			$user_item['result'] = $this->get_user_item_result( $user_item );
		}

		if ( $item_type === $this->course_post_type() && $course_id > 0 ) {
			$this->set_if_missing( $user_item, 'progress', $this->get_course_progress( $user_id, $course_id ) );
		}

		$data['user_item'] = $user_item;

		return $data;
	}

	/**
	 * Add announcement names and course names.
	 *
	 * @param array $data Payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function enrich_announcement_data( array $data ): array {
		if ( ! isset( $data['announcement_id'] ) ) {
			return $data;
		}

		$announcement = $this->get_post_snapshot( absint( $data['announcement_id'] ), 'lp_announcement' );
		$this->set_if_missing( $data, 'announcement_name', $announcement['name'] );
		$this->set_if_missing( $data, 'announcement', $announcement );

		return $data;
	}

	/**
	 * Add membership aliases from IDs and safe gateway data.
	 *
	 * @param array $data Payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function enrich_membership_data( array $data ): array {
		if ( isset( $data['webhook_data'] ) && is_array( $data['webhook_data'] ) ) {
			$member_status = $data['webhook_data']['member_status'] ?? $data['webhook_data']['status'] ?? null;
			if ( null !== $member_status ) {
				$this->set_if_missing( $data, 'member_status', sanitize_key( (string) $member_status ) );
			}

			if ( isset( $data['webhook_data']['course_ids'] ) && is_array( $data['webhook_data']['course_ids'] ) ) {
				$this->set_if_missing( $data, 'course_ids', $data['webhook_data']['course_ids'] );
				$data = $this->enrich_primary_ids( $data );
			}
		}

		return $data;
	}

	/**
	 * Add user_name to instructor events when a user or request name is available.
	 *
	 * @param array $data Payload data.
	 *
	 * @return array<string, mixed>
	 */
	protected function enrich_instructor_data( array $data ): array {
		if ( isset( $data['user_name'] ) ) {
			return $data;
		}

		if ( ! empty( $data['user_id'] ) ) {
			$user = $this->get_user_snapshot( absint( $data['user_id'] ) );
			$this->set_if_missing( $data, 'user_name', $user['name'] );
		} elseif ( ! empty( $data['name'] ) ) {
			$this->set_if_missing( $data, 'user_name', $this->sanitize_text( (string) $data['name'] ) );
		}

		return $data;
	}

	/**
	 * Add user aliases to payload data.
	 *
	 * @param array $data Payload data by reference.
	 * @param mixed $user_id User ID or list of IDs.
	 *
	 * @return void
	 */
	protected function add_user_aliases( array &$data, $user_id ): void {
		if ( is_array( $user_id ) ) {
			$users = array();
			$names = array();
			foreach ( $this->normalize_ids( $user_id ) as $id ) {
				$user    = $this->get_user_snapshot( $id );
				$users[] = $user;
				if ( '' !== $user['name'] ) {
					$names[] = $user['name'];
				}
			}

			$this->set_if_missing( $data, 'users', $users );
			$this->set_if_missing( $data, 'user_names', $names );
			return;
		}

		$user = $this->get_user_snapshot( absint( $user_id ) );
		$this->set_if_missing( $data, 'user_name', $user['name'] );
		$this->set_if_missing( $data, 'user', $user );
	}

	/**
	 * Add post-like resource aliases to payload data.
	 *
	 * @param array  $data Payload data by reference.
	 * @param string $prefix Alias prefix.
	 * @param mixed  $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return void
	 */
	protected function add_post_aliases( array &$data, string $prefix, $post_id, string $post_type = '' ): void {
		$post_id = absint( $post_id );
		$post    = $this->get_post_snapshot( $post_id, $post_type );

		$this->set_if_missing( $data, "{$prefix}_name", $post['name'] );
		$this->set_if_missing( $data, $prefix, $post );
	}

	/**
	 * Return a compact user snapshot.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_user_snapshot( int $user_id ): array {
		if ( isset( $this->user_snapshots[ $user_id ] ) ) {
			return $this->user_snapshots[ $user_id ];
		}

		$name = '';
		$user = false;

		$user = $user_id > 0 ? get_userdata( $user_id ) : false;

		if ( is_object( $user ) ) {
			$name = $this->sanitize_text( (string) ( $user->display_name ?? $user->user_login ?? '' ) );
		}

		$snapshot = array(
			'id'           => $user_id,
			'type'         => 'user',
			'name'         => $name,
			'display_name' => $name,
		);

		$this->user_snapshots[ $user_id ] = $snapshot;

		return $snapshot;
	}

	/**
	 * Return a compact post/resource snapshot.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type hint.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_post_snapshot( int $post_id, string $post_type = '' ): array {
		if ( $post_id <= 0 ) {
			return array(
				'id'        => 0,
				'type'      => $post_type,
				'name'      => '',
				'title'     => '',
				'slug'      => '',
				'permalink' => '',
			);
		}

		$key = "{$post_id}:{$post_type}";
		if ( isset( $this->post_snapshots[ $key ] ) ) {
			return $this->post_snapshots[ $key ];
		}

		$post = get_post( $post_id );
		$type = $post_type;
		if ( is_object( $post ) && ! empty( $post->post_type ) ) {
			$type = (string) $post->post_type;
		} elseif ( '' === $type ) {
			$type = (string) get_post_type( $post_id );
		}

		$title     = is_object( $post ) && isset( $post->post_title ) ? $this->sanitize_text( (string) $post->post_title ) : '';
		$slug      = is_object( $post ) && isset( $post->post_name ) ? $this->sanitize_key_string( (string) $post->post_name ) : '';
		$permalink = get_permalink( $post_id );
		$link      = is_string( $permalink ) ? $permalink : '';

		$snapshot = array(
			'id'        => $post_id,
			'type'      => $this->sanitize_key_string( $type ),
			'name'      => $title,
			'title'     => $title,
			'slug'      => $slug,
			'permalink' => esc_url_raw( $link ),
		);

		$this->post_snapshots[ $key ] = $snapshot;

		return $snapshot;
	}

	/**
	 * Return a compact order snapshot.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_order_snapshot( int $order_id ): array {
		if ( isset( $this->order_snapshots[ $order_id ] ) ) {
			return $this->order_snapshots[ $order_id ];
		}

		$order  = $order_id > 0 ? learn_press_get_order( $order_id ) : null;
		$post   = $this->get_post_snapshot( $order_id, 'lp_order' );
		$number = (string) $order_id;
		$title  = $post['title'];
		$status = '';

		if ( is_object( $order ) ) {
			$number = $this->sanitize_text( (string) $this->call_method( $order, 'get_order_number', $number ) );
			$title  = $this->sanitize_text( (string) $this->call_method( $order, 'get_title', $title ) );
			$status = $this->sanitize_key_string( (string) $this->call_method( $order, 'get_status', '' ) );
		}

		$snapshot = array_merge(
			$post,
			array(
				'type'   => '' !== $post['type'] ? $post['type'] : 'lp_order',
				'number' => $number,
				'title'  => $title,
				'name'   => $title,
				'status' => $status,
			)
		);

		$this->order_snapshots[ $order_id ] = $snapshot;

		return $snapshot;
	}

	/**
	 * Return safe result data for a user item.
	 *
	 * @param array $user_item User item data.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_user_item_result( array $user_item ): array {
		$user_item_id = absint( $user_item['user_item_id'] ?? 0 );
		$item_type    = (string) ( $user_item['item_type'] ?? '' );
		$result       = array();

		if ( $item_type === $this->course_post_type() ) {
			$user_course = $this->get_user_course_model( absint( $user_item['user_id'] ?? 0 ), absint( $user_item['item_id'] ?? 0 ) );
			if ( is_object( $user_course ) && method_exists( $user_course, 'calculate_course_results' ) ) {
				$result = $this->call_method( $user_course, 'calculate_course_results', array() );
			}
		} elseif ( $item_type === $this->quiz_post_type() ) {
			$quiz = $this->get_user_item_model( $user_item );
			if ( $quiz instanceof UserQuizModel ) {
				$result = $this->call_method( $quiz, 'get_result', array() );
			}
		}

		if ( empty( $result ) && $user_item_id > 0 ) {
			$result_from_db = LP_User_Items_Result_DB::instance()->get_result( $user_item_id );
			$result         = is_array( $result_from_db ) ? $result_from_db : array();
		}

		return is_array( $result ) ? $this->strip_sensitive_result_fields( $result ) : array();
	}

	/**
	 * Return full course progress with safe curriculum item data.
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_course_progress( int $user_id, int $course_id ): array {
		$progress = array(
			'sections' => array(),
		);

		$course = $this->get_course_model( $course_id );
		if ( ! is_object( $course ) ) {
			return $progress;
		}

		$user_course = $this->get_user_course_model( $user_id, $course_id );
		$user_items  = $this->get_course_user_items_map( $user_course, $user_id );
		$sections    = $this->call_method( $course, 'get_section_items', array() );
		if ( empty( $sections ) ) {
			$sections = $this->call_method( $course, 'get_full_sections_and_items_course', array() );
		}
		if ( ! is_array( $sections ) ) {
			return $progress;
		}

		foreach ( $sections as $section ) {
			$section_items = is_object( $section ) && isset( $section->items ) && is_array( $section->items ) ? $section->items : array();
			$items         = array();

			foreach ( $section_items as $item ) {
				$item_id        = absint( $item->item_id ?? $item->id ?? 0 );
				$item_type      = (string) ( $item->item_type ?? $item->type ?? '' );
				$item_snapshot  = $this->get_post_snapshot( $item_id, $item_type );
				$item_user_item = array();
				$attend         = $user_items[ $item_id ] ?? null;

				if ( ! is_object( $attend ) && is_object( $user_course ) && method_exists( $user_course, 'get_item_attend' ) ) {
					$attend = $this->call_method( $user_course, 'get_item_attend', false, array( $item_id, $item_type ) );
				}

				if ( is_object( $attend ) ) {
					$item_user_item = $this->normalize_user_item_object( $attend );
				}

				$item_result = ! empty( $item_user_item ) ? $this->get_user_item_result( $item_user_item ) : array();
				$status      = sanitize_key( (string) ( $item_user_item['status'] ?? 'not-started' ) );

				$items[] = array(
					'id'              => $item_id,
					'type'            => $item_snapshot['type'],
					'name'            => $item_snapshot['name'],
					'title'           => $item_snapshot['title'],
					'status'          => $status,
					'learning_status' => $status,
					'user_item'       => $item_user_item,
					'result'          => $item_result,
				);
			}

			$section_name           = $this->sanitize_text( (string) ( $section->section_name ?? $section->title ?? '' ) );
			$progress['sections'][] = array(
				'id'          => absint( $section->section_id ?? $section->id ?? 0 ),
				'section_id'  => absint( $section->section_id ?? $section->id ?? 0 ),
				'name'        => $section_name,
				'title'       => $section_name,
				'order'       => absint( $section->section_order ?? $section->order ?? 0 ),
				'description' => $this->sanitize_text( (string) ( $section->section_description ?? $section->description ?? '' ) ),
				'items'       => $items,
			);
		}

		return $progress;
	}

	/**
	 * Load child user items of a course in one DB call when possible.
	 *
	 * @param object|false $user_course User course model.
	 * @param int          $user_id User ID.
	 *
	 * @return array<int, object>
	 */
	protected function get_course_user_items_map( $user_course, int $user_id ): array {
		if ( ! is_object( $user_course ) ) {
			return array();
		}

		$parent_id = method_exists( $user_course, 'get_user_item_id' ) ? absint( $this->call_method( $user_course, 'get_user_item_id', 0 ) ) : 0;
		if ( $parent_id <= 0 ) {
			return array();
		}

		try {
			$filter            = new LP_User_Items_Filter();
			$filter->user_id   = $user_id;
			$filter->parent_id = $parent_id;
			$items             = LP_User_Items_DB::getInstance()->get_user_course_items( $filter );
		} catch ( Throwable $e ) {
			return array();
		}

		if ( ! is_array( $items ) ) {
			return array();
		}

		$map = array();
		foreach ( $items as $item ) {
			if ( is_object( $item ) && isset( $item->item_id ) ) {
				$map[ absint( $item->item_id ) ] = $item;
			}
		}

		return $map;
	}

	/**
	 * Normalize a user item object into webhook-safe data.
	 *
	 * @param object $user_item User item object.
	 *
	 * @return array<string, mixed>
	 */
	protected function normalize_user_item_object( $user_item ): array {
		return array(
			'user_item_id'     => is_object( $user_item ) && method_exists( $user_item, 'get_user_item_id' ) ? absint( $this->call_method( $user_item, 'get_user_item_id', 0 ) ) : 0,
			'user_id'          => absint( $user_item->user_id ?? 0 ),
			'item_id'          => absint( $user_item->item_id ?? 0 ),
			'item_type'        => sanitize_key( (string) ( $user_item->item_type ?? '' ) ),
			'course_id'        => absint( $user_item->ref_id ?? 0 ),
			'ref_id'           => absint( $user_item->ref_id ?? 0 ),
			'ref_type'         => sanitize_key( (string) ( $user_item->ref_type ?? '' ) ),
			'status'           => sanitize_key( (string) ( $user_item->status ?? '' ) ),
			'learning_status'  => sanitize_key( (string) ( $user_item->status ?? '' ) ),
			'graduation'       => sanitize_key( (string) ( $user_item->graduation ?? '' ) ),
			'started_at'       => $this->sanitize_text( (string) ( $user_item->start_time ?? '' ) ),
			'ended_at'         => $this->sanitize_text( (string) ( $user_item->end_time ?? '' ) ),
			'duration_seconds' => $this->duration_seconds( (string) ( $user_item->start_time ?? '' ), (string) ( $user_item->end_time ?? '' ) ),
		);
	}

	/**
	 * Resolve a concrete user item model when useful for result methods.
	 *
	 * @param array $user_item User item data.
	 *
	 * @return object|false
	 */
	protected function get_user_item_model( array $user_item ) {
		try {
			$model = UserItemModel::find_user_item(
				absint( $user_item['user_id'] ?? 0 ),
				absint( $user_item['item_id'] ?? 0 ),
				(string) ( $user_item['item_type'] ?? '' ),
				absint( $user_item['ref_id'] ?? $user_item['course_id'] ?? 0 ),
				(string) ( $user_item['ref_type'] ?? $this->course_post_type() ),
				true
			);

			if ( $model && (string) ( $user_item['item_type'] ?? '' ) === $this->quiz_post_type() ) {
				return new UserQuizModel( $model );
			}

			return $model;
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Resolve a course model.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return object|false
	 */
	protected function get_course_model( int $course_id ) {
		if ( $course_id <= 0 ) {
			return false;
		}
		$course = CourseModel::find( $course_id, true );
		if ( $course instanceof CourseModel ) {
			return $course;
		} else {
			return false;
		}
	}

	/**
	 * Resolve a user course model.
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 *
	 * @return object|false
	 */
	protected function get_user_course_model( int $user_id, int $course_id ) {
		return UserCourseModel::find( $user_id, $course_id, true );
	}

	/**
	 * Remove sensitive or oversized result fields from quiz/assignment data.
	 *
	 * @param array $result Result data.
	 *
	 * @return array<string, mixed>
	 */
	protected function strip_sensitive_result_fields( array $result ): array {
		$blocked = array(
			'answers',
			'answer',
			'answered',
			'answer_data',
			'questions',
			'question_answers',
			'files',
			'file',
			'attachments',
			'submission',
			'submissions',
			'notes',
		);

		foreach ( $blocked as $key ) {
			unset( $result[ $key ] );
		}

		foreach ( $result as $key => $value ) {
			if ( is_array( $value ) ) {
				$result[ $key ] = $this->strip_sensitive_result_fields( $value );
			}
		}

		return $result;
	}

	/**
	 * Set a field only when the legacy payload did not already provide a value.
	 *
	 * @param array  $data Payload data by reference.
	 * @param string $key Field key.
	 * @param mixed  $value Value.
	 *
	 * @return void
	 */
	protected function set_if_missing( array &$data, string $key, $value ): void {
		if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] || '' === $data[ $key ] || array() === $data[ $key ] ) {
			$data[ $key ] = $value;
		}
	}

	/**
	 * Normalize a scalar/list of IDs.
	 *
	 * @param array $ids Raw IDs.
	 *
	 * @return int[]
	 */
	protected function normalize_ids( array $ids ): array {
		$normalized = array();
		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$normalized[] = $id;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Call an object method when it is available.
	 *
	 * @param object $target Target object.
	 * @param string $method Method name.
	 * @param mixed  $fallback Fallback value.
	 * @param array  $args Method arguments.
	 *
	 * @return mixed
	 */
	protected function call_method( $target, string $method, $fallback = null, array $args = array() ) {
		if ( ! is_object( $target ) || ! method_exists( $target, $method ) ) {
			return $fallback;
		}

		return $target->{$method}( ...$args );
	}

	/**
	 * Calculate duration between two datetime strings.
	 *
	 * @param string $start Start time.
	 * @param string $end End time.
	 *
	 * @return int|null
	 */
	protected function duration_seconds( string $start, string $end ) {
		if ( '' === $start || '' === $end ) {
			return null;
		}

		$start_timestamp = strtotime( $start );
		$end_timestamp   = strtotime( $end );

		if ( ! $start_timestamp || ! $end_timestamp || $end_timestamp < $start_timestamp ) {
			return null;
		}

		return $end_timestamp - $start_timestamp;
	}

	/**
	 * Return LearnPress course post type.
	 *
	 * @return string
	 */
	protected function course_post_type(): string {
		return defined( 'LP_COURSE_CPT' ) ? LP_COURSE_CPT : 'lp_course';
	}

	/**
	 * Return LearnPress quiz post type.
	 *
	 * @return string
	 */
	protected function quiz_post_type(): string {
		return defined( 'LP_QUIZ_CPT' ) ? LP_QUIZ_CPT : 'lp_quiz';
	}

	/**
	 * Check whether a resource type represents an order.
	 *
	 * @param string $type Resource type.
	 *
	 * @return bool
	 */
	protected function is_order_type( string $type ): bool {
		return in_array( $type, array( 'lp_order', 'learnpress_order', 'order' ), true );
	}

	/**
	 * Sanitize display text.
	 *
	 * @param string $value Raw text.
	 *
	 * @return string
	 */
	protected function sanitize_text( string $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize a key-like value.
	 *
	 * @param string $value Raw key.
	 *
	 * @return string
	 */
	protected function sanitize_key_string( string $value ): string {
		return sanitize_key( $value );
	}
}
