<?php

/**
 * Class LP_Quiz_Question_Meta_Box
 *
 * Custom class for overwriting some context.
 * Only use for displaying the meta box of question in the loop
 * of questions in quiz screen.
 */
class LP_Quiz_Question_Meta_Box extends RW_Meta_Box {

	/**
	 * LP_Quiz_Question_Meta_Box constructor.
	 *
	 * @param array $meta_box
	 */
	public function __construct( $meta_box ) {
		parent::__construct( $meta_box );

		$this->sanitize_fields();
		// Filter field meta (saved value).
		add_filter( 'rwmb_field_meta', array( $this, 'field_meta' ), 10, 2 );
	}

	/**
	 * We need to modify IDs of fields with prefix learn_press_question[QUESTION_ID] for JS works.
	 * So, we make the origin id of field such as sub-array.
	 * Eg: (with QUESTION_ID = 21)
	 *  + ID = a => learn_press_question[21][a]
	 *  + ID = multiple[a] => learn_press_question[21][multiple][a]
	 */
	public function sanitize_fields() {
		$fields = $this->meta_box['fields'];
		/**
		 * Modify id of fields
		 */
		foreach ( $fields as $k => $field ) {
			preg_match( '~^[a-zA-Z0-9_-]+~', $field['id'], $m );
			$new_id                    = preg_replace( '~^[a-zA-Z0-9_-]+~', '[' . $m[0] . ']', $field['id'] );
			$fields[ $k ]['id']        = sprintf( 'learn_press_question[%d]%s', $this->meta_box['post_id'], $new_id );
			$fields[ $k ]['origin_id'] = $field['id']; // Keep the origin id to get post meta
		}
		$this->meta_box['fields'] = $fields;
	}

	/**
	 * Get meta of current post with the origin ID of the field.
	 *
	 * @param mixed $meta
	 * @param array $field
	 *
	 * @return mixed
	 */
	public function field_meta( $meta, $field ) {
		/**
		 * No need to do anything if this field is the content of current question
		 */
		if ( ! $this->is_edit_screen() || ! empty( $field['context'] ) ) {
			return $meta;
		}

		// Get post meta such as Meta Box does with the origin ID
		return get_post_meta( $this->meta_box['post_id'], $field['origin_id'], true );
	}

	/**
	 * Check to see if we are standing where we should.
	 *
	 * @param null $screen
	 *
	 * @return bool
	 */
	public function is_edit_screen( $screen = null ) {
		$screen = get_current_screen();

		// Ensure we are in quiz screen and in the loop of questions
		return $screen && $screen->id === LP_QUIZ_CPT && get_post_type() === LP_QUESTION_CPT;
	}
}