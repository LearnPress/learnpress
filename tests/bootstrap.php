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

// ── Legacy (non-PSR-4) LP classes required by CourseModel ────────────────────
// These are loaded manually because they use class-lp-* naming, not PSR-4.
require_once dirname( __DIR__ ) . '/inc/class-lp-datetime.php';