<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$template = get_option( 'template' );
switch( $template ) {
    case 'twentyeleven' :
        echo '<div id="primary"><div id="content" role="main" class="twentyeleven">';
        break;
    case 'twentytwelve' :
        echo '<div id="primary" class="site-content"><div id="content" role="main" class="twentytwelve">';
        break;
    case 'twentythirteen' :
        echo '<div id="primary" class="site-content"><div id="content" role="main" class="entry-content twentythirteen">';
        break;
    case 'twentyfourteen' :
        echo '<div id="primary" class="content-area"><div id="content" role="main" class="site-content twentyfourteen"><div class="tfwc">';
        break;
    case 'twentyfifteen' :

        echo '<div id="primary" class="content-area">';
		echo "\t" . '<main id="main" class="site-main twentyfifteen" role="main">';
        break;
    default :
        echo '<div id="container"><div id="content" role="main">';
        break;
}
echo '<!-- .learn-press --><div class="learn-press">';