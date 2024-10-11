<?php
/**
 * Template for displaying count item.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $data ) ) {
	return;
}

foreach ( $data as $key => $item ) {
 	?>
	<div class="statistic-box" title="<?php echo esc_attr( $item['title'] ?? '' ); ?>">
		<div class="statistic-box__icon">
			<span class="lp-icon-<?php echo esc_attr($key) ?>"></span>
		</div>
		<div class="statistic-box__text">
			<label><?php echo esc_html( $item['label'] ?? '' ); ?></label>
			<span class="statistic-box__text__number"><?php echo esc_html( $item['count'] ?? 0 ); ?></span>
		</div>
	</div>
	<?php
}
