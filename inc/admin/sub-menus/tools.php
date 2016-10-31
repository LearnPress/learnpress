<?php
/**
 * Admin view for add-ons page display in admin under menu LearnPress -> Add ons
 *
 * @author  ThimPress
 * @package Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action( 'init', 'learn_press_tool_request_actions' );
if ( !function_exists( 'learn_press_tool_request_actions' ) ) {

	function learn_press_tool_request_actions() {
		$action = learn_press_get_request( 'action' );
		if ( ! $action ) return;
		if ( current_user_can( 'manage_options' ) ) {
			switch ( $action ) {
				case 'learn-press-remove-data':
						learn_press_remove_data();
					break;
				case 'learn-press-remove-old-data':
						learn_press_remove_old_data();
					break;
				default:
					break;
			}
		} else {
			wp_die( __( 'Sorry, you are nto allowed to access this page.', 'learnpress' ) );
		}
	}

}

if ( !function_exists( 'learn_press_remove_data' ) ) {
		function learn_press_remove_data() {
				$nonce = learn_press_get_request( 'remove-data-nonce' );
				if ( !wp_verify_nonce( $nonce, 'learn-press-remove-data' ) ) {
						return;
				}
				global $wpdb;
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
						'learnpress_order_items'
				);
				// drop all data in our tables
				foreach ( $tables as $table ) {
						$table = $wpdb->prefix.$table;
						if ( $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table ) {
							$wpdb->query( "DELETE FROM {$table}" );
						}
				}

				$query = "
								SELECT p.ID
								FROM {$wpdb->posts} p
								WHERE p.post_type IN ('lp_course', 'lp_lesson', 'lp_quiz', 'lp_question', 'lp_order', 'lp_cert')
						";

				// delete all custom post types and meta data
				if ( $ids = $wpdb->get_col( $query ) ) {
						$object_terms = array();
						foreach ( $ids as $post_id ) {
								// get all terms
								$terms = wp_get_object_terms( $post_id, array( 'course_tag', 'course_category' ) );
								if ( $terms ) {
										foreach ( $terms as $term ) {
												$object_terms[$term->term_id] = $term->term_id;
										}
								}
								LP_Debug::instance()->add( $terms );
						}

						$q = $wpdb->prepare( "
								DELETE FROM p, pm
								USING {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND p.post_type IN('lp_course', 'lp_lesson', 'lp_quiz', 'lp_question', 'lp_order', 'lp_cert')
								WHERE %d AND p.ID IN (" . join( ',', $ids ) . ");
						", 1 );
						$wpdb->query( $q );

						$wpdb->query(
								$wpdb->prepare( "
										DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s
								", '_learn_press_upgraded' )
						);

						if ( $object_terms ) {
								$deleted_terms = array();
								if ( $object_terms ) {
										foreach ( $object_terms as $term_id => $taxonomy ) {
												wp_delete_term( $term_id, $taxonomy );
												$deleted_terms[$term_id] = $taxonomy;
										}
								}
						}
				}
				// delete all options
				$q = $wpdb->prepare( "
						DELETE FROM {$wpdb->options}
						WHERE
								option_name LIKE %s
								OR option_name LIKE %s;
				", '%' . $wpdb->esc_like( 'learn_press' ) . '%', '%' . $wpdb->esc_like( 'learnpress' ) . '%' );
				$wpdb->query( $q );
				delete_option( 'learnpress_db_version' );
				delete_option( 'learnpress_version' );
				
				# Remove categories
				$q = $wpdb->prepare( "
					DELETE tt , t 
					FROM {$wpdb->term_taxonomy} tt
							INNER JOIN
						{$wpdb->terms} AS t ON tt.term_id = t.term_id 
					WHERE
						tt.taxonomy = 'course_category'");
				$wpdb->query( $q );
				LP_Admin_Notice::add( __( 'All courses, lessons, quizzes and questions have been removed', 'learnpress' ), 'updated', '', true );
//                flush_rewrite_rules();
				wp_redirect( admin_url( 'admin.php?page=learn_press_tools&learn-press-remove-data=1' ) );
				exit();
		}
}

if ( !function_exists( 'learn_press_remove_old_data' ) ) {

		function learn_press_remove_old_data() {
		$nonce = learn_press_get_request( 'remove-old-data-nonce' );
		if ( !wp_verify_nonce( $nonce, 'learn-press-remove-old-data' ) ) {
			return;
		}
		global $wpdb;
				$query = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( %s, %s, %s, %s, %s, %s )", 'lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question', 'lpr_order', 'lpr_certificate');
		// delete all custom post types and meta data
		if ( $ids = $wpdb->get_col( $query ) ) {
			$object_terms = array();
			foreach ( $ids as $post_id ) {
				// get all terms
				$terms = wp_get_object_terms( $post_id, array( 'course_tag', 'course_category' ) );
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$object_terms[$term->term_id] = $term->term_id;
					}
				}
			}

			$wpdb->query(
				$wpdb->prepare( "
					DELETE FROM p, pm
					USING {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND p.post_type IN('lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question', 'lpr_order', 'lpr_certificate')
					WHERE %d AND p.ID IN (" . join( ',', $ids ) . ")
				", 1 )
			);

			if ( $object_terms ) {
				$deleted_terms = array();
				if ( $object_terms ) {
					foreach ( $object_terms as $term_id => $taxonomy ) {
						wp_delete_term( $term_id, $taxonomy );
						$deleted_terms[$term_id] = $taxonomy;
					}
				}
			}
		}
		// delete all options
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM {$wpdb->options}
				WHERE
					option_name LIKE %s
					OR option_name LIKE %s
			", '%' . $wpdb->esc_like( '_lpr_' ) . '%', $wpdb->esc_like( 'lpr_' ) . '%' )
		);

		// delete all user meta
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM {$wpdb->usermeta}
				WHERE
					meta_key LIKE %s
					OR meta_key LIKE %s
			", '%' . $wpdb->esc_like( 'lpr_' ) . '%', $wpdb->esc_like( 'lpr_' ) . '%' )
		);

			LP_Admin_Notice::add( __( 'Outdated data from version less than 1.0 have been removed', 'learnpress' ), 'updated', '', true );
			//flush_rewrite_rules();
//            header('Location: '.admin_url( 'admin.php?page=learn_press_tools' ));
			wp_redirect( admin_url( 'admin.php?page=learn_press_tools' ) );
			exit();
	}
}
/**
 * Add-on page
 */
