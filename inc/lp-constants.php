<?php
/**
 * Define common constants used by LearnPress
 */
include_once ABSPATH . 'wp-admin/includes/plugin.php';
$upload_dir  = wp_upload_dir();
$plugin_info = get_plugin_data( LP_PLUGIN_FILE );

// version.
define( 'LEARNPRESS_VERSION', $plugin_info['Version'] );

//define( 'LP_WP_CONTENT', basename( WP_CONTENT_DIR ) );

// Plugin paths and urls.
define( 'LP_PLUGIN_PATH', trailingslashit( plugin_dir_path( LP_PLUGIN_FILE ) ) );
define( 'LP_TEMPLATE_PATH', LP_PLUGIN_PATH . 'templates/' );
//define( 'LP_CONTENT_PATH', '/' . LP_WP_CONTENT . '/plugins/learnpress/' );
define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', LP_PLUGIN_FILE ) ) );
define( 'LP_JS_URL', LP_PLUGIN_URL . 'assets/js/' );
define( 'LP_CSS_URL', LP_PLUGIN_URL . 'assets/css/' );

// Log path.
define( 'LP_LOG_PATH', $upload_dir['basedir'] . '/learn-press-logs/' );

// Turn on/off cart.
define( 'LP_ENABLE_CART', false );

// Cache group id.
define( 'LP_SESSION_CACHE_GROUP', 'learn_press_session_id' );

// Table prefix.
define( 'LP_TABLE_PREFIX', 'learnpress_' );

// Define constants for custom post types.
const LP_COURSE_CPT   = 'lp_course';
const LP_LESSON_CPT   = 'lp_lesson';
const LP_QUESTION_CPT = 'lp_question';
const LP_QUIZ_CPT     = 'lp_quiz';
const LP_ORDER_CPT    = 'lp_order';

// Role of user .
const LP_TEACHER_ROLE = 'lp_teacher';
const ADMIN_ROLE      = 'administrator';

// Turn debug mode on/off.
//define( 'LP_DEBUG', true );

// Options.
define( 'LP_USE_ATTRIBUTES', false );
define( 'LP_WIDGET_PATH', LP_PLUGIN_PATH . 'inc/widgets' );
define( 'LP_WIDGET_URL', LP_PLUGIN_URL . 'inc/widgets' );

// Course access level.
const LP_COURSE_ACCESS_LEVEL_0  = 0; // No accessible
const LP_COURSE_ACCESS_LEVEL_10 = 10; // Normal users
const LP_COURSE_ACCESS_LEVEL_20 = 20; // Author of course
const LP_COURSE_ACCESS_LEVEL_30 = 30; // Admin site
const LP_COURSE_ACCESS_LEVEL_35 = 35; // No require enrollment
const LP_COURSE_ACCESS_LEVEL_40 = 40; // Ordered but not completed
const LP_COURSE_ACCESS_LEVEL_50 = 50; // Order completed but not enrolled
const LP_COURSE_ACCESS_LEVEL_55 = 55; // Enrolled but has blocked (access level = 0)
const LP_COURSE_ACCESS_LEVEL_60 = 60; // User has already enrolled course
const LP_COURSE_ACCESS_LEVEL_70 = 70; // User has already finished course

// Error codes.
define( 'LP_INVALID_REQUEST', 100 );
define( 'LP_ACCESS_FORBIDDEN_OR_ITEM_IS_NOT_EXISTS', 110 );
define( 'LP_REQUIRE_LOGIN', 120 );
define( 'LP_PREVIEW_MODE', 130 );
define( 'LP_INVALID_QUIZ_OR_COURSE', 140 );
define( 'LP_COURSE_IS_FINISHED', 150 );
define( 'LP_QUIZ_HAS_STARTED_OR_COMPLETED', 160 );
define( 'LP_ERROR_NO_PAYMENT_METHOD_SELECTED', 1000 );
define( 'LP_COMPLETE_ITEM_FAIL', 170 );
define( 'LP_COMPRESS_ASSETS', false );

// Pages.
const LP_PAGE_CHECKOUT                 = 'lp_page_checkout';
const LP_PAGE_COURSES                  = 'lp_page_courses';
const LP_PAGE_SINGLE_COURSE            = 'lp_page_single_course';
const LP_PAGE_QUIZ                     = 'lp_page_quiz';
const LP_PAGE_QUESTION                 = 'lp_page_question';
const LP_PAGE_PROFILE                  = 'lp_page_profile';
const LP_PAGE_BECOME_A_TEACHER         = 'lp_page_become_a_teacher';
const LP_PAGE_SINGLE_COURSE_CURRICULUM = 'lp_page_single_course_curriculum';

// Key block course's item.
const LP_BLOCK_COURSE_FINISHED        = 'block_course_finished';
const LP_BLOCK_COURSE_DURATION_EXPIRE = 'block_course_duration_expire';
const LP_BLOCK_COURSE_PURCHASE        = 'block_course_purchased';

// Status user item course.
const LP_COURSE_ENROLLED  = 'enrolled';
const LP_COURSE_FINISHED  = 'finished';
const LP_COURSE_PURCHASED = 'purchased';
const LP_ITEM_COMPLETED   = 'completed';
const LP_ITEM_STARTED     = 'started';

// Graduation user item course
const LP_COURSE_GRADUATION_IN_PROGRESS = 'in-progress';
const LP_COURSE_GRADUATION_PASSED      = 'passed';
const LP_COURSE_GRADUATION_FAILED      = 'failed';

// Enable lazyload animation placeholder.
const LP_LAZY_LOAD_ANIMATION = true;

/**
 * Thim Market library
 */
define( 'TMP_ROOT', LP_PLUGIN_PATH . 'inc/libraries/' );
