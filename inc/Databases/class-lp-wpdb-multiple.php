<?php
/**
 * Class LP_WPDB_Multiple
 *
 * @version 1.0.0
 * @since 4.2.4
 */
defined( 'ABSPATH' ) || exit();
class LP_WPDB_Multiple extends wpdb {
	private static $_instance;
	public function __construct() {
		parent::__construct( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * [insert_multiple allow insert multiple row to table]
	 * @param  string $table  table name
	 * @param  array  $data   data to insert
	 * @param  array $format data format, ex:['%s', '%d','%s']
	 * @return int|false     insert row count or false
	 */
	public function insert_multiple( $table = '' , $data = [] , $format = null ) {
		$this->insert_id = 0;

		$formats = array();
		$values  = array();

		foreach ( $data as $index => $row ) {
			$row         = $this->process_fields( $table, $row, $format );
			$row_formats = array();

			if ( $row === false || array_keys( $data[ $index ] ) !== array_keys( $data[0] ) ) {
				continue;
			}

			foreach ( $row as $col => $value ) {
				if ( is_null( $value['value'] ) ) {
					$row_formats[] = 'NULL';
				} else {
					$row_formats[] = $value['format'];
				}

				$values[] = $value['value'];
			}

			$formats[] = '(' . implode( ', ', $row_formats ) . ')';
		}

		$fields  = '`' . implode( '`, `', array_keys( $data[0] ) ) . '`';
		$formats = implode( ', ', $formats );
		$sql     = "INSERT INTO `$table` ($fields) VALUES $formats";

		$this->check_current_query = false;
		return $this->query( $this->prepare( $sql, $values ) );
	}
}
LP_WPDB_Multiple::getInstance();

