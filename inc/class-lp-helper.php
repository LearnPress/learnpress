<?php

/**
 * Class LP_Helper
 */
defined( 'ABSPATH' ) || exit;

class LP_Helper {

	/**
	 * Wrap function unserialize to fix issues with UTF-8 chars when encoding/decoding
	 * of serialize function.
	 *
	 * @param string $string
	 *
	 * @return mixed
	 */
	public static function maybe_unserialize( $string ) {
		if ( is_string( $string ) ) {

			$unserialized = maybe_unserialize( $string );
			if ( ! $unserialized && strlen( $string ) ) {
				$string = preg_replace_callback(
					'!s:(\d+):"(.*?)";!s',
					array( __CLASS__, '_unserialize_replace_callback' ),
					$string
				);

				$unserialized = maybe_unserialize( $string );
			}

			$string = $unserialized;
		}

		return $string;
	}

	public static function _unserialize_replace_callback( $m ) {
		$len    = strlen( $m[2] );
		$result = "s:$len:\"{$m[2]}\";";

		return $result;
	}

	/**
	 * Shuffle array and keep the keys
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function shuffle_assoc( &$array ) {
		$keys = array_keys( $array );
		shuffle( $keys );
		$new = array();
		foreach ( $keys as $key ) {
			$new[ $key ] = $array[ $key ];
		}
		$array = $new;

		return true;
	}

	/**
	 * Sanitize array by removing empty and/or duplicating values.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	public static function sanitize_array( $array ) {
		$array = array_filter( $array );
		$array = array_unique( $array );

		return $array;
	}

	/**
	 * MD5 an array.
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public static function array_to_md5( $array ) {
		settype( $array, 'array' );
		ksort( $array );

		return md5( serialize( $array ) );
	}

	/**
	 * Load posts from database into cache by ids
	 *
	 * @param array|int $ids
	 * @Todo: tungnx - need to review code
	 * @deprecated 4.1.6.9
	 */
	public static function cache_posts( $ids ) {
		//_deprecated_function( __FUNCTION__, '4.1.6.9' );
	}

	/**
	 * Sort an array by a field.
	 * Having some issue with default PHP usort function.
	 *
	 * @param array  $array .
	 * @param string $field .
	 * @param int    $default .
	 */
	public static function sort_by_priority( &$array, $field = 'priority', $default = 10 ) {
		foreach ( $array as $k => $item ) {
			if ( ! array_key_exists( $field, $item ) ) {
				$array[ $k ][ $field ] = $default;
			}
		}

		$priority = array_unique( wp_list_pluck( $array, $field ) );
		sort( $priority );
		$priority = array_fill_keys( $priority, array() );

		foreach ( $array as $k => $item ) {
			$priority[ $item[ $field ] ][] = $item;
		}

		$sorted = array();
		foreach ( $priority as $item ) {
			$sorted = array_merge( $sorted, $item );
		}
		$array = $sorted;
	}

	/**
	 * Merge two or more classes into one.
	 *
	 * @return array
	 */
	public static function merge_class() {
		if ( func_num_args() == 1 ) {
			return func_get_arg( 0 );
		} elseif ( func_num_args() == 0 ) {
			return null;
		}

		$classes = array();
		foreach ( func_get_args() as $class ) {
			if ( is_string( $class ) ) {
				$cls     = explode( ' ', $class );
				$classes = array_merge( $classes, $cls );
			} else {
				$classes = array_merge( $classes, $class );
			}
		}

		$classes = array_filter( $classes );
		$classes = array_unique( $classes );

		return $classes;
	}

	/**
	 * Sanitize order statuses.
	 * Add prefix lp- into each status if it is not exists.
	 *
	 * @param $statuses
	 *
	 * @return array|mixed
	 */
	public static function sanitize_order_status( &$statuses ) {
		if ( is_array( $statuses ) ) {
			foreach ( $statuses as $k => $status ) {
				if ( false === strpos( $status, 'lp-' ) ) {
					$statuses[ $k ] = "lp-{$status}";
				}
			}
		} else {
			$statuses = preg_split( '#\s+#', $statuses );
			self::sanitize_order_status( $statuses );
			if ( sizeof( $statuses ) == 1 ) {
				$statuses = reset( $statuses );
			}
		}

		return $statuses;
	}

