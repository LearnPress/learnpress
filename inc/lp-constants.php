<?php
/**
 * Define common constants used by LearnPress
 */
include_once ABSPATH . 'wp-admin/includes/plugin.php';
$upload_dir  = wp_upload_dir();
$plugin_info = get_plugin_data( LP_PLUGIN_FILE );

// version.
define( 'LEARNPRESS_VERSION', $plugin_info['Version'] );
const LP_KEY_DB_VERSION = 'learnpress_db_version';
/**
 * @since 4.2.6.5
 */
define( "LP_TEXT_DOMAIN", $plugin_info['TextDomain'] );

// Plugin paths and urls.
define( 'LP_PLUGIN_PATH', plugin_dir_path( LP_PLUGIN_FILE ) );
define( 'LP_PLUGIN_BASENAME', plugin_basename( LP_PLUGIN_FILE ) );
define( 'LP_PLUGIN_FOLDER_NAME', str_replace( array( '/', basename( LP_PLUGIN_FILE ) ), '', LP_PLUGIN_BASENAME ) );
const LP_TEMPLATE_PATH = LP_PLUGIN_PATH . 'templates/';
define( 'LP_PLUGIN_URL', trailingslashit( plugins_url( '/', LP_PLUGIN_FILE ) ) );
const LP_JS_URL  = LP_PLUGIN_URL . 'assets/js/';
const LP_CSS_URL = LP_PLUGIN_URL . 'assets/css/';

// Log path.
define( 'LP_LOG_PATH', $upload_dir['basedir'] . '/learn-press-logs/' );

// Turn on/off cart.
const LP_ENABLE_CART = false;

// Table prefix.
const LP_TABLE_PREFIX = 'learnpress_';

// Define constants for custom post types.
const LP_COURSE_CPT   = 'lp_course';
const LP_LESSON_CPT   = 'lp_lesson';
const LP_QUESTION_CPT = 'lp_question';
const LP_QUIZ_CPT     = 'lp_quiz';
const LP_ORDER_CPT    = 'lp_order';

// Define constants for custom taxonomies.
const LP_COURSE_CATEGORY_TAX = 'course_category';
const LP_COURSE_TAXONOMY_TAG = 'course_tag';

// Role of user .
const LP_TEACHER_ROLE = 'lp_teacher';
const ADMIN_ROLE      = 'administrator';

// Options.
const LP_USE_ATTRIBUTES = false;

// Error codes.
const LP_REQUIRE_LOGIN                    = 120;
const LP_INVALID_QUIZ_OR_COURSE           = 140;
const LP_COURSE_IS_FINISHED               = 150;
const LP_QUIZ_HAS_STARTED_OR_COMPLETED    = 160;
const LP_ERROR_NO_PAYMENT_METHOD_SELECTED = 1000;
const LP_COMPLETE_ITEM_FAIL               = 170;

// Pages.
const LP_PAGE_CHECKOUT                 = 'lp_page_checkout';
const LP_PAGE_COURSES                  = 'lp_page_courses';
const LP_PAGE_SINGLE_COURSE            = 'lp_page_single_course';
const LP_PAGE_QUIZ                     = 'lp_page_quiz';
const LP_PAGE_QUESTION                 = 'lp_page_question';
const LP_PAGE_PROFILE                  = 'lp_page_profile';
const LP_PAGE_BECOME_A_TEACHER         = 'lp_page_become_a_teacher';
const LP_PAGE_SINGLE_COURSE_CURRICULUM = 'lp_page_single_course_curriculum';
const LP_PAGE_INSTRUCTORS              = 'lp_page_instructors';
const LP_PAGE_INSTRUCTOR               = 'lp_page_single_instructor';

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

// Status LP Order to compare.
const LP_ORDER_COMPLETED  = 'completed';
const LP_ORDER_PENDING    = 'pending';
const LP_ORDER_PROCESSING = 'processing';
const LP_ORDER_CANCELLED  = 'cancelled';
const LP_ORDER_FAILED     = 'failed';
const LP_ORDER_TRASH      = 'trash';

// Status LP Order to set DB.
const LP_ORDER_COMPLETED_DB  = 'lp-completed';
const LP_ORDER_PENDING_DB    = 'lp-pending';
const LP_ORDER_PROCESSING_DB = 'lp-processing';
const LP_ORDER_CANCELLED_DB  = 'lp-cancelled';
const LP_ORDER_FAILED_DB     = 'lp-failed';
const LP_ORDER_TRASH_DB      = 'lp-trash';

// LP Order type create via.
const LP_ORDER_CREATED_VIA_MANUAL = 'manual';

// Graduation user item course
const LP_COURSE_GRADUATION_IN_PROGRESS = 'in-progress';
const LP_COURSE_GRADUATION_PASSED      = 'passed';
const LP_COURSE_GRADUATION_FAILED      = 'failed';

// Enable lazy-load animation placeholder.
const LP_LAZY_LOAD_ANIMATION = true;
