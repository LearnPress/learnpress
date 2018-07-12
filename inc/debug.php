<?php
/**
 * Search regexp \'[a-z]+ - [a-z]+\'
 */

/**
 * use http://example.com?debug=yes to execute the code in this file
 */
class LP_Unit_Test {
	public static function init() {
		//add_action( 'get_header', array( __CLASS__, 'test_emails' ) );
		//add_action( 'shutdown', array( __CLASS__, 'shutdown' ) );
	}

	public static function test_emails() {
		global $wp_rewrite;
		$emailer       = LP_Emails::instance();
		$email         = $emailer->emails['LP_Email_Completed_Order_User'];
		$email->enable = true;
		$email->trigger( 2147 );
		learn_press_debug( $email );
		die();
	}

	public static function shutdown() {
		if ( $times = LP_Debug::getLogTimes() ) {
			$total = 0;

			$styles = array(
				'position'   => 'fixed',
				'bottom'     => 0,
				'left'       => 0,
				'right'      => 0,
				'background' => '#000',
				'z-index'    => 999999,
				'font-size'  => '12px',
				'color'      => '#FFF',
				'max-height' => '200px',
				'overflow'   => 'auto',
				'opacity'    => 0.5
			);
			foreach ( $styles as $k => $v ) {
				$styles[ $k ] = "$k: $v";
			}
			echo '<div style="' . join( ';', $styles ) . '">';
			echo '<style>#query-monitor{z-index: 1000000 !important;}</style>';
			echo '<table>';
			foreach ( $times as $key => $time ) {
				$total_time = array_sum( $time );
				echo "<tr><td>{$key}</td><td>" . $total_time . '</td><td>' . sizeof( $time ) . '</td>' . '</tr>';
				$total += $total_time;
			}
			echo '</table>';
			echo "Total (" . sizeof( $times ) . ") = " . $total;
			echo '</div>';
		}
	}
}

LP_Unit_Test::init();

function get_files( $dir ) {
	$files  = scandir( $dir );
	$return = array();
	foreach ( $files as $file ) {
		if ( in_array( $file, array( '..', '.' ) ) ) {
			continue;
		}
		if ( is_file( $dir . '/' . $file ) ) {
			if ( strpos( $file, '.php' ) !== false ) {
				$return[] = $dir . '/' . $file;
			}
		} else {
			if ( ! in_array( $file, [ 'libraries', 'admin' ] ) ) {
				$return = array_merge( $return, get_files( $dir . '/' . $file ) );
			}
		}
	}

	return $return;
}

if ( empty( $_REQUEST['test-hooks'] ) ) {
	return;
}

$hooks = array();
$files = get_files( dirname( dirname( __FILE__ ) ) );
foreach ( $files as $file ) {

	if ( ! preg_match_all( '~add_action\(([^,]*), .*\)~', file_get_contents( $file ), $matches ) ) {
		continue;
	}
	echo "\n" . $file . "\n";
	print_r( $matches );
	foreach ( $matches[1] as $hook ) {
		$hook = str_replace( [ '"', "'" ], '', $hook );
		$hook = trim( $hook );
		if ( empty( $hooks[ $hook ] ) ) {
			$hooks[ $hook ] = 0;
		}
		$hooks[ $hook ] ++;
	}

}
arsort( $hooks );
print_r( $hooks );
die();