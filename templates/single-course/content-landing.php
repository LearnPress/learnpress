<?php
/**
 * Template for displaying content of landing course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
learn_press_debug( LP()->cart->total, LP()->cart->get_cart());
$start = 1000000000;
$rate = 0.3;
for($i = 1; $i <=10;$i++){
    $start=$start+$start * $rate / 100;
}

echo $start;
?>

<?php do_action( 'learn_press_before_content_landing' ); ?>

<div class="course-landing-summary">

	<?php do_action( 'learn_press_content_landing_summary' ); ?>

</div>

<?php do_action( 'learn_press_after_content_landing' ); ?>
