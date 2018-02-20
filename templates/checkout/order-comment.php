<<<<<<< HEAD
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$comment_heading = apply_filters( 'learn_press_order_comment_heading', __( 'Additional Information', 'learnpress' ) );

?>

<div class="learn-press-checkout-comment">

	<?php if ( $comment_heading ) { ?>

		<h3 class="learn-press-order-comment-heading"><?php echo $comment_heading; ?></h3>

	<?php } ?>
	<textarea name="order_comments"></textarea>

</div>
=======
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$comment_heading = apply_filters( 'learn_press_order_comment_heading', __( 'Additional Information', 'learnpress' ) );

?>

<div class="learn-press-checkout-comment">

	<?php if ( $comment_heading ) { ?>

		<h3 class="learn-press-order-comment-heading"><?php echo $comment_heading; ?></h3>

	<?php } ?>
	<textarea name="order_comments"></textarea>

</div>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
