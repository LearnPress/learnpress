<?php
/**
 * Single KPI card. Value/delta/subline are empty nodes filled by JS.
 *
 * Usage: learn_press_admin_view( 'statistics/parts/kpi-card', [ 'key' => 'net_sales', 'title' => __( 'Net sales', 'learnpress' ) ] );
 * learn_press_admin_view() extracts $args — default every var so a missing key renders empty, not a notice.
 *
 * @var string $key   Slug for the JS hook class .lp-kpi-{key}.
 * @var string $title Card heading.
 *
 * @since 4.4.2
 */

defined( 'ABSPATH' ) || exit();

$key   = $key ?? '';
$title = $title ?? '';
?>
<div class="lp-kpi-card lp-kpi-<?php echo esc_attr( $key ); ?>">
	<span class="lp-kpi-title"><?php echo esc_html( $title ); ?></span>
	<span class="lp-kpi-value">&ndash;</span>
	<span class="lp-kpi-delta"></span>
	<span class="lp-kpi-subline"></span>
</div>
