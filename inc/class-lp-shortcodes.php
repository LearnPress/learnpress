<?php
/**
 * Base class of LearnPress shortcodes and helper functions.
 *
 * @author   ThimPress
 * @category Shortcode
 * @package  Learnpress/Shortcodes
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcodes' ) ) {
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
				'profile'             => __CLASS__ . '::profile',
				'become_teacher_form' => __CLASS__ . '::become_teacher_form',
				'login_form'          => __CLASS__ . '::login_form',
				'register_form'       => __CLASS__ . '::register_form',
				'checkout'            => __CLASS__ . '::checkout',
				'recent_courses'      => __CLASS__ . '::recent_courses',
				'featured_courses'    => __CLASS__ . '::featured_courses',
				'popular_courses'     => __CLASS__ . '::popular_courses',
				'button_enroll'       => __CLASS__ . '::button_enroll',
				'button_purchase'     => __CLASS__ . '::button_purchase',
				'button_course'       => __CLASS__ . '::button_course',
				'course_curriculum'   => __CLASS__ . '::course_curriculum',
			);

			foreach ( $shortcodes as $shortcode => $function ) {
				$shortcode = "learn_press_{$shortcode}";
				add_shortcode( apply_filters( "learn-press/shortcode/{$shortcode}/tag", $shortcode ), $function );
			}

			add_action( 'template_include', array( __CLASS__, 'auto_shortcode' ), - 10, 2 );
		}

		/**
		 * Auto add shortcode into some default pages.
		 *
		 * @param $template
		 *
		 * @return mixed
		 */
		public static function auto_shortcode( $template ) {
			global $post;

			if ( ! $post ) {
				return $template;
			}

			if ( $post->ID == learn_press_get_page_id( 'checkout' ) ) {
				if ( ! preg_match( '/\[learn_press_checkout\s?(.*)\]/', $post->post_content ) ) {
					$post->post_content .= '[learn_press_checkout]';
				}
			} elseif ( $post->ID == learn_press_get_page_id( 'become_a_teacher' ) ) {
				if ( ! preg_match( '/\[learn_press_become_teacher_form\s?(.*)\]/', $post->post_content ) ) {
					$post->post_content .= '[learn_press_become_teacher_form]';
				}
			}

			return $template;
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

			try {
				$html .= $content->output();
			} catch ( Exception $ex ) {
				$html .= $ex->getMessage();
			}

			return '<div class="learnpress">' . $html . '</div>';
		}

		/**
		 * Displaying recently courses added.
		 *
		 * @param mixed $atts
		 *
		 * @return string
		 */
		public static function recent_courses( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Recent_Courses( $atts ) );
		}

		/**
		 * Displaying courses are set as featured.
		 *
		 * @param array $atts
		 *
		 * @return string
		 */
		public static function featured_courses( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Featured_Courses( $atts ) );
		}

		/**
		 * Displaying popular courses.
		 *
		 * @param array $atts
		 *
		 * @return string
		 */
		public static function popular_courses( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Popular_Courses( $atts ) );
		}

		/**
		 * Displaying checkout form.
		 *
		 * @param mixed $atts
		 *
		 * @return string
		 */
		public static function checkout( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Checkout( $atts ) );
		}

		/**
		 * Display content of user profile.
		 *
		 * @param mixed $atts
		 *
		 * @return string
		 */
		public static function profile( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Profile( $atts ) );
		}

		/**
		 * Display a form let the user can be join as a teacher.
		 *
		 * @param array|null
		 *
		 * @return string
		 */
		public static function become_teacher_form( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Become_A_Teacher( $atts ) );
		}

		/**
		 * Display a register user form.
		 *
		 * @param array|null
		 *
		 * @return string
		 */
		public static function register_form( $atts ) {
			return self::wrapper_shortcode( new LP_Shortcode_Register_Form( $atts ) );
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
					'order_id' => ! empty( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0,
				),
				$atts
			);

			$order_id = null;

			extract( $atts ); // phpcs:ignore

			ob_start();

			$order = learn_press_get_order( $order_id );

			if ( $order ) {
				learn_press_get_template( 'order/confirm.php', array( 'order' => $order ) );
			}

			return self::wrapper_shortcode( ob_get_clean() );
		}

		public static function login_form( $atts, $content = '' ) {
			$atts = shortcode_atts(
				array(
					'redirect' => '',
				),
				$atts
			);
			add_filter( 'login_form_bottom', array( __CLASS__, 'login_form_bottom' ), 10, 2 );

			return self::wrapper_shortcode( new LP_Shortcode_Login_Form( $atts ) );
		}


		public static function login_form_bottom( $html, $args ) {
			ob_start();
			?>
			<p>
				<a href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e( 'Forgot password?', 'learnpress' ); ?></a>
				&nbsp;|&nbsp;
				<a href="<?php echo wp_registration_url(); ?>"><?php esc_html_e( 'Create new account', 'learnpress' ); ?></a>
			</p>
			<?php
			$html .= ob_get_clean();

			return $html;
		}

		/**
		 * @param        $atts
		 * @param string $content
		 *
		 * @return LP_Shortcode_Button_Enroll
		 */
		public static function button_enroll( $atts, $content = '' ) {
			return new LP_Shortcode_Button_Enroll( $atts );
		}

		/**
		 * @param        $atts
		 * @param string $content
		 *
		 * @return LP_Shortcode_Button_Purchase
		 */
		public static function button_purchase( $atts, $content = '' ) {
			return new LP_Shortcode_Button_Purchase( $atts );
		}

		/**
		 * @param        $atts
		 * @param string $content
		 *
		 * @return LP_Shortcode_Button_Course
		 */
		public static function button_course( $atts, $content = '' ) {
			return new LP_Shortcode_Button_Course( $atts );
		}

		/**
		 * @param array  $atts
		 * @param string $content
		 *
		 * @return LP_Shortcode_Course_Curriculum
		 */
		public static function course_curriculum( $atts, $content = '' ) {
			return new LP_Shortcode_Course_Curriculum( $atts );
		}
	}
}

add_action( 'init', array( 'LP_Shortcodes', 'init' ) );
