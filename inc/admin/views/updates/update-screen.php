<?php
/**
 * Template for displaying update database page.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta name="viewport" content="width=device-width"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php esc_html_e( 'LearnPress &rsaquo; Update Database', 'learnpress' ); ?></title>
	<?php wp_print_scripts( 'lp-update' ); ?>
	<?php do_action( 'admin_print_styles' ); ?>
	<?php do_action( 'admin_head' ); ?>
    <script type="text/javascript">
        var LP_Settings = {
            siteurl: '<?php echo site_url();?>'
        }
    </script>
</head>
<body class="lp-update-database wp-core-ui js">
<div id="content">
    <div class="logo">
		<a href="javascript:void(0)">
			<?php $logoUrl = LP_PLUGIN_URL . '/assets/images/icon-128x128.png' ?>
			<img src="<?php echo esc_attr( esc_html( $logoUrl ) ) ?>">
		</a>
    </div>
    <div id="main">

        <h2><?php _e( 'LearnPress Update Database', 'learnpress' ); ?></h2>

        <p><?php _e( 'Before updating please ensure your site data is already backed up!', 'learnpress' ); ?></p>

        <form id="learn-press-update-form" class="lp-update-content" name="lp-update" method="post">
            <p class="finish-buttons">
                <a id="button-update" class="button button-primary"
                   href="<?php echo admin_url( 'index.php?do-update-learnpress=yes' ); ?>"><?php _e( 'Run Updater', 'learnpress' ); ?></a>
                <a class="button"
                   href="<?php echo ( $redirect = LP_Request::get_string( 'redirect' ) ) ? $redirect : admin_url( 'index.php' ); ?>">
					<?php _e( 'Back', 'learnpress' ); ?>
                </a>
            </p>
        </form>
        <span class="icon-loading"></span>
    </div>
</div>
</body>
</html>