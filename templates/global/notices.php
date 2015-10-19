<?php
/**
 * Template for displaying all notices from queue
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! $notices ){
	return;
}

?>

<ul class="learn-press-error">
	<?php foreach ( $notices as $notice ) : ?>
	<li><?php echo wp_kses_post( $notice ); ?></li>
	<?php endforeach; ?>
</ul>
