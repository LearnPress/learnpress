<?php
/**
 * Place the function will be deprecated or may not used here
 */

throw new Exception( "This file will not included in anywhere" );
function learn_press_settings_payment() {
	?>
	<h3><?php _e( 'Payment', 'learn_press' ); ?></h3>
	<?php
}

/**
 * Remove all filter
 *
 * @param  String  $tag
 * @param  boolean $priority
 *
 * @return boolean
 */
function learn_press_remove_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	if ( !function_exists( 'bbpress' ) ) return;
	$bbp = bbpress();

	// Filters exist
	if ( isset( $wp_filter[$tag] ) ) {

		// Filters exist in this priority
		if ( !empty( $priority ) && isset( $wp_filter[$tag][$priority] ) ) {

			// Store filters in a backup
			$bbp->filters->wp_filter[$tag][$priority] = $wp_filter[$tag][$priority];

			// Unset the filters
			unset( $wp_filter[$tag][$priority] );

			// Priority is empty
		} else {

			// Store filters in a backup
			$bbp->filters->wp_filter[$tag] = $wp_filter[$tag];

			// Unset the filters
			unset( $wp_filter[$tag] );
		}
	}

	// Check merged filters
	if ( isset( $merged_filters[$tag] ) ) {

		// Store filters in a backup
		$bbp->filters->merged_filters[$tag] = $merged_filters[$tag];

		// Unset the filters
		unset( $merged_filters[$tag] );
	}

	return true;
}

/*
 * Rewrite url
 */

add_action( 'init', 'learn_press_add_rewrite_tag' );
function learn_press_add_rewrite_tag() {
	add_rewrite_tag( '%user%', '([^/]*)' );
	flush_rewrite_rules();
}


add_filter( 'page_rewrite_rules', 'learn_press_add_rewrite_rule' );
function learn_press_add_rewrite_rule( $rewrite_rules ) {
	// The most generic page rewrite rule is at end of the array
	// We place our rule one before that
	end( $rewrite_rules );
	$last_pattern     = key( $rewrite_rules );
	$last_replacement = array_pop( $rewrite_rules );
	$page_id          = learn_press_get_profile_page_id();
	$rewrite_rules += array(
		'^profile/([^/]*)' => 'index.php?page_id=' . $page_id . '&user=$matches[1]',
		$last_pattern      => $last_replacement
	);

	return $rewrite_rules;
}

/*
 * Editing permalink notification when using LearnPress profile
 */
add_action( 'admin_notices', 'learn_press_edit_permalink' );
add_action( 'network_admin_notices', 'learn_press_edit_permalink' );
function learn_press_edit_permalink() {

	// Setting up notification
	$check = get_option( '_lpr_ignore_setting_up' );
	if ( !$check && current_user_can( 'manage_options' ) ) {
		echo '<div id="lpr-setting-up" class="updated"><p>';
		echo sprintf(
			__( '<strong>LearnPress is almost ready</strong>. <a class="lpr-set-up" href="%s">Setting up</a> something right now is a good idea. That\'s better than you <a class="lpr-ignore lpr-set-up">ignore</a> the message.', 'learn_press' ),
			esc_url( add_query_arg( array( 'page' => 'learn_press_settings' ), admin_url( 'options-general.php' ) ) )
		);
		echo '</p></div>';
	}

	// Add notice if no rewrite rules are enabled
	global $wp_rewrite;
	if ( learn_press_has_profile_method() ) {
		if ( empty( $wp_rewrite->permalink_structure ) ) {
			echo '<div class="fade error"><p>';
			echo sprintf(
				wp_kses(
					__( '<strong>LearnPress Profile is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'learn_press' ),
					array(
						'a'      => array(
							'href' => array()
						),
						'strong' => array()
					)
				),
				admin_url( 'options-permalink.php' )
			);
			echo '</p></div>';
		}
	}
}