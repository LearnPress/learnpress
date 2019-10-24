<?php

class LP_Quiz_Results implements ArrayAccess {

	/**
	 * @var array
	 */
	protected $results = array();

	/**
	 * LP_Quiz_Results constructor.
	 *
	 * @param $results
	 */
	public function __construct( $results ) {
		if ( is_numeric( $results ) ) {
			$this->read( $results );
		} else {
			$this->results = $results ? (array) $results : array();
		}
	}

	/**
	 * @param $user_item_id
	 */
	public function read( $user_item_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
			AND meta_key IN(%s, %s, %s)
		", $user_item_id, 'results', 'answers', '_question_answers' );

		$results = array();
		$answers = array();

		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				switch ( $row->meta_key ) {
					case 'results':
						$results = maybe_unserialize( $row->meta_value );
						break;
					case '_question_answers':
					case 'answers':
						if ( $row->meta_key === '_question_answers' && ! $answers ) {
							$answers = maybe_unserialize( $rows->meta_value );
						} elseif ( $row->meta_key === 'answers' ) {
							$answers = maybe_unserialize( $rows->meta_value );
						}
				}
			}

			if ( $answers ) {
				foreach ( $answers as $k => $v ) {
					if ( ! isset( $results[ $k ] ) ) {
						continue;
					}

					if ( empty( $results[ $k ]['answered'] ) || is_bool( $results[ $k ]['answered'] ) ) {
						$results[ $k ]['answered'] = $v;
					}
				}
			}

			$this->results = $results;
		}
	}

	/**
	 * @param string $return
	 *
	 * @return array|bool|mixed
	 */
	public function getQuestions( $return = '' ) {
		if ( ! $questions = $this->offsetGet( 'questions' ) ) {
			$questions = array();
		}

		return $return === 'ids' ? array_keys( $questions ) : $questions;
	}

	/**
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function getAnswered( $id = 0 ) {
		$questions = $this->getQuestions();

		if ( $id ) {
			return isset( $questions[ $id ] ) ? $questions[ $id ]['answered'] : false;
		}

		return wp_list_pluck( $questions, 'answered' );
	}

	public function get( $prop = null, $default = '' ) {
		if ( ! $prop ) {
			return $this->results ? $this->results : $default;
		}

		$value = $this->offsetGet( $prop );

		return $value ? $value : $default;
	}

	public function offsetUnset( $offset ) {
		if ( isset( $this->results[ $offset ] ) ) {
			unset( $this->results[ $offset ] );
		}
	}

	public function offsetSet( $offset, $value ) {
		$this->results[ $offset ] = $value;
	}

	public function offsetGet( $offset ) {
		return isset( $this->results[ $offset ] ) ? $this->results[ $offset ] : false;
	}

	public function offsetExists( $offset ) {
		return array_key_exists( $offset, $this->results );
	}

	public function __toString() {
		return $this->get( 'result' );
	}
}