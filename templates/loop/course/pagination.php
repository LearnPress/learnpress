<?php
/**
 * Template for displaying pagination of course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/pagination.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.1.1
 */

defined( 'ABSPATH' ) || exit();

global $wp_query;

$total = $total ?? $wp_query->max_num_pages;
$paged = $paged ?? get_query_var( 'paged' );
$base  = $base ?? esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) );

if ( $total <= 1 ) {
	return;
}
?>

<nav class="learn-press-pagination navigation pagination">
	<?php
	echo paginate_links(
		apply_filters(
			'learn_press_pagination_args',
			array(
				'base'      => $base,
				'format'    => '',
				'add_args'  => '',
				'current'   => max( 1, $paged ),
				'total'     => $total,
				'prev_text' => '<i class="fas fa-angle-left"></i>',
				'next_text' => '<i class="fas fa-angle-right"></i>',
				'type'      => 'list',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);
	?>
</nav>