function learn_press_tools_page() {
	?>
	<div id="learn-press-tools-wrap" class="wrap">
		<h2><?php echo __( 'LearnPress Tools', 'learnpress' ); ?></h2>
		<div class="card">
			<h2><?php _e( 'Upgrade courses', 'learnpress' ); ?></h2>
			<p><?php _e( 'Upgrade courses, lessons, quizzes and questions from version less than 1.0.', 'learnpress' ); ?></p>
			<div class="learn-press-message">
				<?php _e( 'Use this action to force system to upgrade outdated data to latest version.', 'learnpress' ); ?>
			</div>
			<div class="learn-press-error">
				<?php _e( 'All courses will be upgraded whether you have done this action in the past. So please remove all courses before you upgrade to prevent duplicated courses.', 'learnpress' ); ?>
			</div>
			<p>
				<a class="button" href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=learn_press_upgrade_from_09&force=true' ), 'learn-press-upgrade-09' ); ?>"><?php esc_html_e( 'Upgrade', 'learnpress' ); ?></a>
			</p>
		</div>
		<div class="card">
			<h2><?php _e( 'Remove current data', 'learnpress' ); ?></h2>
			<p><?php _e( 'Remove all courses, lessons, quizzes and questions', 'learnpress' ); ?></p>
			<form method="post" name="learn-press-form-remove-data">
				<div class="learn-press-message learn-press-error">
					<?php _e( 'Be careful before using this action!', 'learnpress' ); ?>
				</div>
				<label class="hide-if-js">
					<input type="checkbox" name="action" value="learn-press-remove-data" />
					<?php _e( 'Check this box and click this button again to confirm.', 'learnpress' ); ?>
				</label>
				<p>
					<button class="button button-fade"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
				</p>
				<?php wp_nonce_field( 'learn-press-remove-data', 'remove-data-nonce' ); ?>
			</form>
		</div>
		<div class="card">
			<h2><?php _e( 'Remove outdated data', 'learnpress' ); ?></h2>
			<p><?php _e( 'Remove all courses, lessons, quizzes and questions from version less than 1.0.', 'learnpress' ); ?></p>
			<form method="post" name="learn-press-form-remove-data">
				<div class="learn-press-message learn-press-error">
					<?php _e( 'Be careful before using this action! Only use this action in case all outdated data has been upgraded.', 'learnpress' ); ?>
				</div>
				<label class="hide-if-js">
					<input type="checkbox" name="action" value="learn-press-remove-old-data" />
					<?php _e( 'Check this box and click this button again to confirm.', 'learnpress' ); ?>
				</label>
				<p>
					<button class="button button-fade"><?php esc_html_e( 'Remove', 'learnpress' ); ?></button>
				</p>
				<?php wp_nonce_field( 'learn-press-remove-old-data', 'remove-old-data-nonce' ); ?>

			</form>
		</div>
	</div>
	<script type="text/javascript">
		jQuery(function ($) {
			$('form[name="learn-press-form-remove-data"]').on('submit', function () {
				var $form = $(this),
					$check = $form.find('input[name="action"]');
				if (!$form.find('input[name="action"]').is(':checked')) {
					$check.parent().removeClass('hide-if-js');
					return false;
				}
			})
		})
	</script>
	<?php
}
