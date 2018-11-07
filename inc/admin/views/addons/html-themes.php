<?php
/**
 * Admin View: Displaying all LearnPress's related themes.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit();

$last_checked = LP_Background_Query_Items::instance()->get_last_checked( 'plugins_tp' );
$check_url    = wp_nonce_url( add_query_arg( 'force-check-update', 'yes' ), 'lp-check-updates' );
?>

<p><?php printf( __( 'Last checked %s. <a href="%s">Check again</a>', 'learnpress' ), human_time_diff( $last_checked ), $check_url ); ?></p>

<?php
// get Thimpress Education themes
$education_themes = LP_Plugins_Helper::get_related_themes( 'education' );
// get Thimpress other themes
$other_themes = LP_Plugins_Helper::get_related_themes( 'other' );

if ( ! ( $education_themes || $other_themes ) ) {
	_e( 'No related themes.', 'learnpress' );

	return;
}

// theme link referral
$ref = learn_press_get_item_referral();

$all_themes = array(
	array(
		'title' => __( 'Education Support', 'learnpress' ),
		'items' => $education_themes,
	),
	array(
		'title' => __( 'Other', 'learnpress' ),
		'items' => $other_themes,
	)
);

foreach ( $all_themes as $themes ) {
	if ( $themes['items'] ) { ?>
        <h2>
			<?php echo $themes['title'] . ' (<span>' . sizeof( $themes['items'] ) . '</span>)'; ?>
        </h2>
        <ul class="addons-browse related-themes widefat">
			<?php foreach ( $themes['items'] as $item ) {
				$item['url'] = add_query_arg( $ref, $item['url'] );
				learn_press_admin_view( 'addons/html-loop-theme', array( 'theme' => $item, 'ref' => $ref ) );
			} ?>
        </ul>
		<?php
	}
}