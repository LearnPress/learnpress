<?php
/**
 * Admin view for add-ons page display in admin under menu LearnPress -> Add ons
 *
 * @author  ThimPress
 * @package Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add-on page
 */
function learn_press_add_ons_page() {
	$current = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

	?>
	<div id="learn-press-add-ons-wrap" class="wrap">
	<h2><?php echo __( 'LearnPress Add-ons', 'learnpress' ); ?></h2>
	<!-- <p class="top-description"><?php _e( 'Add-ons are features that you can add or remove depending on your needs', 'learnpress' ); ?></p>-->
	<ul class="subsubsub">
		<?php
		do_action( 'learn_press_add_ons_before_head_tab' );
		if ( $tabs = learn_press_get_add_on_tabs() ) {
			if ( empty( $tabs[$current] ) ) {
				$tab_ids = array_keys( $tabs );
				$current = reset( $tab_ids );
			}
			$links = array();
			foreach ( $tabs as $id => $args ) {
				$class = array();
				if ( !empty( $args['class'] ) ) {
					if ( is_array( $args['class'] ) ) {
						$class = array_merge( $class, $args['class'] );
					} else {
						$class[] = $args['class'];
					}
				}

				$class = join( ' ', $class );
				if ( !empty( $args['url'] ) ) {
					$url = $args['url'];
				} else {
					$url = admin_url( 'admin.php?page=learn_press_add_ons&tab=' . $id );
				}
				$text = $args['text'];

				$links[] = sprintf( '<li class="%s"><a href="%s" class="%s">%s</a></li>', $class, $url, ( $current == $id ? 'current' : '' ), $text );
			}
			echo join( '|', $links );
		}
		do_action( 'learn_press_add_ons_after_head_tab' );
		?>
	</ul>
	<div class="clear"></div>
	<?php do_action( 'learn_press_add_ons_content_tab_' . $current, $current );	?>
	</div>
	<?php
}
