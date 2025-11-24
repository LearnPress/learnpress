<?php
/**
 * Button Course Shortcode.
 *
 * @author   ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  3.0.2
 * @extends  LP_Abstract_Shortcode
 * @depreacted 4.3.0
 */
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Button_Course' ) ) {

	/**
	 * Class LP_Shortcode_Button_Course
	 *
	 * @since 3.0.0
	 */
	class LP_Shortcode_Button_Course extends LP_Abstract_Shortcode {

		/**
		 * LP_Shortcode_Button_Course constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
			$this->_atts = shortcode_atts(
				array(
					'id' => 0,
				),
				$this->_atts
			);
		}

		/**
		 * Output button course.
		 *
		 * @return string
		 */
		public function output() {
			wp_enqueue_style( 'learnpress' );
			ob_start();

			$atts = $this->_atts;

			if ( 'current' === $atts['id'] ) {
				$course_id = learn_press_is_course() ? get_the_ID() : 0;
			} else {
				$course_id = $atts['id'];
			}

			try {
				$singleCourseTemplate = SingleCourseTemplate::instance();
				$courseModel          = CourseModel::find( $course_id, true );
				if ( ! $courseModel ) {
					return '';
				}

				$userModel = UserModel::find( get_current_user_id(), true );

				// Load js button course.
				wp_enqueue_script( 'lp-single-course' );

				if ( $courseModel->is_free() ) {
					echo $singleCourseTemplate->html_btn_enroll_course( $courseModel, $userModel );
				} elseif ( ! empty( $courseModel->get_external_link() ) ) {
					echo $singleCourseTemplate->html_btn_external( $courseModel, $userModel );
				} elseif ( $courseModel->has_no_enroll_requirement() ) {
					printf( '<a href="%s">%s</a>', $courseModel->get_permalink(), __( 'Learn now', 'learnpress' ) );
				} else {
					echo $singleCourseTemplate->html_btn_purchase_course( $courseModel, $userModel );
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return ob_get_clean();
		}

		/**
		 * Label button purchase.
		 *
		 * @param string $text
		 *
		 * @return string
		 * @deprecated 4.2.7.5
		 */
		public function button_text_purchase( string $text ): string {
			if ( $this->_atts['purchase_text'] ) {
				$text = $this->_atts['purchase_text'];
			} elseif ( $this->_atts['btn_label'] ) {
				$text = $this->_atts['btn_label'];
			}

			return $text;
		}

		/**
		 * Label button enroll.
		 *
		 * @param string $text
		 *
		 * @return string
		 * @deprecated 4.2.7.5
		 */
		public function button_text_enroll( string $text ): string {
			if ( $this->_atts['enroll_text'] ) {
				$text = $this->_atts['enroll_text'];
			} elseif ( $this->_atts['btn_label'] ) {
				$text = $this->_atts['btn_label'];
			}

			return $text;
		}
	}
}

new LP_Shortcode_Button_Course();
