<?php
define( 'WP_CACHE', TRUE ); // Added by WP Rocket
# Database Configuration
define( 'DB_NAME', 'wp_crisismedicine' );
define( 'DB_USER', 'crisismedicine' );
define( 'DB_PASSWORD', 'QcegIXBnxyVnkNkx6tni' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_HOST_SLAVE', '127.0.0.1' );
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_unicode_ci');
$table_prefix = 'wp_';

# Security Salts, Keys, Etc
define('AUTH_KEY',         'wvU?JaLb {Y;B8?l;jpJ6wjzB:Srq#O1$&Ld) A-F`8B3y@PN$PDzm48*-SleSih');
define('SECURE_AUTH_KEY',  'PBw:]ELVf|GLI<_G(Kp?w|$Y7-wf:%ss@$fN`9eEx,:M~-;3~t]dpUTKbMRpZwF}');
define('LOGGED_IN_KEY',    'y,c>pgjX14p}@~?h:)?ZT%`d-H1,6{-f=}.@Zg>EHvgq{=/0JmJFly7,EAfwIm|Q');
define('NONCE_KEY',        'V<9eBkmZN>K!I3Gxya&Z{BpEILof{]U?<YH4>)u/u?fL>z9)q{%rb3Wn+/v]GyBX');
define('AUTH_SALT',        '{6})X9&KxDgF0tE{Y-Fh=;G>86-zZrtiX$DUrHm2iZ2eDZ(>lNdEU/RWJO}=4SX)');
define('SECURE_AUTH_SALT', '^ALX5j0@3HTtm/p-uF|ZWyb QqBSJ|#(-OM&CSh9.~aS?0X;;}Z3pkRCekrF2sZ-');
define('LOGGED_IN_SALT',   'rL!}R7Z/zy(+z>FI)q#rUCzEsRV/KE.Y[?P9hCm{6~|ODPE>6U+e+_aPoC-7IB7:');
define('NONCE_SALT',       'c)jSl4NsN3S`xx@i9b=0G2HCI:|E9%Mp5|;=%TvX_}vC0%LdiwvN=tx$x|N3l~=]');


# Localized Language Stuff


define( 'WP_AUTO_UPDATE_CORE', false );

define( 'PWP_NAME', 'crisismedicine' );

define( 'FS_METHOD', 'direct' );

define( 'FS_CHMOD_DIR', 0775 );

define( 'FS_CHMOD_FILE', 0664 );

define( 'PWP_ROOT_DIR', '/nas/wp' );

define( 'WPE_APIKEY', '49e25d2780bfca52859b0e6b48aa3db2dd805b40' );

define( 'WPE_CLUSTER_ID', '100421' );

define( 'WPE_CLUSTER_TYPE', 'pod' );

define( 'WPE_ISP', true );

define( 'WPE_BPOD', false );

define( 'WPE_RO_FILESYSTEM', false );

define( 'WPE_LARGEFS_BUCKET', 'largefs.wpengine' );

define( 'WPE_SFTP_PORT', 2222 );

define( 'WPE_LBMASTER_IP', '' );

define( 'WPE_CDN_DISABLE_ALLOWED', false );

define( 'DISALLOW_FILE_MODS', FALSE );

define( 'DISALLOW_FILE_EDIT', FALSE );

define( 'DISABLE_WP_CRON', false );

define( 'WPE_FORCE_SSL_LOGIN', true );

define( 'FORCE_SSL_LOGIN', true );

/*SSLSTART*/ if ( isset($_SERVER['HTTP_X_WPE_SSL']) && $_SERVER['HTTP_X_WPE_SSL'] ) $_SERVER['HTTPS'] = 'on'; /*SSLEND*/

define( 'WPE_EXTERNAL_URL', false );

define( 'WP_POST_REVISIONS', FALSE );

define( 'WPE_WHITELABEL', 'wpengine' );

define( 'WP_TURN_OFF_ADMIN_BAR', false );

define( 'WPE_BETA_TESTER', false );

umask(0002);

$wpe_cdn_uris=array ( );

$wpe_no_cdn_uris=array ( );

$wpe_content_regexs=array ( );

$wpe_all_domains=array ( 0 => 'www.crisis-medicine.com', 1 => 'crisis-medicine.com', 2 => 'crisismedicine.wpengine.com', );

$wpe_varnish_servers=array ( 0 => 'pod-100421', );

$wpe_special_ips=array ( 0 => '104.196.186.157', );

$wpe_ec_servers=array ( );

$wpe_largefs=array ( );

$wpe_netdna_domains=array ( );

$wpe_netdna_domains_secure=array ( );

$wpe_netdna_push_domains=array ( );

$wpe_domain_mappings=array ( );

$memcached_servers=array ( 'default' =>  array ( 0 => 'unix:///tmp/memcached.sock', ), );


# WP Engine ID


# WP Engine Settings
define( 'WPE_MONITOR_ADMIN_AJAX', false );
//define('WP_DEBUG', false);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# other
define('WP_MEMORY_LIMIT', '1024M');
define('WP_MAX_MEMORY_LIMIT', '1024M');

# needed for wp-rocket
define('WP_HOME', 'https://www.crisis-medicine.com');
define('WP_SITEURL', 'https://www.crisis-medicine.com');


/* cloudflare when flex ssl is in use */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') { $_SERVER['HTTPS'] = 'on'; }
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

if ( ! defined( 'WP_CACHE') ) {
	define( 'WP_CACHE', TRUE );
}


# That's It. Pencils down
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
require_once(ABSPATH . 'wp-settings.php');