<?php
/**
 * PHPUnit bootstrap for LearnPress unit tests.
 *
 * Uses Brain Monkey to stub WordPress functions — no WP core needed.
 */

declare( strict_types=1 );

// ── Composer autoloader (PSR-4: LearnPress\ → inc/) ─────────────────────────
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// ── Normalise timezone so LP_Datetime calculations are deterministic ──────────
date_default_timezone_set( 'UTC' );

// ── WordPress constants expected by LP classes ───────────────────────────────
defined( 'ABSPATH' )          || define( 'ABSPATH', '/fake/wp/' );
defined( 'WPINC' )            || define( 'WPINC', 'wp-includes' );
defined( 'WP_DEBUG' )         || define( 'WP_DEBUG', false );
defined( 'HOUR_IN_SECONDS' )  || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'DAY_IN_SECONDS' )   || define( 'DAY_IN_SECONDS', 86400 );

// ── LearnPress constants (minimal set) ───────────────────────────────────────
defined( 'LP_PLUGIN_FILE' )   || define( 'LP_PLUGIN_FILE', dirname( __DIR__ ) . '/learnpress.php' );
defined( 'LP_ABSPATH' )       || define( 'LP_ABSPATH', dirname( __DIR__ ) . '/' );
defined( 'LP_INC_PATH' )      || define( 'LP_INC_PATH', dirname( __DIR__ ) . '/inc/' );
defined( 'LP_VERSION' )       || define( 'LP_VERSION', '4.0.0-test' );
defined( 'LP_QUIZ_CPT' )      || define( 'LP_QUIZ_CPT', 'lp_quiz' );
defined( 'LP_LESSON_CPT' )    || define( 'LP_LESSON_CPT', 'lp_lesson' );
defined( 'LP_COURSE_CPT' )    || define( 'LP_COURSE_CPT', 'lp_course' );
defined( 'LP_TEACHER_ROLE' )  || define( 'LP_TEACHER_ROLE', 'lp_teacher' );
defined( 'LP_QUESTION_CPT' ) || define( 'LP_QUESTION_CPT', 'lp_question' );
defined( 'LP_ORDER_CPT' )    || define( 'LP_ORDER_CPT', 'lp_order' );
defined( 'ADMIN_ROLE' )      || define( 'ADMIN_ROLE', 'administrator' );

// Course / item status constants
defined( 'LP_COURSE_ENROLLED' )          || define( 'LP_COURSE_ENROLLED', 'enrolled' );
defined( 'LP_COURSE_FINISHED' )          || define( 'LP_COURSE_FINISHED', 'finished' );
defined( 'LP_COURSE_PURCHASED' )         || define( 'LP_COURSE_PURCHASED', 'purchased' );
defined( 'LP_ITEM_COMPLETED' )           || define( 'LP_ITEM_COMPLETED', 'completed' );
defined( 'LP_ITEM_STARTED' )             || define( 'LP_ITEM_STARTED', 'started' );
defined( 'LP_COURSE_GRADUATION_PASSED' ) || define( 'LP_COURSE_GRADUATION_PASSED', 'passed' );
defined( 'LP_COURSE_GRADUATION_FAILED' ) || define( 'LP_COURSE_GRADUATION_FAILED', 'failed' );

// ── WordPress stub classes ────────────────────────────────────────────────────
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public string $code;
		public string $message;
		public function __construct( string $code = '', string $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

// ── Legacy (non-PSR-4) LP classes required by CourseModel ────────────────────
// These are loaded manually because they use class-lp-* naming, not PSR-4.
require_once dirname( __DIR__ ) . '/inc/class-lp-datetime.php';
require_once dirname( __DIR__ ) . '/inc/cache/class-lp-cache.php';
