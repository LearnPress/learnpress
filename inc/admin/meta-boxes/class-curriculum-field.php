<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'RWMB_Curriculum_Field' ) ) {
	/**
	 * Thim Theme
	 *
	 * Manage Course, Lesson, Quiz
	 *
	 * @class      RWMB_Course_lesson_Quiz_Field
	 */
	class RWMB_Curriculum_Field extends RWMB_Field {
		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		public static function admin_enqueue_scripts() {

			LP_Assets::enqueue_script( 'learn-press-modal-search-items' );
			LP_Assets::add_localize(
				array(
					'confirm_remove_section_lesson' => __( 'Do you want to remove this lesson permanently?', 'learnpress' ),
					'confirm_remove_section_quiz'   => __( 'Do you want to remove this quiz permanently?', 'learnpress' ),
					'confirm_remove_section'        => __( 'Do you want to remove this section permanently?', 'learnpress' ),
					'add_new_quiz'                  => __( 'New quiz added', 'learnpress' ),
					'add_new_lesson'                => __( 'New lesson added', 'learnpress' ),
					'add_new_section'               => __( 'New section added', 'learnpress' ),
					'remove_section_lesson'         => __( 'The lesson removed', 'learnpress' ),
					'remove_section_quiz'           => __( 'The quiz removed', 'learnpress' ),
					'remove_section'                => __( 'The section removed', 'learnpress' ),
					'section_ordered'               => __( 'The ordering completed', 'learnpress' ),
					'add_lesson_to_section'         => __( 'Lesson added to section completed!', 'learnpress' ),
					'add_quiz_to_section'           => __( 'Quiz added to section completed!', 'learnpress' ),
					'update_lesson_quiz'            => __( '%s updated', 'learnpress' ),
					'quick_edit_name'               => __( 'Click to quick edit name', 'learnpress' ),
					'save_course'                   => __( 'Save Course', 'learnpress' ),
					'submit_course_review'          => __( 'Submit for Review', 'learnpress' )
				), null, 'learn-press-mb-course'
			);
		}

		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param array $field
		 *
		 * @return string
		 */
		public static function html( $meta, $field ) {
			global $post;
			$course = LP_Course::get_course( $post );
			$view   = learn_press_get_admin_view( 'meta-boxes/course/curriculum.php' );
			ob_start();
			include $view;
			return ob_get_clean();
		}

		/**
		 * Normalize parameters for field
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function normalize_field( $field ) {
			$field = wp_parse_args( $field, array(
				'size'        => 30,
				'datalist'    => false,
				'placeholder' => '',
			) );

			return $field;
		}

		/**
		 * Create datalist, if any
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		public static function datalist_html( $field ) {
			if ( !$field['datalist'] ) {
				return '';
			}

			$datalist = $field['datalist'];
			$html     = sprintf(
				'<datalist id="%s">',
				$datalist['id']
			);

			foreach ( $datalist['options'] as $option ) {
				$html .= sprintf( '<option value="%s"></option>', $option );
			}

			$html .= '</datalist>';

			return $html;
		}

		/**
		 * Save meta value
		 * If field is cloneable, value is saved as a single entry in DB
		 * Otherwise value is saved as multiple entries (for backward compatibility)
		 *
		 * TODO: A good way to ALWAYS save values in single entry in DB, while maintaining backward compatibility
		 *
		 * @param $new
		 * @param $old
		 * @param $post_id
		 * @param $field
		 */
		public static function save( $new, $old, $post_id, $field ) {

			if ( empty( $new ) ) {
				delete_post_meta( $post_id, $field['id'] );
			} else {
				$new = array_values( $new );
				for ( $n = count( $new ), $i = $n - 1; $i >= 0; $i -- ) {
					if ( !$new[$i]['name'] ) unset( $new[$i] );
				}
				$new = array_values( $new );
				update_post_meta( $post_id, $field['id'], $new );
			}
		}

		public static function add_actions() {
			// Do same actions as file field
			parent::add_actions();

			/*add_action( 'wp_ajax_lpr_quick_add', array( __CLASS__, 'quick_add' ) );
			add_action( 'wp_ajax_lpr_update_course_curriculum', array( __CLASS__, 'update_course_curriculum' ) );
			add_action( 'wp_ajax_lpr_quick_edit_lesson_quiz_name', array( __CLASS__, 'quick_edit_lesson_quiz_name' ) );
			add_action( 'wp_ajax_lpr_update_section_state', array( __CLASS__, 'update_section_state' ) );
			add_action( 'wp_ajax_lpr_remove_lesson_quiz', array( __CLASS__, 'remove_lesson_quiz' ) );*/

			///add_action( 'save_post', array( __CLASS__, 'update_course_curriculum' ) );
			add_filter( 'learn_press_loop_section_buttons', array( __CLASS__, 'add_section_buttons' ) );
			//add_filter( 'learn_press_after_section_content', array( __CLASS__, 'section_options' ) );
		}

		public static function add_section_buttons( $buttons ) {
			$buttons = array_merge(
				$buttons,
				array(
					array(
						'id'   => 'add-lesson',
						'text' => __( 'Add Lesson', 'learnpress' ),
						'attr' => 'data-action="add-lesson" data-type="lp_lesson"'
					),
					array(
						'id'   => 'add-quiz',
						'text' => __( 'Add Quiz', 'learnpress' ),
						'attr' => 'data-action="add-quiz" data-type="lp_quiz"'
					)
				)
			);
			return $buttons;
		}

		public static function section_options() {
			?>
			<table class="form-table">
				<tr>
					<th>
						<?php _e( 'Using final quiz', 'learnpress' ); ?>
					</th>
					<td>
						<input type="checkbox" />

						<p class="description"><?php _e( 'User must be complete the final quiz to finish a section.', 'learnpress' ); ?></p>

					</td>
				</tr>
			</table>
			<?php
		}

		public static function remove_lesson_quiz() {
			$lesson_quiz_id = $_POST['lesson_quiz_id'];
			delete_post_meta( $lesson_quiz_id, '_lpr_course' );
			update_post_meta( $lesson_quiz_id, '_lpr_course', 0 );
		}

		public static function update_section_state() {
			$post_id = $_POST['post_id'];
			$section = $_POST['section'];
			update_post_meta( $post_id, '_lpr_course_section_state', $section );
			die();
		}

		public static function quick_add() {
			//ob_end_flush();
			echo '__LP_JSON__';
			$name      = isset( $_POST['name'] ) ? $_POST['name'] : null;
			$type      = isset( $_POST['type'] ) ? $_POST['type'] : null;
			$course_id = isset( $_POST['course_id'] ) ? $_POST['course_id'] : null;
			$post      = false;
			if ( $name && $type ) {
				$post_id = wp_insert_post(
					array(
						'post_title'  => $name,
						'post_type'   => $type == 'lesson' ? LP_LESSON_CPT : LP_QUIZ_CPT,
						'post_status' => 'publish'
					)
				);
				if ( $post_id ) {
					$post = get_post( $post_id );
					if ( $course_id ) {
						update_post_meta( $post_id, '_lpr_course', $course_id );
					}
				}
			}
			wp_send_json( $post );
			die();
		}

		public static function quick_edit_lesson_quiz_name() {
			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			echo '__LP_JSON__';
			if ( $id ) {
				$name    = $_POST['name'];
				$slug    = sanitize_title( $name );
				$post_id = wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $name,
						'post_name'  => $slug
					)
				);
				if ( $post_id ) {
					wp_send_json( get_post( $post_id ) );
				}
			}

		}

		public static function update_course_curriculum() {

			$is_ajax = false;
			if ( !empty( $_REQUEST['action'] ) && 'lpr_update_course_curriculum' == $_REQUEST['action'] ) {
				$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
				$is_ajax   = true;
			} else {
				if ( LP_COURSE_CPT != get_post_type() ) return;
				global $post;
				$course_id = $post->ID;
			}
			$meta_key = isset( $_POST['meta_key'] ) ? $_POST['meta_key'] : '_lpr_course_lesson_quiz';
			$metadata = isset( $_POST['_lpr_course_lesson_quiz'] ) ? $_POST['_lpr_course_lesson_quiz'] : false;
			if ( !$course_id ) {
				echo '__LP_JSON__';
				wp_send_json(
					array(
						'message' => __( 'The course is empty', 'learnpress' )
					)
				);
			}

			$metadata = apply_filters( 'learn_press_course_curriculum', $metadata, $course_id );
			update_post_meta( $course_id, $meta_key, $metadata );
			do_action( 'learn_press_update_course_curriculum', $course_id, $metadata );

			//
			global $wpdb;
			if ( $metadata ) foreach ( $metadata as $section ) {
				if ( !empty( $section['lesson_quiz'] ) && $lesson_quiz = $section['lesson_quiz'] ) {
					$query = $wpdb->prepare( "
                        DELETE FROM {$wpdb->postmeta}
                        WHERE meta_key = %s
                        AND ( meta_value = %d OR meta_value = %d OR meta_value = %s )
                        AND post_id IN(" . join( ',', $lesson_quiz ) . ")
                    ", '_lpr_course', $course_id, 0, '' );

					$wpdb->query( $query );

					$query        = "INSERT INTO {$wpdb->postmeta}(`post_id`, `meta_key`, `meta_value`) VALUES";
					$query_values = array();
					foreach ( $lesson_quiz as $id ) {
						$query_values[] = $wpdb->prepare( "(%d, %s, %d)", $id, '_lpr_course', $course_id );
					}
					$query .= join( ",", $query_values );
					$wpdb->query( $query );
				}
			}
			if ( !$is_ajax ) return;
			wp_send_json(
				array(
					'message' => __( 'Success', 'learnpress' )
				)
			);
		}

		public static function meta( $post_id, $saved, $field ) {
			$meta = get_post_meta( $post_id, $field['id'], true );

			// Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run)
			$meta = ( !$saved && '' === $meta || array() === $meta ) ? $field['std'] : $meta;

			// Escape attributes for non-wysiwyg fields
			if ( 'wysiwyg' !== $field['type'] ) {
				//$meta = is_array( $meta ) ? array_map( 'esc_attr', $meta ) : esc_attr( $meta );
			}

			return $meta;
		}

		public static function cleanHeader() {

		}
	}
}
