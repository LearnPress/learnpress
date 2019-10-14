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
			SELECT results 
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d LIMIT 0, 1
		", $user_item_id );

		if ( $results = $wpdb->get_var( $query ) ) {
			$this->results = maybe_unserialize( $results );
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
}