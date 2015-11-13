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

function the_sub_title( $id = null ) {
	global $post;
	if ( isset( $id ) ) {
		$return = get_post_meta( $id, '_lpr_sub_title' );
	} else {
		$return = get_post_meta( $post->ID, '_lpr_sub_title', true );
	}
	if ( isset( $return ) && strlen( $return ) > 5 ) {
		echo $return;
	}

}

if ( !function_exists( 'thim_get_currency_symbol' ) ) {
	/**
	 * Get Currency symbol.
	 *
	 * @param string $currency (default: '')
	 *
	 * @return string
	 */
	function thim_get_currency_symbol( $currency = '' ) {
		if ( !$currency ) {
//			$currency = get_woocommerce_currency();
		}

		switch ( $currency ) {
			case 'AED' :
				$currency_symbol = '';
				break;
			case 'BDT':
				$currency_symbol = '&#2547;&nbsp;';
				break;
			case 'BRL' :
				$currency_symbol = '&#82;&#36;';
				break;
			case 'BGN' :
				$currency_symbol = '&#1083;&#1074;.';
				break;
			case 'AUD' :
			case 'CAD' :
			case 'CLP' :
			case 'COP' :
			case 'MXN' :
			case 'NZD' :
			case 'HKD' :
			case 'SGD' :
			case 'USD' :
				$currency_symbol = '&#36;';
				break;
			case 'EUR' :
				$currency_symbol = '&euro;';
				break;
			case 'CNY' :
			case 'RMB' :
			case 'JPY' :
				$currency_symbol = '&yen;';
				break;
			case 'RUB' :
				$currency_symbol = '&#1088;&#1091;&#1073;.';
				break;
			case 'KRW' :
				$currency_symbol = '&#8361;';
				break;
			case 'PYG' :
				$currency_symbol = '&#8370;';
				break;
			case 'TRY' :
				$currency_symbol = '&#8378;';
				break;
			case 'NOK' :
				$currency_symbol = '&#107;&#114;';
				break;
			case 'ZAR' :
				$currency_symbol = '&#82;';
				break;
			case 'CZK' :
				$currency_symbol = '&#75;&#269;';
				break;
			case 'MYR' :
				$currency_symbol = '&#82;&#77;';
				break;
			case 'DKK' :
				$currency_symbol = 'kr.';
				break;
			case 'HUF' :
				$currency_symbol = '&#70;&#116;';
				break;
			case 'IDR' :
				break;
				$currency_symbol = 'Rp';
				break;
			case 'INR' :
				$currency_symbol = 'Rs.';
				break;
			case 'NPR' :
				$currency_symbol = 'Rs.';
			case 'ISK' :
				$currency_symbol = 'Kr.';
				break;
			case 'ILS' :
				$currency_symbol = '&#8362;';
				break;
			case 'PHP' :
				$currency_symbol = '&#8369;';
				break;
			case 'PLN' :
				$currency_symbol = '&#122;&#322;';
				break;
			case 'SEK' :
				$currency_symbol = '&#107;&#114;';
				break;
			case 'CHF' :
				$currency_symbol = '&#67;&#72;&#70;';
				break;
			case 'TWD' :
				$currency_symbol = '&#78;&#84;&#36;';
				break;
			case 'THB' :
				$currency_symbol = '&#3647;';
				break;
			case 'GBP' :
				$currency_symbol = '&pound;';
				break;
			case 'RON' :
				$currency_symbol = 'lei';
				break;
			case 'VND' :
				$currency_symbol = '&#8363;';
				break;
			case 'NGN' :
				$currency_symbol = '&#8358;';
				break;
			case 'HRK' :
				$currency_symbol = 'Kn';
				break;
			case 'EGP' :
				$currency_symbol = 'EGP';
				break;
			case 'DOP' :
				$currency_symbol = 'RD&#36;';
				break;
			case 'KIP' :
				$currency_symbol = '&#8365;';
				break;
			default    :
				$currency_symbol = '';
				break;
		}

		return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
	}

}

add_action( 'wp_ajax_load_curriculum_template', 'load_curriculum_template' );
function load_curriculum_template() {
	global $post;
	$user_id = get_current_user_id();

	if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], 'user' . $user_id ) ) {
		$id = intval( $_POST['id'] );

		if ( get_post_type( $id ) != LP_COURSE_CPT ) {
			echo __( 'Invalid ID', 'learn_press' );
			die();
		}
		load_template( LP_PLUGIN_URL . '/templates/single-course-feature.php' );
	} else {
		echo __( 'Unable to take the course', 'learn_press' );
	}

	die();
}

// remove author metabox from teachers in editor screen.
// add_action( 'admin_head-post-new.php', 'learn_press_remove_author_box' );
// add_action( 'admin_head-post.php', 'learn_press_remove_author_box' );
function learn_press_remove_author_box() {
	if ( current_user_can( LP()->teacher_role ) ) {
		remove_meta_box( 'authordiv', LP()->course_post_type, 'normal' );
		remove_meta_box( 'authordiv', LP()->lesson_post_type, 'normal' );
		remove_meta_box( 'authordiv', LP()->quiz_post_type, 'normal' );
		remove_meta_box( 'authordiv', LP()->question_post_type, 'normal' );
	}
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
