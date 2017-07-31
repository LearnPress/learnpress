<?php
/**
 * @author  ThimPress
 * @package LearnPress/Shortcodes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * LP_Shortcodes class
 */
class LP_Shortcodes {
	/**
	 * Init shortcodes
	 */
	public static function init() {

		$shortcodes = array(
			'confirm_order'       => __CLASS__ . '::confirm_order',
			'profile'             => array( __CLASS__, 'profile' ),
			'become_teacher_form' => __CLASS__ . '::become_teacher_form',
			'login_form'          => __CLASS__ . '::login_form',
			'checkout'            => __CLASS__ . '::checkout',
			'recent_courses'      => __CLASS__ . '::recent_courses',
			'featured_courses'    => __CLASS__ . '::featured_courses',
			'popular_courses'     => __CLASS__ . '::popular_courses'
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			$shortcode = "learn_press_{$shortcode}";
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

		add_action( 'the_post', array( __CLASS__, 'auto_shortcode' ) );

	}

	public static function auto_shortcode( $post ) {
		if ( ! is_page() ) {
			return $post;
		}

		if ( $post->ID == learn_press_get_page_id( 'profile' ) ) {
			if ( ! preg_match( '/\[learn_press_profile\s?(.*)\]/', $post->post_content ) ) {
				$post->post_content .= '[learn_press_profile]';
			}
		} elseif ( $post->ID == learn_press_get_page_id( 'checkout' ) ) {
			if ( ! preg_match( '/\[learn_press_checkout\s?(.*)\]/', $post->post_content ) ) {
				$post->post_content .= '[learn_press_checkout]';
			}
		} elseif ( $post->ID == learn_press_get_page_id( 'become_a_teacher' ) ) {
			if ( ! preg_match( '/\[learn_press_become_teacher_form\s?(.*)\]/', $post->post_content ) ) {
				$post->post_content .= '[learn_press_become_teacher_form]';
			}
		}

		return $post;
		if ( is_page() ) {
			global $post, $wp_query, $wp;
			$page_id = ! empty( $wp_query->queried_object_id ) ?
				$wp_query->queried_object_id :
				( ! empty( $wp_query->query_vars['page_id'] ) ? $wp_query->query_vars['page_id'] : - 1 );
			if ( $page_id == learn_press_get_page_id( 'checkout' ) ) {
				if ( ! preg_match( '/\[learn_press_checkout\s?(.*)\]/', $post->post_content ) ) {
					$post->post_content .= '[learn_press_checkout]';
				}
			} elseif ( $page_id == learn_press_get_page_id( 'profile' ) ) {
				learn_press_debug( $wp );


			} elseif ( $page_id == learn_press_get_page_id( 'become_a_teacher' ) ) {
				if ( ! preg_match( '/\[learn_press_become_teacher_form\s?(.*)\]/', $post->post_content ) ) {
					$post->post_content .= '[learn_press_become_teacher_form]';
				}
			}

			do_action( 'learn_press_auto_shortcode', $post, $template );
		}

		return $template;
	}

	public static function _login_form_bottom( $content, $args ) {
		if ( ! ( ! empty( $args['context'] ) && $args['context'] == 'learn-press-login' ) ) {
			return;
		}
	}

	/**
	 * Wrap content of a shortcode into wrapper element.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function wrapper_shortcode( $content ) {
		ob_start();
		learn_press_print_messages();
		$html = ob_get_clean();

		return '<div class="learnpress">' . $html . $content . '</div>';
	}

	/**
	 * Shortcode for displaying checkout form.
	 *
	 * @param mixed $atts
	 *
	 * @return string
	 */
	public static function checkout( $atts ) {
		return self::wrapper_shortcode( new LP_Shortcode_Checkout( $atts ) );
	}

	/**
	 * Shortcode for display content of user profile.
	 *
	 * @param mixed $atts
	 *
	 * @return string
	 */
	public static function profile( $atts ) {
		return self::wrapper_shortcode( new LP_Shortcode_Profile( $atts ) );
	}

	/**
	 * Shortcode for displaying recently courses added.
	 *
	 * @param mixed $atts
	 *
	 * @return string
	 */
	public static function recent_courses( $atts ) {
		return self::wrapper_shortcode( new LP_Shortcode_Recent_Courses( $atts ) );
	}

	/**
	 * Shortcode for displaying courses are set as featured.
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function featured_courses( $atts ) {
		return self::wrapper_shortcode( new LP_Shortcode_Featured_Courses( $atts ) );
	}

	/**
	 * Shortcode for displaying popular courses
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function popular_courses( $atts ) {
		return self::wrapper_shortcode( new LP_Shortcode_Popular_Courses( $atts ) );
	}

	/**
	 * Display a form let the user can be join as a teacher
	 *
	 * @param array|null
	 *
	 * @return string
	 */
	public static function become_teacher_form( $atts ) {
		return self::wrapper_shortcode( new LP_Shortcode_Become_A_Teacher( $atts ) );
	}

	/**
	 * Shortcode content for "Confirm Order" page
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function confirm_order( $atts = null ) {
		$atts = shortcode_atts(
			array(
				'order_id' => ! empty( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0
			),
			$atts
		);

		$order_id = null;

		extract( $atts );
		ob_start();

		$order = learn_press_get_order( $order_id );

		if ( $order ) {
			learn_press_get_template( 'order/confirm.php', array( 'order' => $order ) );
		}

		return self::wrapper_shortcode( ob_get_clean() );
	}

	static function login_form( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'redirect' => ''
			),
			$atts
		);
		add_filter( 'login_form_bottom', array( __CLASS__, 'login_form_bottom' ), 10, 2 );

		return self::wrapper_shortcode( learn_press_get_template_content( 'profile/login-form.php', $atts ) );
	}

	public static function login_form_bottom( $html, $args ) {
		ob_start();
		?>
        <p>
            <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e( 'Forgot password?', 'learnpress' ); ?></a>
            &nbsp;|&nbsp;
            <a href="<?php echo wp_registration_url(); ?>"><?php _e( 'Create new account', 'learnpress' ); ?></a>
        </p>
		<?php $html .= ob_get_clean();

		return $html;
	}
}

LP_Shortcodes::init();
