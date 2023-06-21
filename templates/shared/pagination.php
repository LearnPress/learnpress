<?php
/**
 * The template for displaying archive pagination.
 *
 * @version 1.0.0
 * @since 4.2.3
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $total ) || ! isset( $base )
	|| ! isset( $format ) || ! isset( $current ) ) {
	return;
}

if ( $total <= 1 ) {
	return;
}
?>
<nav class="learn-press-pagination">
	<?php
	echo paginate_links(
		apply_filters(
			'learn-press/pagination_args',
			array(
				'base'      => $base,
				'format'    => $format,
				'add_args'  => false,
				'current'   => max( 1, $current ),
				'total'     => $total,
				'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
				'next_text' => is_rtl() ? '&larr;' : '&rarr;',
				'type'      => 'list',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);
	?>
</nav>
