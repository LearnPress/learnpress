<?php
/**
 * Template for displaying header of setup wizard.
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
    <title><?php esc_html_e( 'LearnPress &rsaquo; Setup Wizard', 'learnpress' ); ?></title>
	<?php wp_print_scripts( 'lp-setup' ); ?>
	<?php do_action( 'admin_print_styles' ); ?>
	<?php do_action( 'admin_print_scripts' ); ?>
	<?php do_action( 'admin_head' ); ?>

</head>
<body class="lp-setup wp-core-ui js">
<div id="content">
    <div class="logo">
        <a href="">
            <img src="https://ps.w.org/learnpress/assets/icon-128x128.png?rev=1176138">
        </a>
    </div>