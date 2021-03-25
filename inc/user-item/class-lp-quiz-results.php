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
		// If $results is user_item_id.
		if ( is_numeric( $results ) ) {
			$this->results = LP_User_Items_Result_DB::instance()->get_result( $results );
		} else {
			$this->results = $results ? (array) $results : array();
		}
	}

	/**
	 * @param string $return
	 *
	 * @return array|bool|mixed
	 */
	public function getQuestions( $return = '' ) {
		$questions = $this->offsetGet( 'questions' );

		if ( ! $questions ) {
			$questions = array();
		}
		$ids = array_keys( $questions );
		$ids = apply_filters('lp-quiz/results/getquestions',$ids);

		return $return === 'ids' ? $ids : $questions;
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