	/**
	 * Merge two arrays recursive.
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return array
	 */
	public static function array_merge_recursive( &$array1, &$array2 ) {
		$merged = $array1;

		if ( is_array( $array1 ) && is_array( $array2 ) ) {
			foreach ( $array2 as $key => & $value ) {
				if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
					$merged[ $key ] = self::array_merge_recursive( $merged[ $key ], $value );
				} elseif ( is_numeric( $key ) ) {
					if ( ! in_array( $value, $merged ) ) {
						$merged[] = $value;
					}
				} else {
					$merged[ $key ] = $value;
				}
			}
		} elseif ( is_array( $array2 ) ) {
			$merged = $array2;
		}

		return $merged;
	}

	/**
	 * Encode the object to json format.
	 * Replace number, "false", "true" in couple of quotes with
	 * original value.
	 * Example:
	 *      Input: {
	 *          number: "1234",
	 *          true_value: "true",
	 *          false_value: "false"
	 *      }
	 *      Output: {
	 *          number: 1234,
	 *          true_value: true,
	 *          false_value: false
	 *      }
	 *
	 * @param array $data
	 *
	 * @return false|mixed|string
	 */
	public static function json_encode( $data ) {
		$data = wp_json_encode( $data );
		$data = preg_replace_callback(
			'~:"(([0-9]+)([.,]?)([0-9]?)|true|false)"~',
			array(
				__CLASS__,
				'_valid_json_value',
			),
			$data
		);

		return $data;
	}

	/**
	 * Callback function for json_encode method "json_encode".
	 *
	 * @param array $m
	 *
	 * @return string
	 */
	public static function _valid_json_value( $m ) {
		return str_replace( array( ':"', '"' ), array( ':', '' ), $m[0] );
	}

	/**
	 * Create LP static page.
	 *
	 * @param string $name
	 * @param string $assign_to - Optional. Assign to LP page after creating successful.
	 *
	 * @return bool|int|WP_Error
	 */
	public static function create_page( $name, $assign_to = '' ) {
		$args = array(
			'post_type'   => 'page',
			'post_title'  => $name,
			'post_status' => 'publish',
		);

		$page_id = wp_insert_post( $args );

		if ( ! $page_id ) {
			return false;
		}

		update_post_meta( $page_id, '_lp_page', 'yes' );

		if ( $assign_to ) {
			$pages = learn_press_static_pages();

			if ( ! empty( $pages[ $assign_to ] ) ) {
				update_option( "learn_press_{$assign_to}_page_id", $page_id );

				// Update cache
				$page_ids               = learn_press_static_page_ids();
				$page_ids[ $assign_to ] = $page_id;
				LP_Object_Cache::set( 'static-page-ids', $page_ids, 'learnpress' );
			}
		}

		return $page_id;
	}

	/**
	 * Wrap function ksort of PHP itself and support recursive.
	 *
	 * @param array $array
	 * @param int   $sort_flags
	 *
	 * @return bool
	 * @since 3.3.0
	 */
	public static function ksort( &$array, $sort_flags = SORT_REGULAR ) {
		if ( ! is_array( $array ) ) {
			return false;
		}

		ksort( $array, $sort_flags );

		foreach ( $array as &$arr ) {
			self::ksort( $arr, $sort_flags );
		}

		return true;
	}

	/**
	 * Return new array/object with the keys exists in list of props.
	 *
	 * @param array|string $props
	 * @param array|object $obj
	 *
	 * @return array|object
	 */
	public function pick( $props, $obj ) {
		$is_array  = is_array( $obj );
		$new_array = array();
		settype( $props, 'array' );

		foreach ( $props as $prop ) {
			if ( $is_array && array_key_exists( $prop, $obj ) ) {
				$new_array[ $prop ] = $obj[ $prop ];
			} elseif ( ! $is_array && property_exists( $obj, $prop ) ) {
				$new_array[ $prop ] = $obj->{$prop};
			}
		}

		return $is_array ? $new_array : (object) $new_array;
	}

	public static function list_pluck( $list, $field, $index_key = null ) {
		$newlist = array();

		if ( ! $index_key ) {
			/*
			 * This is simple. Could at some point wrap array_column()
			 * if we knew we had an array of arrays.
			 */
			foreach ( $list as $key => $value ) {
				if ( is_callable( array( $value, $field ) ) ) {
					$newlist[ $key ] = call_user_func( array( $value, $field ) );
				} elseif ( is_object( $value ) ) {
					$newlist[ $key ] = $value->$field;
				} else {
					$newlist[ $key ] = $value[ $field ];
				}
			}

			return $newlist;
		}

		/*
		 * When index_key is not set for a particular item, push the value
		 * to the end of the stack. This is how array_column() behaves.
		 */
		foreach ( $list as $value ) {
			if ( is_callable( array( $value, $field ) ) ) {
				if ( isset( $value->$index_key ) ) {
					$newlist[ $value->$index_key ] = call_user_func( array( $value, $field ) );
				} else {
					$newlist[] = call_user_func( array( $value, $field ) );
				}
			} elseif ( is_object( $value ) ) {
				if ( isset( $value->$index_key ) ) {
					$newlist[ $value->$index_key ] = $value->$field;
				} else {
					$newlist[] = $value->$field;
				}
			} else {
				if ( isset( $value[ $index_key ] ) ) {
					$newlist[ $value[ $index_key ] ] = $value[ $field ];
				} else {
					$newlist[] = $value[ $field ];
				}
			}
		}

		return $newlist;
	}

	/**
	 * Get the current url
	 *
	 * @return string
	 * @since  3.2.6.8
	 * @author tungnx
	 */
	public static function getUrlCurrent(): string {
		$schema      = is_ssl() ? 'https://' : 'http://';
		$http_host   = LP_Helper::sanitize_params_submitted( urldecode( $_SERVER['HTTP_HOST'] ?? '' ) );
		$request_uri = LP_Helper::sanitize_params_submitted( urldecode( $_SERVER['REQUEST_URI'] ?? '' ) );
		//$http_host   = LP_Helper::sanitize_params_submitted( filter_input( INPUT_SERVER, 'HTTP_HOST' ) ?? '' );
		//$request_uri = LP_Helper::sanitize_params_submitted( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ?? '' );

		return untrailingslashit( $schema . $http_host . $request_uri );
	}

	/**
	 * Check request is rest api
	 *
	 * @since 4.1.6.6
	 * @author tungnx
	 * @return bool
	 */
	public static function isRestApiLP(): bool {
		return strpos( self::getUrlCurrent(), '/wp-json/lp/' ) || strpos( self::getUrlCurrent(), '/wp-json/learnpress/' );
	}

	/**
	 * Sanitize string and array
	 *
	 * @param array|string $value
	 * @param string       $type_content
	 *
	 * @return array|string
	 * @since  3.2.7.1
	 * @author tungnx
	 */
	public static function sanitize_params_submitted( $value, string $type_content = 'text' ) {
		$value = wp_unslash( $value );

		if ( is_string( $value ) ) {
			switch ( $type_content ) {
				case 'html':
					$value = wp_kses_post( $value );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( $value );
					break;
				case 'key':
					$value = sanitize_key( $value );
					break;
				case 'int':
					$value = (int) $value;
					break;
				case 'float':
					$value = (float) $value;
					break;
				default:
					$value = sanitize_text_field( $value );
			}
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = self::sanitize_params_submitted( $v, $type_content );
			}
		}

		return $value;
	}

	/**
	 * Wrap function $wpdb->prepare(...) to support arguments as
	 * array.
	 *
	 * @param string      $query
	 * @param array|mixed $args
	 *
	 * @return string
	 * @example
	 *
	 * $this->prepare($sql, $one, $two, array($three, $four, $file))
	 * => $wpdb->prepare($sql, $one, $two, $three, $four, $file)
	 */
	public static function prepare( $query, $args ) {
		global $wpdb;

		$args = func_get_args();
		array_shift( $args );
		$new_args = array();

		foreach ( $args as $arg ) {
			if ( is_array( $arg ) ) {
				$new_args = array_merge( $new_args, $arg );
			} else {
				$new_args[] = $arg;
			}
		}

		return $wpdb->prepare( $query, $new_args );
	}

	public static function db_format_array( array $arr, string $format = '%d' ): string {
		$arr_formatted = array_fill( 0, sizeof( $arr ), $format );

		return join( ',', $arr_formatted );
	}

	/**
	 * Calculate percent of progress rows on db
	 *
	 * @param int $offset .
	 * @param int $limit .
	 * @param int $total_row .
	 *
	 * @return float
	 */
	public static function progress_percent( int $offset, int $limit, int $total_row ): float {
		if ( $total_row <= 0 ) {
			return 0;
		}

		$percent = ( $offset + $limit ) * 100 / $total_row;

		$percent = min( $percent, 100 );

		return floatval( number_format( $percent, 2 ) );
	}

	/**
	 * Convert array to string
	 * Ex: array("publish", "pending") to post_status IN(%s, %s)
	 *
	 * @param array $arr
	 *
	 * @return string
	 */
	public static function format_query_IN( array $arr ): string {
		$format = array_fill( 0, count( $arr ), '%s' );

		return join( ',', $format );
	}

	/**
	 * Get link lp checkout page
	 * without cache - because some cache(redis) will cache page with user anonymous
	 */
	public static function get_link_no_cache( string $link ): string {
		return esc_url_raw( add_query_arg( 'no-cache', uniqid(), $link ) );
	}

	/**
	 * Check string is json
	 *
	 * @param string $str
	 *
	 * @return bool
	 */
	public static function is_json( string $str ): bool {
		json_decode( $str );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Handle permalink structure for LP
	 *
	 * @return string
	 * @since 4.2.2
	 */
	public static function handle_lp_permalink_structure( $post_link, $post ) {
		if ( false === strpos( $post_link, '%' ) ) {
			return $post_link;
		}

		$find = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%post_id%',
		);

		$replace = array(
			date_i18n( 'Y', strtotime( $post->post_date ) ),
			date_i18n( 'm', strtotime( $post->post_date ) ),
			date_i18n( 'd', strtotime( $post->post_date ) ),
			date_i18n( 'H', strtotime( $post->post_date ) ),
			date_i18n( 'i', strtotime( $post->post_date ) ),
			date_i18n( 's', strtotime( $post->post_date ) ),
			$post->ID,
		);

		if ( strpos( $post_link, '%course_category%' ) && get_post_type( $post ) === LP_COURSE_CPT ) {
			// Get the custom taxonomy terms in use by this post
			$terms = get_the_terms( $post->ID, 'course_category' );

			if ( ! empty( $terms ) ) {
				$terms = wp_list_sort( $terms, 'term_id' );
				// order by ID
				$category_object = apply_filters(
					'learn_press_course_post_type_link_course_category',
					$terms[0],
					$terms,
					$post
				);
				$category_object = get_term( $category_object, 'course_category' );
				$course_category = $category_object->slug;

				$parent = $category_object->parent;
				if ( $parent ) {
					$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
					foreach ( $ancestors as $ancestor ) {
						$ancestor_object = get_term( $ancestor, 'course_category' );
						$course_category = $ancestor_object->slug . '/' . $course_category;
					}
				}
			} else {
				// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
				$course_category = _x( 'uncategorized', 'slug', 'learnpress' );
			}

			$find[]    = '%course_category%';
			$replace[] = $course_category;
		}

		return str_replace( $find, $replace, $post_link );
	}
}
