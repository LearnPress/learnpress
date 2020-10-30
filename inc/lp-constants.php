<?php
/**
 * Define common constants used by LearnPress
 */
include_once ABSPATH . 'wp-admin/includes/plugin.php';
$upload_dir  = wp_upload_dir();
$plugin_info = get_plugin_data( LP_PLUGIN_FILE );

// version
define( 'LEARNPRESS_VERSION', $plugin_info['Version'] );

//define( 'LP_WP_CONTENT', basename( WP_CONTENT_DIR ) );

// Plugin paths and urls
define( 'LP_PLUGIN_PATH', trailingslashit( plugin_dir_path( LP_PLUGIN_FILE ) ) );
//define( 'LP_CONTENT_PATH', '/wp-content/plugins/learnpress/' );
define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', LP_PLUGIN_FILE ) ) );
define( 'LP_JS_URL', LP_PLUGIN_URL . 'assets/js/' );
define( 'LP_CSS_URL', LP_PLUGIN_URL . 'assets/css/' );

// Log path
define( 'LP_LOG_PATH', $upload_dir['basedir'] . '/learn-press-logs/' );

// Turn on/off cart
define( 'LP_ENABLE_CART', false );

// Cache group id
define( 'LP_SESSION_CACHE_GROUP', 'learn_press_session_id' );

// Table prefix
define( 'LP_TABLE_PREFIX', 'learnpress_' );

// Define constants for custom post types
define( 'LP_COURSE_CPT', 'lp_course' );
define( 'LP_LESSON_CPT', 'lp_lesson' );
define( 'LP_QUESTION_CPT', 'lp_question' );
define( 'LP_QUIZ_CPT', 'lp_quiz' );
define( 'LP_ORDER_CPT', 'lp_order' );

// Role of user who is a teacher
define( 'LP_TEACHER_ROLE', 'lp_teacher' );

// Turn debug mode on/off
//define( 'LP_DEBUG', false);
define( 'LP_CACHE_RESOURCE', false );

// Options
define( 'LP_USE_ATTRIBUTES', false );
define( 'LP_WIDGET_PATH', LP_PLUGIN_PATH . 'inc/widgets' );
define( 'LP_WIDGET_URL', LP_PLUGIN_URL . 'inc/widgets' );

// Course access level
define( 'LP_COURSE_ACCESS_LEVEL_0', 0 ); // No accessible
define( 'LP_COURSE_ACCESS_LEVEL_10', 10 ); // Normal users
define( 'LP_COURSE_ACCESS_LEVEL_20', 20 ); // Author of course
define( 'LP_COURSE_ACCESS_LEVEL_30', 30 ); // Admin site
define( 'LP_COURSE_ACCESS_LEVEL_35', 35 ); // No require enrollment
define( 'LP_COURSE_ACCESS_LEVEL_40', 40 ); // Ordered but not completed
define( 'LP_COURSE_ACCESS_LEVEL_50', 50 ); // Order completed but not enrolled
define( 'LP_COURSE_ACCESS_LEVEL_60', 60 ); // User has already enrolled course
define( 'LP_COURSE_ACCESS_LEVEL_70', 70 ); // User has already finished course

// Error codes
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

// Pages
define( 'LP_PAGE_CHECKOUT', 'lp_page_checkout' );
define( 'LP_PAGE_COURSES', 'lp_page_courses' );
define( 'LP_PAGE_COURSE', 'lp_page_course' );
define( 'LP_PAGE_QIZ', 'lp_page_quiz' );
define( 'LP_PAGE_QUESTION', 'lp_page_question' );
define( 'LP_PAGE_PROFILE', 'lp_page_profile' );
define( 'LP_PAGE_BECOME_A_TEACHER', 'lp_page_become_a_teacher' );

/**
 * Thim Market library
 */
define( 'TMP_ROOT', LP_PLUGIN_PATH . 'inc/libraries/' );
