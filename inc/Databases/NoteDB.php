<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\NoteFilter;
use LP_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Database access for LearnPress student notes.
 *
 * @since 4.4.9.2
 */
class NoteDB extends DataBase {
	/**
	 * @var self|null
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Query note rows.
	 *
	 * @param NoteFilter $filter     Note query filter.
	 * @param int        $total_rows Total matching rows.
	 *
	 * @return array|int|string|null
	 * @throws Exception
	 */
	public function get_notes( NoteFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_notes;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'n';
		}

		// Prefix columns with the alias to avoid ambiguity when joining users/posts.
		$alias          = $filter->collection_alias;
		$filter->fields = array_map(
			static function ( $field ) use ( $alias ) {
				return false === strpos( $field, '.' ) && false === strpos( $field, '(' ) ? "{$alias}.{$field}" : $field;
			},
			$filter->fields
		);

		$this->add_where_conditions( $filter );

		$filter = apply_filters( 'learn-press/note/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get a note row by ID.
	 *
	 * @param int $note_id Note ID.
	 *
	 * @return object|null
	 * @throws Exception
	 */
	public function get_note( int $note_id ) {
		$filter                  = new NoteFilter();
		$filter->note_id         = absint( $note_id );
		$filter->limit           = 1;
		$filter->run_query_count = false;
		$rows                    = $this->get_notes( $filter );

		return is_array( $rows ) && ! empty( $rows ) ? reset( $rows ) : null;
	}

	/**
	 * Count total notes, distinct students and distinct courses.
	 *
	 * @param NoteFilter $filter Note query filter (only conditions are used).
	 *
	 * @return array{total_notes:int, total_students:int, total_courses:int}
	 * @throws Exception
	 */
	public function get_stats( NoteFilter $filter ): array {
		$filter->collection       = $this->tb_lp_notes;
		$filter->collection_alias = 'n';
		$filter->only_fields      = array(
			'COUNT(n.note_id) AS total_notes',
			'COUNT(DISTINCT n.user_id) AS total_students',
			'COUNT(DISTINCT n.course_id) AS total_courses',
		);
		$filter->limit            = -1;
		$filter->order_by         = '';
		$filter->run_query_count  = false;

		$this->add_where_conditions( $filter );

		$rows = $this->execute( $filter );
		$row  = is_array( $rows ) && ! empty( $rows ) ? reset( $rows ) : null;

		return array(
			'total_notes'    => (int) ( $row->total_notes ?? 0 ),
			'total_students' => (int) ( $row->total_students ?? 0 ),
			'total_courses'  => (int) ( $row->total_courses ?? 0 ),
		);
	}

	/**
	 * Insert a note row.
	 *
	 * @param array<string, mixed> $data Note data.
	 *
	 * @return int
	 * @throws Exception
	 */
	public function insert_note( array $data ): int {
		return $this->insert_data(
			array(
				'data'               => $data,
				'filter'             => new NoteFilter(),
				'table_name'         => $this->tb_lp_notes,
				'key_auto_increment' => NoteFilter::COL_NOTE_ID,
			)
		);
	}

	/**
	 * Update a note row.
	 *
	 * @param array<string, mixed> $data Note data, must contain note_id.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function update_note( array $data ): bool {
		return $this->update_data(
			array(
				'data'       => $data,
				'filter'     => new NoteFilter(),
				'table_name' => $this->tb_lp_notes,
				'where_key'  => NoteFilter::COL_NOTE_ID,
			)
		);
	}

	/**
	 * Delete note rows by ID.
	 *
	 * @param int[] $note_ids Note IDs.
	 *
	 * @return int Number of deleted rows.
	 * @throws Exception
	 */
	public function delete_notes( array $note_ids ): int {
		return $this->delete_notes_by( NoteFilter::COL_NOTE_ID, $note_ids );
	}

	/**
	 * Delete all notes matching the given IDs of a column.
	 * Used to clean up when a user, course or lesson is deleted.
	 *
	 * @param string $column note_id | user_id | course_id | item_id.
	 * @param int[]  $ids    IDs.
	 *
	 * @return int Number of deleted rows.
	 * @throws Exception
	 */
	public function delete_notes_by( string $column, array $ids ): int {
		$allowed_columns = array(
			NoteFilter::COL_NOTE_ID,
			NoteFilter::COL_USER_ID,
			NoteFilter::COL_COURSE_ID,
			NoteFilter::COL_ITEM_ID,
		);
		if ( ! in_array( $column, $allowed_columns, true ) ) {
			throw new Exception( 'Invalid column to delete notes by.' );
		}

		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$filter             = new NoteFilter();
		$filter->collection = $this->tb_lp_notes;
		$ids_format         = LP_Helper::db_format_array( $ids, '%d' );
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Column is whitelisted, placeholders are generated for sanitized integer IDs.
		$filter->where[] = $this->wpdb->prepare( "AND {$column} IN ($ids_format)", $ids );
		$deleted         = $this->delete_execute( $filter );

		return $deleted > 0 ? (int) $deleted : 0;
	}

	/**
	 * Append WHERE conditions from the filter properties.
	 *
	 * @param NoteFilter $filter Note query filter.
	 *
	 * @return void
	 */
	protected function add_where_conditions( NoteFilter $filter ) {
		$alias = $filter->collection_alias;

		if ( isset( $filter->note_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.note_id = %d", $filter->note_id );
		}

		if ( ! empty( $filter->note_ids ) ) {
			$ids_format = LP_Helper::db_format_array( $filter->note_ids, '%d' );
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders are generated for sanitized integer IDs.
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.note_id IN ($ids_format)", $filter->note_ids );
		}

		if ( isset( $filter->user_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.user_id = %d", $filter->user_id );
		}

		if ( isset( $filter->course_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.course_id = %d", $filter->course_id );
		}

		if ( ! empty( $filter->course_ids ) ) {
			$ids_format = LP_Helper::db_format_array( $filter->course_ids, '%d' );
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders are generated for sanitized integer IDs.
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.course_id IN ($ids_format)", $filter->course_ids );
		}

		if ( isset( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.item_id = %d", $filter->item_id );
		}

		if ( isset( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.item_type = %s", $filter->item_type );
		}

		if ( isset( $filter->note_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.note_type = %s", $filter->note_type );
		}

		if ( ! empty( $filter->key_word ) ) {
			$search          = '%' . $this->wpdb->esc_like( $filter->key_word ) . '%';
			$filter->where[] = $this->wpdb->prepare(
				"AND ({$alias}.content LIKE %s OR {$alias}.highlight_text LIKE %s)",
				$search,
				$search
			);
		}
	}
}
