<?php
/**
 * @author  ThimPress
 * @package LearnPress/Shortcodes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LP_Shortcodes class
 */
class LP_Shortcodes {
	/**
	 * Init shortcodes
	 */
	static function init() {
		$shortcodes = array(
			'learn_press_confirm_order'       => __CLASS__ . '::confirm_order',
			'learn_press_profile'             => __CLASS__ . '::profile',
			'learn_press_become_teacher_form' => __CLASS__ . '::become_teacher_form',
			'learn_press_cart'                => __CLASS__ . '::cart',
			'learn_press_checkout'            => __CLASS__ . '::checkout',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Checkout form
	 *
	 * @return string
	 */
	static function checkout() {
		global $wp;
		ob_start();

		if ( isset( $wp->query_vars['order-received'] ) ) {

			self::order_received( $wp->query_vars['order-received'] );

		} else {
			// Check cart has contents
			if ( LP()->cart->is_empty() ) {
				learn_press_get_template( 'checkout/empty-cart.php', array( 'checkout' => LP()->checkout() ) );
			} else {
				learn_press_get_template( 'checkout/form.php', array( 'checkout' => LP()->checkout() ) );
			}
		}

		return ob_get_clean();
	}

	private static function order_received( $order_id = 0 ) {

		learn_press_print_notices();

		$order = false;

		// Get the order
		$order_id  = absint( $order_id );
		$order_key = !empty( $_GET['key'] ) ? $_GET['key'] : '';

		if ( $order_id > 0 ) {
			$order = learn_press_get_order( $order_id );
			if ( $order->order_key != $order_key )
				unset( $order );
		}

		LP()->session->order_awaiting_payment = null;

		learn_press_get_template( 'checkout/order-received.php', array( 'order' => $order ) );
	}

	static function cart() {
		ob_start();
		// Check cart has contents
		if ( LP()->cart->is_empty() ) {
			learn_press_get_template( 'cart/empty-cart.php', array( 'cart' => LP()->cart ) );
		} else {
			learn_press_get_template( 'cart/form.php', array( 'cart' => LP()->cart ) );
		}
		return ob_get_clean();
	}

	static function confirm_order( $atts = null ) {
		$atts = shortcode_atts(
			array(
				'order_id' => !empty( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0
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
		return ob_get_clean();
	}

	/**
	 * Display a form let the user can be join as a teacher
	 */
	static function become_teacher_form( $atts ) {
		global $current_user;

		$user = new WP_User( $current_user->ID );
		if ( in_array( LP()->teacher_role, $user->roles ) ) {
			return __( "You are a teacher now", 'learn_press' );
		}

		if ( !is_user_logged_in() ) {
			return __( "Please login to fill out this form", 'learn_press' );
		}

		if ( !empty( $_REQUEST['become-a-teacher-send'] ) ) {
			return __( 'Your request has been sent! We will get in touch with you soon!', 'learn_press' );
		}
		get_currentuserinfo();
		$atts   = shortcode_atts(
			array(
				'method'             => 'post',
				'action'             => '',
				'title'              => __( 'Become a Teacher', 'learn_press' ),
				'description'        => __( 'Fill out your information and send to us to become a teacher', 'learn_press' ),
				'submit_button_text' => __( 'Submit', 'learn_press' )
			),
			$atts
		);
		$fields = array(
			'bat_name'  => array(
				'title'       => __( 'Name', 'learn_press' ),
				'type'        => 'text',
				'placeholder' => __( 'Your name', 'learn_press' ),
				'def'         => $current_user->display_name
			),
			'bat_email' => array(
				'title'       => __( 'Email', 'learn_press' ),
				'type'        => 'email',
				'placeholder' => __( 'Your email address', 'learn_press' ),
				'def'         => $current_user->user_email
			),
			'bat_phone' => array(
				'title'       => __( 'Phone', 'learn_press' ),
				'type'        => 'text',
				'placeholder' => __( 'Your phone number', 'learn_press' )
			)
		);
		$fields = apply_filters( 'learn_press_become_teacher_form_fields', $fields );
		ob_start();
		$form_template = learn_press_locate_template( 'global/become-teacher-form.php' );
		if ( file_exists( $form_template ) ) {
			require $form_template;
		}

		$html = ob_get_clean();
		ob_start();
		?>
		<script>
			$('form[name="become_teacher_form"]').submit(function () {
				var $form = $(this);
				$form.siblings('.error-message').fadeOut('fast', function () {
					$(this).remove()
				});
				if ($form.triggerHandler('become_teacher_send') !== false) {
					$.ajax({
						url     : $form.attr('action'),
						data    : $form.serialize(),
						dataType: 'html',
						type    : 'post',
						success : function (code) {
							if (code.indexOf('<!-- LP_AJAX_START -->') >= 0)
								code = code.split('<!-- LP_AJAX_START -->')[1];

							if (code.indexOf('<!-- LP_AJAX_END -->') >= 0)
								code = code.split('<!-- LP_AJAX_END -->')[0];
							var result = $.parseJSON(code);
							return;
							if (!result.error.length) {
								var url = window.location.href;
								if (url.indexOf('?') != -1) url += '&'
								else url += '?';

								url += 'become-a-teacher-send=1';
								window.location.href = url;
							} else {
								$.each(result.error, function () {
									$('<p class="error-message">' + this + '</p>').insertBefore($form);
								})
							}
						}
					});
				}
				return false;
			});
		</script>
		<?php
		$js = preg_replace( '!</?script>!', '', ob_get_clean() );
		//$js = preg_replace( '!\s+|\t+!', ' ', $js );
		learn_press_enqueue_script( $js );
		return $html;
	}

	static function profile() {
		global $wp_query;

		if ( isset( $wp_query->query['user'] ) ) {
			$user = get_user_by( 'login', $wp_query->query['user'] );
		} else {
			$user = get_user_by( 'id', get_current_user_id() );
		}

		$output = '';
		if ( !$user ) {
			ob_start();
			learn_press_display_message( sprintf( __( 'The user %s in not available!', 'learn_press' ), $wp_query->query['user'] ), 'error' );
			$output .= ob_get_clean();
			return $output;
		}
		ob_start();
		learn_press_get_template( 'profile/index.php', array( 'user' => LP_User::get_user( $user->id ) ) );
		return ob_get_clean();
	}
}

LP_Shortcodes::init();