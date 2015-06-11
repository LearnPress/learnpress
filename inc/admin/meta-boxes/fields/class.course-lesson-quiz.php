<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'RWMB_Course_lesson_Quiz_Field' ) ) {
	/**
	 * Thim Theme
	 *
	 * Manage Course, Lesson, Quiz
	 *
	 * @class      RWMB_Course_lesson_Quiz_Field
	 */
	class RWMB_Course_lesson_Quiz_Field extends RWMB_Field {
		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts() {
			LPR_Admin_Assets::enqueue_style( 'select2',         RWMB_CSS_URL . 'select2/select2.css' );
            LPR_Admin_Assets::enqueue_style( 'toastr',          LPR_CSS_URL . 'toastr.css' );
            LPR_Admin_Assets::enqueue_style( 'thim-course',     LearnPress()->plugin_url( 'inc/admin/meta-boxes/css/course.css' ) );

			LPR_Admin_Assets::enqueue_script( 'select2',        RWMB_JS_URL . 'select2/select2.min.js' );
			LPR_Admin_Assets::enqueue_script( 'toastr',         LPR_JS_URL . 'toastr.js' );
			LPR_Admin_Assets::enqueue_script( 'tojson',         LPR_JS_URL . 'toJSON.js' );
			LPR_Admin_Assets::enqueue_script( 'thim-course',    LearnPress()->plugin_url( 'inc/admin/meta-boxes/js/course.js' ), array( 'jquery', 'toastr', 'tojson' ) );

			LPR_Admin_Assets::add_localize(
                array(
                    'confirm_remove_section_lesson' => __( 'Do you want to remove this lesson permanently?' ),
                    'confirm_remove_section_quiz'   => __( 'Do you want to remove this quiz permanently?' ),
                    'confirm_remove_section'        => __( 'Do you want to remove this section permanently?' ),
                    'add_new_quiz'                  => __( 'New quiz added' ),
                    'add_new_lesson'                => __( 'New lesson added' ),
                    'add_new_section'               => __( 'New section added' ),
                    'remove_section_lesson'         => __( 'The lesson removed' ),
                    'remove_section_quiz'           => __( 'The quiz removed' ),
                    'remove_section'                => __( 'The section removed' ),
                    'section_ordered'               => __( 'The ordering completed' ),
                    'add_lesson_to_section'         => __( 'Lesson added to section complete!' ),
                    'add_quiz_to_section'           => __( 'Quiz added to section complete!' ),
                    'update_lesson_quiz'            => __( '%s updated' ),
                    'quick_edit_name'               => __( 'Click to quick edit name' )
                ), null, 'thim-course'
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
		static function html( $meta, $field ) {
			$exclude_quiz   = array();
			$exclude_lesson = array();
			$current_user   = get_current_user_id();
			global $wpdb;
			$used_item = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT         pm.meta_value
					FROM            $wpdb->posts        AS p
					INNER JOIN      $wpdb->postmeta     AS pm  ON p.ID = pm.post_id
						WHERE           p.post_type = %s
						AND 			p.post_author = %d
						AND             pm.meta_key = %s",
					'lpr_course',
					$current_user,
					'_lpr_course_lesson_quiz'
				)
			);


			for ( $i = 0; $i < count( $used_item ); $i ++ ) {
				$lesson_quiz_array = unserialize( $used_item[$i] );
				for ( $j = 0; $j < count( $lesson_quiz_array ); $j ++ ) {
					if ( isset($lesson_quiz_array[$j]['lesson_quiz']) ) {
						foreach ( $lesson_quiz_array[$j]['lesson_quiz'] as $key => $value ) {
							array_push( $exclude_lesson, $value );
							array_push( $exclude_quiz, $value );
						}
					}
				}
			}


			ob_start();
			?><!-- -->
			<div class="lpr-course-curriculum">

                <p class="lpr-course-curriculum-toggle">
					<a href="" class="expand" data-action="expand"><?php _e( 'Expand All', 'learnpress' ); ?></a>
					<a href="" class="close" data-action="close"><?php _e( 'Collapse All', 'learnpress' ); ?></a>
				</p>
                <?php _e( 'Outline your course and add content with sections, lessons and quizzes.', 'learnpress');?>
				<ul class="lpr-curriculum-sections">
					<?php
					global $post;
					$course_sections = get_post_meta( $post->ID, '_lpr_course_lesson_quiz', true );
					$section_state   = get_post_meta( $post->ID, '_lpr_course_section_state', true );
					if ( $course_sections ): foreach ( $course_sections as $k => $section ):?>
						<?php
						$is_open = !isset( $section_state[$k] ) || ( isset( $section_state[$k] ) && $section_state[$k] );
						?>
						<li class="lpr-curriculum-section<?php echo $is_open ? "" : " closed"; ?>">
							<h3>
								<!-- actions -->
								<span class="lpr-action lpr-toggle" title="<?php _e( 'Expand/Close' ); ?>"><i class="dashicons <?php echo $is_open ? "dashicons-minus" : "dashicons-plus"; ?>"></i></span>
								<span class="lpr-action lpr-sort"><i class="dashicons dashicons-sort"></i></span>
								<span class="lpr-action lpr-remove" title="<?php _e( 'Remove' ); ?>"><i class="dashicons dashicons-no"></i></span>
								<!-- // actions -->
								<span class="lpr-section-icon"><i class="dashicons dashicons-pressthis"></i></span>
								<span class="lpr-section-name-wrapper"><input name="_lpr_course_lesson_quiz[__SECTION__][name]" type="text" placeholder="Enter the section name and hit enter" class="lpr-section-name" value="<?php echo esc_attr( $section['name'] ); ?>" /></span>
							</h3>

							<div class="lpr-curriculum-section-content">
								<ul class="lpr-section-quiz-less">
									<?php if ( isset( $section['lesson_quiz'] ) && is_array( $section['lesson_quiz'] ) ):
										global $wpdb;

										$query = "
                                            SELECT *
                                            FROM {$wpdb->posts} p
                                            WHERE p.ID IN(" . join( ',', $section['lesson_quiz'] ) . ")
                                        ";

										if ( $items = $wpdb->get_results( $query, OBJECT_K ) ):

											foreach ( $section['lesson_quiz'] as $id ):
                                                if( empty( $items[$id] ) ) continue;
												$item = $items[$id];
												if ( 'lpr_quiz' == $item->post_type ) $exclude_quiz[] = $item->ID;
												if ( 'lpr_lesson' == $item->post_type ) $exclude_lesson[] = $item->ID;
												?>
												<li class="lpr-<?php echo $item->post_type; ?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $item->post_type; ?>">
													<?php if ( 'lpr_quiz' == $item->post_type ) { ?>
														<span class="handle dashicons dashicons-format-status"></span>
													<?php } else { ?>
														<span class="handle dashicons dashicons-media-document"></span>
													<?php } ?>
													<span class="lpr-title" title="<?php _e( 'Click to quick edit' ); ?>"><?php echo $item->post_title; ?></span>
													<a href="" class="lpr-remove"><?php _e( 'Remove' ); ?></a>
													<a href="<?php echo get_edit_post_link( $item->ID ); ?>" target="_blank"><?php _e( 'Edit' ); ?></a>
													<input type="hidden" name="_lpr_course_lesson_quiz[__SECTION__][lesson_quiz][]" value="<?php echo $item->ID; ?>" />
												</li>
											<?php endforeach; ?>
										<?php endif; ?>
									<?php endif; ?>
								</ul>
								<p class="lpr-add-buttons">
									<button class="button button-primary" data-action="add-lesson"><?php _e( 'Add Lesson' ); ?></button>
									<button class="button button-primary" data-action="add-quiz"><?php _e( 'Add Quiz' ); ?></button>
									<button class="button button-primary" data-action="quick-add-lesson"><?php _e( 'Quick add <span>L</span>esson' ); ?></button>
									<button class="button button-primary" data-action="quick-add-quiz"><?php _e( 'Quick add <span>Q</span>uiz' ); ?></button>
								</p>
							</div>

						</li>
					<?php endforeach; endif; ?>
					<li class="lpr-curriculum-section lpr-empty">
						<h3>
							<!-- actions -->
							<span class="lpr-action lpr-toggle" title="<?php _e( 'Expand/Close' ); ?>"><i class="dashicons dashicons-minus"></i></span>
							<span class="lpr-action lpr-sort"><i class="dashicons dashicons-sort"></i></span>
							<span class="lpr-action lpr-remove" title="<?php _e( 'Remove' ); ?>"><i class="dashicons dashicons-no"></i></span>
							<!-- // actions -->
							<span class="lpr-section-icon"><i class="dashicons dashicons-pressthis"></i></span>
							<span class="lpr-section-name-wrapper"><input name="_lpr_course_lesson_quiz[__SECTION__][name]" type="text" placeholder="Enter the section name and hit enter" class="lpr-section-name" /></span>
						</h3>

						<div class="lpr-curriculum-section-content">
							<ul class="lpr-section-quiz-less">

							</ul>
							<p class="lpr-add-buttons">
								<button class="button button-primary" data-action="add-lesson"><?php _e( 'Add Lesson' ); ?></button>
								<button class="button button-primary" data-action="add-quiz"><?php _e( 'Add Quiz' ); ?></button>
								<button class="button button-primary" data-action="quick-add-lesson"><?php _e( 'Quick add <span>L</span>esson' ); ?></button>
								<button class="button button-primary" data-action="quick-add-quiz"><?php _e( 'Quick add <span>Q</span>uiz' ); ?></button>
							</p>
						</div>

					</li>
				</ul>
			</div>
			<script type="text/html" id="tmpl-quick-add-lesson">
				<div id="lpr-quick-add-lesson-form" class="lpr-quick-add-form">
					<input type="text" name="" />
					<button type="button" class="button" data-action="cancel"><?php _e( 'Cancel [ESC]' ); ?></button>
					<button type="button" class="button" data-action="add"><?php _e( 'Add [Enter]' ); ?></button>
					<span class="lpr-ajaxload">...</span>
				</div>
			</script>
			<script type="text/html" id="tmpl-quick-add-quiz">
				<div id="lpr-quick-add-quiz-form" class="lpr-quick-add-form">
					<input type="text" name="" />
					<button type="button" class="button" data-action="cancel"><?php _e( 'Cancel [ESC]' ); ?></button>
					<button type="button" class="button" data-action="add"><?php _e( 'Add [Enter]' ); ?></button>
					<span class="lpr-ajaxload">...</span>
				</div>
			</script>
			<script type="text/html" id="tmpl-curriculum-section">
				<li class="lpr-curriculum-section lpr-empty">
					<h3>
						<!-- actions -->
						<span class="lpr-action lpr-toggle" title="<?php _e( 'Expand/Close' ); ?>"><i class="dashicons dashicons-minus"></i></span>
						<span class="lpr-action lpr-sort"><i class="dashicons dashicons-sort"></i></span>
						<span class="lpr-action lpr-remove" title="<?php _e( 'Remove' ); ?>"><i class="dashicons dashicons-no"></i></span>
						<!-- // actions -->
						<span class="lpr-section-icon"><i class="dashicons dashicons-pressthis"></i></span>
						<span class="lpr-section-name-wrapper"><input name="_lpr_course_lesson_quiz[__SECTION__][name]" type="text" placeholder="Type section's name and hit enter" class="lpr-section-name" /></span>
					</h3>

					<div class="lpr-curriculum-section-content">
						<ul class="lpr-section-quiz-less"></ul>
						<p class="lpr-add-buttons">
							<button class="button button-primary" data-action="add-lesson"><?php _e( 'Add Lesson' ); ?></button>
							<button class="button button-primary" data-action="add-quiz"><?php _e( 'Add Quiz' ); ?></button>
							<button class="button button-primary" data-action="quick-add-lesson"><?php _e( 'Quick add <span>L</span>esson' ); ?></button>
							<button class="button button-primary" data-action="quick-add-quiz"><?php _e( 'Quick add <span>Q</span>uiz' ); ?></button>
						</p>
					</div>
				</li>
			</script>
			<script type="text/html" id="tmpl-section-quiz-lesson">

				<li class="lpr-empty lpr-{{data.type}}" data-id="{{data.id}}" data-type="{{data.type}}">
					<# if( 'quiz' == data.type ){ #>
						<span class="handle dashicons dashicons-format-status"></span>
						<# }else{ #>
							<span class="handle dashicons dashicons-media-document"></span>
							<# } #>
								<span class="lpr-title" title="<?php _e( 'Click to quick edit' ); ?>">{{data.title}}</span><a href="" class="lpr-remove"><?php _e( 'Remove' ); ?></a><a href="<?php echo admin_url( 'post.php?post={{data.id}}&action=edit' );?>" target="_blank"><?php _e( 'Edit' ); ?></a>
								<input type="hidden" name="_lpr_course_lesson_quiz[__SECTION__][lesson_quiz][]" value="{{data.id}}" />
				</li>
			</script>

			<script type="text/html" id="tmpl-lpr-lesson-form">
				<div id="lpr-lesson-form" class="lpr-dynamic-form">
                    <?php
                    $query_args = array(
                        'post_type'      => 'lpr_lesson',
                        'post_status'    => 'publish',
                        'author'         => $current_user,
                        'posts_per_page' => - 1,
                        'post__not_in'   => $exclude_lesson
                    );
                    //print_r( $query_args );
                    $query      = new WP_Query( $query_args );
                    ?>
					<select name="">
						<option value=""><?php _e( '--Select a Lesson--' ); ?></option>
						<?php
						if ( $query->have_posts() ) {
							while ( $query->have_posts() ) {
								$p = $query->next_post();
								echo '<option value="' . $p->ID . '">' . $p->post_title . '</option>';
							}
						}
						?>
                    </select>
				</div>
			</script>
			<script type="text/html" id="tmpl-lpr-quiz-form">
				<div id="lpr-quiz-form" class="lpr-dynamic-form">
					<select name="">
						<option value=""><?php _e( '--Select a Quiz--' ); ?></option>
						<?php
						$query_args = array(
							'post_type'      => 'lpr_quiz',
							'post_status'    => 'publish',
							'author'         => $current_user,
							'posts_per_page' => - 1,
							'post__not_in'   => $exclude_quiz
						);
						$query      = new WP_Query( $query_args );
						if ( $query->have_posts() ) {
							while ( $query->have_posts() ) {
								$p = $query->next_post();
								echo '<option value="' . $p->ID . '">' . $p->post_title . '</option>';
							}
						}
						?>
					</select>
				</div>
			</script>
			<?php global $post; ?>
			<script type="text/javascript">
				var lpr_course_id =
				<?php echo $post->ID;?>
			</script>
			<?php
			return ob_get_clean();
		}

		/**
		 * Normalize parameters for field
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		static function normalize_field( $field ) {
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
		static function datalist_html( $field ) {
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
		static function save( $new, $old, $post_id, $field ) {

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

		static function add_actions() {
			// Do same actions as file field
			parent::add_actions();

			add_action( 'wp_ajax_lpr_quick_add', array( __CLASS__, 'quick_add' ) );
			add_action( 'wp_ajax_lpr_update_course_curriculum', array( __CLASS__, 'update_course_curriculum' ) );
			add_action( 'wp_ajax_lpr_quick_edit_lesson_quiz_name', array( __CLASS__, 'quick_edit_lesson_quiz_name' ) );
			add_action( 'wp_ajax_lpr_update_section_state', array( __CLASS__, 'update_section_state' ) );
            add_action( 'wp_ajax_lpr_remove_lesson_quiz', array( __CLASS__, 'remove_lesson_quiz' ) );

            add_action( 'save_post', array( __CLASS__, 'update_course_curriculum' ) );

		}
        static function remove_lesson_quiz(){
            $lesson_quiz_id = $_POST['lesson_quiz_id'];
            delete_post_meta( $lesson_quiz_id, '_lpr_course' );
        }
		static function update_section_state() {
			$post_id = $_POST['post_id'];
			$section = $_POST['section'];
			update_post_meta( $post_id, '_lpr_course_section_state', $section );
			die();
		}

		static function quick_add() {
			//ob_end_flush();
			echo '__LPR_JSON__';
			$name = isset( $_POST['name'] ) ? $_POST['name'] : null;
			$type = isset( $_POST['type'] ) ? $_POST['type'] : null;
            $course_id = isset( $_POST['course_id'] ) ? $_POST['course_id'] : null;
			$post = false;
			if ( $name && $type ) {
				$post_id = wp_insert_post(
					array(
						'post_title'  => $name,
						'post_type'   => $type == 'lesson' ? LPR_LESSON_CPT : LPR_QUIZ_CPT,
						'post_status' => 'publish'
					)
				);
				if ( $post_id ) {
					$post = get_post( $post_id );
                    if( $course_id ) {
                        update_post_meta($post_id, '_lpr_course', $course_id);
                    }
				}
			}
			wp_send_json( $post );
			die();
		}

		static function quick_edit_lesson_quiz_name() {

			$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
			echo '__LPR_JSON__';
			if ( $id ) {
				$name    = $_POST['name'];
				$post_id = wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $name
					)
				);
				if ( $post_id ) {
					wp_send_json( get_post( $post_id ) );
				}
			}

		}

		static function update_course_curriculum() {

            $is_ajax = false;
            if( ! empty( $_REQUEST['action'] ) && 'lpr_update_course_curriculum' == $_REQUEST['action'] ) {
                $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
                $is_ajax = true;
            }else{
                if( 'lpr_course' != get_post_type() ) return;
                global $post;
                $course_id = $post->ID;
            }
			$meta_key  = isset( $_POST['meta_key'] ) ? $_POST['meta_key'] : '_lpr_course_lesson_quiz';
			$metadata  = isset( $_POST['_lpr_course_lesson_quiz'] ) ? $_POST['_lpr_course_lesson_quiz'] : false;
			if ( !$course_id ) {
				echo '__LPR_JSON__';
				wp_send_json(
					array(
						'message' => __( 'The course is empty' )
					)
				);
			}

            $metadata = apply_filters( 'learn_press_course_curriculum', $metadata, $course_id );
			update_post_meta( $course_id, $meta_key, $metadata );
            do_action( 'learn_press_update_course_curriculum', $course_id, $metadata );

            //
            global $wpdb;
            if( $metadata ) foreach( $metadata as $section ){
                if( !empty( $section['lesson_quiz'] ) && $lesson_quiz = $section['lesson_quiz'] ){
                    $query = $wpdb->prepare( "
                        DELETE FROM {$wpdb->postmeta}
                        WHERE meta_key = %s
                        AND meta_value = %d
                        AND post_id IN(" . join( ',', $lesson_quiz ) . ")
                    ", '_lpr_course', $course_id );

                    $wpdb->query( $query );

                    $query = "INSERT INTO {$wpdb->postmeta}(`post_id`, `meta_key`, `meta_value`) VALUES";
                    $query_values = array();
                    foreach( $lesson_quiz as $id ){
                        $query_values[] = $wpdb->prepare( "(%d, %s, %d)", $id, '_lpr_course', $course_id );
                    }
                    $query .= join( ",", $query_values );
                    $wpdb->query( $query );
                }
            }
            if( ! $is_ajax ) return;
			wp_send_json(
				array(
					'message' => __( 'Success' )
				)
			);
		}

		static function meta( $post_id, $saved, $field ) {
			$meta = get_post_meta( $post_id, $field['id'], true );

			// Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run)
			$meta = ( !$saved && '' === $meta || array() === $meta ) ? $field['std'] : $meta;

			// Escape attributes for non-wysiwyg fields
			if ( 'wysiwyg' !== $field['type'] ) {
				//$meta = is_array( $meta ) ? array_map( 'esc_attr', $meta ) : esc_attr( $meta );
			}

			return $meta;
		}

		static function cleanHeader() {

		}
	}
}