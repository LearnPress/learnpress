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
		<p class="statistic-box__text"><?php echo esc_html( $item['label'] ?? '' ); ?></p>
		<span class="statistic-box__number"><?php echo esc_html( $item['count'] ?? 0 ); ?></span>
	</div>
	<?php
}
