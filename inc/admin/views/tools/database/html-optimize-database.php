<?php
/**
 * @template html-optimize-database
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
?>

<div class="card">
    <h2><?php _e( 'Optimize Database', 'learnpress' ); ?></h2>
    <p><?php echo sprintf( '+ %s', esc_html__( 'Scan database if have not index in table will create', 'learnpress' ) ); ?></p>
    <p class="tools-button">
        <a class="button lp-button-optimize-database"
           href="javascript:void(0)">
			<?php esc_html_e( 'Optimize now', 'learnpress' ); ?>
        </a>
        <span class="spinner"></span>
		<?php wp_nonce_field( 'lp-optimize-database', 'lp-nonce' ); ?>
    </p>
</div>
