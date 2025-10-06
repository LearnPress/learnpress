<?php

/**
 * Class LP_Helper
 */

use LearnPress\Helpers\Template;

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
	 *
	 * @Todo: tungnx - need to review code - addon h5p v4.0.3 still use
	 * @deprecated 4.1.6.9
	 */
	public static function cache_posts( $ids ) {
		//_deprecated_function( __FUNCTION__, '4.1.6.9' );
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
	 * @param array $args
	 * @param string $key_option
	 *
	 * @return bool|int|WP_Error
	 */
	public static function create_page( array $args, string $key_option ) {
		$page_id = 0;

		try {
			if ( ! isset( $args['post_title'] ) ) {
				throw new Exception( __( 'Missing post title', 'learnpress' ) );
			}

			if ( preg_match( '#^learn_press_single_instructor_page_id.*#', $key_option ) ) {
				$args['post_content'] = '<!-- wp:shortcode -->[learn_press_single_instructor]<!-- /wp:shortcode -->';
			} elseif ( preg_match( '#^learn_press_instructors_page_id.*#', $key_option ) ) {
				$args['post_content'] = '<!-- wp:shortcode -->[learn_press_instructors]<!-- /wp:shortcode -->';
			} elseif ( preg_match( '#^learn_press_profile_page_id.*#', $key_option ) ) {
				$args['post_content'] = '<!-- wp:shortcode -->[learn_press_profile]<!-- /wp:shortcode -->';
			}

			$args = array_merge(
				[
					'post_title'     => '',
					'post_name'      => '',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'post_content'   => '',
					'post_author'    => get_current_user_id(),
				],
				$args
			);

			$page_id = wp_insert_post( $args );
			if ( ! $page_id ) {
				return false;
			}

			update_option( $key_option, $page_id );
			$lp_settings_cache = new LP_Settings_Cache( true );
			$lp_settings_cache->clean_lp_settings();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $page_id;
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
	 * @return bool
	 * @author tungnx
	 * @since 4.1.6.6
	 */
	public static function isRestApiLP(): bool {
		$restPrefix = '/' . rest_get_url_prefix();
		return strpos( self::getUrlCurrent(), $restPrefix . '/lp/' ) || strpos( self::getUrlCurrent(), $restPrefix . '/learnpress/' );
	}

	/**
	 * Sanitize string and array
	 *
	 * @param array|string $value
	 * @param string $type_content
	 * @param bool $unslash Set it is false when you don’t want to remove slashes (unslash) from $value
	 * for example, in cases involving LaTeX math syntax.
	 * @return array|string
	 * @since  3.2.7.1
	 * @author tungnx
	 */
	public static function sanitize_params_submitted( $value, string $type_content = 'text', $unslash = true ) {
		$value = $unslash ? wp_unslash( $value ) : $value;

		if ( is_string( $value ) ) {
			switch ( $type_content ) {
				case 'html':
					$value = Template::sanitize_html_content( $value );
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
					if ( is_callable( $type_content ) ) {
						$value = call_user_func( $type_content, $value );
					} else {
						$value = sanitize_text_field( $value );
					}
			}
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				unset( $value[ $k ] );
				$value[ sanitize_text_field( $k ) ] = self::sanitize_params_submitted( $v, $type_content, $unslash );
			}
		}

		return $value;
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
	 * @param null $associative
	 *
	 * @return mixed
	 * @throws Exception
	 * @since 4.1.6.4
	 * @version 1.0.1
	 */
	public static function json_decode( string $str, $associative = null ) {
		$obj = json_decode( $str, $associative );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new Exception( 'JSON decode: ' . json_last_error_msg() );
		}

		return $obj;
	}

	/**
	 * Handle permalink structure for LP
	 *
	 * @return string
	 * @since 4.2.2
	 * @version 1.0.4
	 */
	public static function handle_lp_permalink_structure( $post_link, $post ) {
		if ( false === strpos( $post_link, '%' ) ) {
			return $post_link;
		}

		$find    = [];
		$replace = [];
		if ( ! empty( $post->post_date_gmt ) ) {
			$find = array(
				'%year%',
				'%monthnum%',
				'%day%',
				'%hour%',
				'%minute%',
				'%second%',
				'%post_id%',
			);

			$time = strtotime( $post->post_date_gmt );

			$replace = array(
				date_i18n( 'Y', $time ),
				date_i18n( 'm', $time ),
				date_i18n( 'd', $time ),
				date_i18n( 'H', $time ),
				date_i18n( 'i', $time ),
				date_i18n( 's', $time ),
				$post->ID,
			);
		}

		if ( strpos( $post_link, '%course_category%' ) && get_post_type( $post ) === LP_COURSE_CPT ) {
			// Get the custom taxonomy terms in use by this post
			$terms = get_the_terms( $post->ID, 'course_category' );

			if ( ! empty( $terms ) ) {
				$terms = wp_list_sort( $terms, 'term_id' );
				// order by IDF
				$category_object = apply_filters(
					'learn_press_course_post_type_link_course_category',
					$terms[0],
					$terms,
					$post
				);
				$category_object = get_term( $category_object, 'course_category' );
				if ( ! $category_object instanceof WP_Term ) {
					return $post_link;
				}

				$course_category = $category_object->slug;
				$parent          = $category_object->parent;
				if ( $parent ) {
					$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
					foreach ( $ancestors as $ancestor ) {
						$ancestor_object = get_term( $ancestor, 'course_category' );
						if ( $ancestor_object instanceof WP_Term ) {
							$course_category = $ancestor_object->slug . '/' . $course_category;
						}
					}
				}
			} else {
				// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
				$course_category = _x( 'uncategorized', 'slug', 'learnpress' );
			}

			$find[]    = '%course_category%';
			$replace[] = urldecode( $course_category );
		}

		return apply_filters(
			'learn-press/single-course/permalink',
			str_replace( $find, $replace, $post_link ),
			$post
		);
	}

	/**
	 * Print variable script inline script tag.
	 * If $name_variable_script is empty,
	 * the script will be print as json with set $tag_args['type'] = application/json.
	 *
	 * @param string $name_variable_script
	 * @param array $data
	 * @param array $tag_args as ['type' => 'text/javascript', 'id' => '']
	 *
	 * @return void
	 * @version 1.0.1
	 * @since 4.2.5.5
	 */
	public static function print_inline_script_tag( string $name_variable_script, array $data, array $tag_args = [] ) {
		foreach ( $data as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$data[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		$data_json = wp_json_encode( $data );
		$script    = '';
		if ( ! empty( $name_variable_script ) ) {
			$script = "var {$name_variable_script} = {$data_json};";
		} elseif ( isset( $tag_args['type'] ) && $tag_args['type'] === 'application/json' ) {
			$script = $data_json;
		}
		wp_print_inline_script_tag( $script, $tag_args );
	}

	/**
	 * Get translation string single/plural
	 *
	 * @param float $number
	 * @param string $string_value
	 * @param bool $include_number
	 *
	 * @return string
	 * @since 4.2.7.4
	 * @version 1.0.0
	 */
	public static function get_i18n_string_plural( float $number, string $string_value = '', bool $include_number = true ): string {
		switch ( $string_value ) {
			case LP_COURSE_CPT:
				$plural = sprintf(
					_n( 'Course', 'Courses', $number, 'learnpress' ),
					$number
				);
				break;
			case LP_LESSON_CPT:
				$plural = sprintf(
					_n( 'Lesson', 'Lessons', $number, 'learnpress' ),
					$number
				);
				break;
			case LP_QUIZ_CPT:
				$plural = sprintf(
					_n( 'Quiz', 'Quizzes', $number, 'learnpress' ),
					$number
				);
				break;
			case LP_QUESTION_CPT:
				$plural = sprintf(
					_n( 'Question', 'Questions', $number, 'learnpress' ),
					$number
				);
				break;
			case 'lp_assignment':
				$plural = sprintf(
					_n( 'Assignment', 'Assignments', $number, 'learnpress' ),
					$number
				);
				break;
			case 'lp_h5p':
				$plural = sprintf(
					_n( 'H5P', 'H5Ps', $number, 'learnpress' ),
					$number
				);
				break;
			default:
				$plural = $string_value;
				break;
		}

		if ( $include_number ) {
			$plural = sprintf( '%s %s', $number, $plural );
		}

		return apply_filters( 'learn-press/i18n/plural', $plural, $number, $string_value, $include_number );
	}

	/**
	 * Get client ip address
	 *
	 * @return mixed|string
	 * @since 4.2.7.9
	 * @version 1.0.0
	 */
	public static function get_client_ip(): string {
		$ipaddress = 'ip-unknown';
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		}

		$ipaddress = (string) $ipaddress;
		$ipaddress = 'gip-' . $ipaddress;

		return $ipaddress;
	}
}
