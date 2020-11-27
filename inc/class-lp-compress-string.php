<?php
class LP_Compress_String{
	public static function decompressString( $compressed ) {
		$compressed = explode( ",", $compressed );
		$dictSize   = 256;
		$dictionary = array();
		for ( $i = 1; $i < 256; $i ++ ) {
			$dictionary[ $i ] = chr( $i );
		}
		$w      = chr( $compressed[0] );
		$result = $w;
		for ( $i = 1; $i < count( $compressed ); $i ++ ) {
			$entry = "";
			$k     = $compressed[ $i ];
			if ( isset( $dictionary[ $k ] ) ) {
				$entry = $dictionary[ $k ];
			} else if ( $k == $dictSize ) {
				$entry = $w . self::charAt( $w, 0 );
			} else {
				return null;
			}
			$result                     .= $entry;
			$dictionary[ $dictSize ++ ] = $w . self::charAt( $entry, 0 );
			$w                          = $entry;
		}

		return $result;
	}

	public static function charAt( $string, $index ) {
		if ( $index < mb_strlen( $string ) ) {
			return mb_substr( $string, $index, 1 );
		} else {
			return - 1;
		}
	}

	public static function chr($u){
		return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
	}

	public static function toCharCodes($c){
		$x = 'charCodeAt';
		$b = '';
		$e = array();
		$f = str_split($c );
		$d = [];
		$a = $f[0];
		$g = 256;
		for ( $b = 1; $b < sizeof( $f ); $b ++ ) {
			$c = $f[ $b ];
			if ( null != $e[ $a . $c ] ) {
				$a .= $c;
			} else {
				//$d[]          = 1 < sizeof( $a ) ? $e[ $a ] : $a[ $x ]( 0 );
				$d[]          = 1 < mb_strlen( $a ) ? $e[ $a ] : self::utf8_char_code_at( $a, 0 );
				$e[ $a . $c ] = $g;
				$g ++;
				$a = $c;
			}
		}
		$d[] = 1 < mb_strlen( $a ) ? $e[ $a ] : self::utf8_char_code_at( $a, 0 );

		return $d;
	}

	public static function compressString( $c ) {

		$d = self::toCharCodes($c);

		for ( $b = 0; $b < sizeof( $d ); $b ++ ) {
			$d[ $b ] = self::chr( $d[ $b ] );
		}

		return join( $d, "" );


//		$dictSize   = 256;
//		$dictionary = array();
//		for ( $i = 0; $i < 256; $i ++ ) {
//			$dictionary[ chr( $i ) ] = $i;
//		}
//		$w      = "";
//		$result = "";
//		for ( $i = 0; $i < strlen( $uncompressed ); $i ++ ) {
//			$c  = self::charAt( $uncompressed, $i );
//			$wc = $w . $c;
//			if ( isset( $dictionary[ $wc ] ) ) {
//				$w = $wc;
//			} else {
//				if ( $result != "" ) {
//					$result .= "," . $dictionary[ $w ];
//				} else {
//					$result .= $dictionary[ $w ];
//				}
//				$dictionary[ $wc ] = $dictSize ++;
//				$w                 = "" . $c;
//			}
//		}
//		if ( $w != "" ) {
//			if ( $result != "" ) {
//				$result .= "," . $dictionary[ $w ];
//			} else {
//				$result .= $dictionary[ $w ];
//
//			}
//
//			return $result;
//		}
	}

	public static function utf8_char_code_at($str, $index)
	{
		$char = '';
		$str_index = 0;

		$str = self::utf8_scrub($str);
		$len = strlen($str);

		for ($i = 0; $i < $len; $i += 1) {

			$char .= $str[$i];

			if (self::utf8_check_encoding($char)) {

				if ($str_index === $index) {
					return self::utf8_ord($char);
				}

				$char = '';
				$str_index += 1;
			}
		}

		return null;
	}

	public static function utf8_scrub($str)
	{
		return htmlspecialchars_decode(htmlspecialchars($str, ENT_SUBSTITUTE, 'UTF-8'));
	}

	public static function utf8_check_encoding($str)
	{
		return $str === self::utf8_scrub($str);
	}

	public static function utf8_ord($char)
	{
		$lead = ord($char[0]);

		if ($lead < 0x80) {
			return $lead;
		} else if ($lead < 0xE0) {
			return (($lead & 0x1F) << 6)
			       | (ord($char[1]) & 0x3F);
		} else if ($lead < 0xF0) {
			return (($lead &  0xF) << 12)
			       | ((ord($char[1]) & 0x3F) <<  6)
			       |  (ord($char[2]) & 0x3F);
		} else {
			return (($lead &  0x7) << 18)
			       | ((ord($char[1]) & 0x3F) << 12)
			       | ((ord($char[2]) & 0x3F) <<  6)
			       |  (ord($char[3]) & 0x3F);
		}
	}
}
