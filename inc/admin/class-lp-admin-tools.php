<?php
/**
 * Admin tools
 */
if ( ! class_exists( 'LP_Admin_Tools' ) ) {
	/**
	 * Class LP_Admin_Tools
	 */
	class LP_Admin_Tools {
		/**
		 * Entry point
		 */
		public static function init() {
			$action = learn_press_get_request( 'action' );

			if ( ! $action ) {
				return;
			}

			if ( current_user_can( 'manage_options' ) ) {
				switch ( $action ) {
					case 'learn-press-remove-data':
						self::remove_data();
						break;
					case 'learn-press-remove-old-data':
						self::remove_old_data();
						break;
					default:
						break;
				}
			}
		}

		/**
		 * Clean table data
		 */
		/*public static function remove_data() {
			global $wpdb;

			$nonce = learn_press_get_request( 'remove-data-nonce' );

			if ( ! wp_verify_nonce( $nonce, 'learn-press-remove-data' ) ) {
				return;
			}

			$tables = array(
				'learnpress_sections',
				'learnpress_section_items',
				'learnpress_review_logs',
				'learnpress_quiz_questions',
				'learnpress_question_answers',
				'learnpress_user_courses',
				'learnpress_user_lessons',
				'learnpress_user_quizmeta',
				'learnpress_user_quizzes',
				'learnpress_order_itemmeta',
				'learnpress_order_items',
			);

			foreach ( $tables as $table ) {
				$table = $wpdb->prefix . $table;
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
					$wpdb->query( "DELETE FROM {$table}" );
				}
			}

			$query = "
				SELECT p.ID
				FROM {$wpdb->posts} p
				WHERE p.post_type IN ('lp_course', 'lp_lesson', 'lp_quiz', 'lp_question', 'lp_order', 'lp_cert')
			";

			$ids = $wpdb->get_col( $query );

			// delete all custom post types and meta data.
			if ( ! empty( $ids ) ) {
				$q = $wpdb->prepare(
					"
					DELETE FROM p, pm
					USING {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND p.post_type IN('lp_course', 'lp_lesson', 'lp_quiz', 'lp_question', 'lp_order', 'lp_cert')
					WHERE %d AND p.ID IN (" . join( ',', $ids ) . ');
					',
					1
				);
				$wpdb->query( $q );

				$wpdb->query(
					$wpdb->prepare(
						"
						DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s
						",
						'_learn_press_upgraded'
					)
				);
			}

			// 1 REMOVE term_relationships of posts
			$q = "
				DELETE FROM `tr`
					USING {$wpdb->term_relationships} AS `tr` INNER JOIN {$wpdb->term_taxonomy} AS `tt` ON `tr`.`term_taxonomy_id`=`tt`.`term_taxonomy_id`
				WHERE `tt`.`taxonomy` IN ('course_tag', 'course_category')
				";
			$wpdb->query( $q );

			// 2 Remove categories and tags
			$q = "
					DELETE
					FROM tt, t
						USING {$wpdb->term_taxonomy} AS tt
							INNER JOIN
						{$wpdb->terms} AS t ON tt.term_id = t.term_id
					WHERE
						tt.taxonomy IN('course_category','course_tag')";
			$wpdb->query( $q );

			// END REMOVE TERMS

			// DELETE all options
			$q = $wpdb->prepare(
				"
						DELETE FROM {$wpdb->options}
						WHERE
								option_name LIKE %s
								OR option_name LIKE %s;
				",
				'%' . $wpdb->esc_like( 'learn_press' ) . '%',
				'%' . $wpdb->esc_like( 'learnpress' ) . '%'
			);
			$wpdb->query( $q );
			delete_option( 'learnpress_db_version' );
			delete_option( 'learnpress_version' );

			LP_Admin_Notice::instance()->add( __( 'All courses, lessons, quizzes and questions have been removed', 'learnpress' ), 'updated', '', true );

			wp_redirect( admin_url( 'admin.php?page=learn-press-tools&learn-press-remove-data=1' ) );
			exit();
		}*/

		/*public static function remove_old_data() {
			$nonce = learn_press_get_request( 'remove-old-data-nonce' );

			if ( ! wp_verify_nonce( $nonce, 'learn-press-remove-old-data' ) ) {
				return;
			}

			global $wpdb;

			$query = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( %s, %s, %s, %s, %s, %s )", 'lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question', 'lpr_order', 'lpr_certificate' );
			$ids   = $wpdb->get_col( $query );

			if ( $ids ) {
				$object_terms = array();
				foreach ( $ids as $post_id ) {
					$terms = wp_get_object_terms( $post_id, array( 'course_tag', 'course_category' ) );

					if ( $terms ) {
						foreach ( $terms as $term ) {
							$object_terms[ $term->term_id ] = $term->term_id;
						}
					}
				}

				$wpdb->query(
					$wpdb->prepare(
						"
					DELETE FROM p, pm
					USING {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND p.post_type IN('lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question', 'lpr_order', 'lpr_certificate')
					WHERE %d AND p.ID IN (" . join( ',', $ids ) . ')
				',
						1
					)
				);

				if ( $object_terms ) {
					$deleted_terms = array();
					if ( $object_terms ) {
						foreach ( $object_terms as $term_id => $taxonomy ) {
							wp_delete_term( $term_id, $taxonomy );
							$deleted_terms[ $term_id ] = $taxonomy;
						}
					}
				}
			}

			// delete all options
			$wpdb->query(
				$wpdb->prepare(
					"
				DELETE FROM {$wpdb->options}
				WHERE
					option_name LIKE %s
					OR option_name LIKE %s
			",
					'%' . $wpdb->esc_like( '_lpr_' ) . '%',
					$wpdb->esc_like( 'lpr_' ) . '%'
				)
			);

			// delete all user meta
			$wpdb->query(
				$wpdb->prepare(
					"
				DELETE FROM {$wpdb->usermeta}
				WHERE
					meta_key LIKE %s
					OR meta_key LIKE %s
			",
					'%' . $wpdb->esc_like( 'lpr_' ) . '%',
					$wpdb->esc_like( 'lpr_' ) . '%'
				)
			);

			LP_Admin_Notice::instance()->add( __( 'Outdated data from version older than 1.0 has been removed', 'learnpress' ), 'updated', '', true );

			wp_redirect( admin_url( 'admin.php?page=learn-press-tools' ) );
			exit();
		}*/
	}
}

add_action( 'admin_init', array( 'LP_Admin_Tools', 'init' ) );
