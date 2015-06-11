<?php

class LPR_Export_Import{
    private static $_instance 	= false;
    private $_plugin_url		= '';
    private $_plugin_path		= '';
    /**
     * Constructor
     */
    function __construct(){

        $this->_plugin_path = dirname( __FILE__ );
        $this->_plugin_url 	= LPR_PLUGIN_URL . '/inc/core-addons/export-import';

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

        add_action( 'all_admin_notices', array( $this, 'import_upload_form' ) );
        add_action( 'load-edit.php', array( $this, 'do_bulk_actions' ) );
        add_action( 'admin_footer-edit.php', array( $this, 'course_bulk_actions' ), 2 );

        //add_filter( 'page_row_actions', array( $this, 'course_row_actions' ), 1 );

        add_action( 'admin_menu', array( $this, 'admin_menu' ), 1 );
        add_action( 'admin_notices', array( $this, 'admin_notice' ) );

        add_filter( 'learn_press_row_action_links', array( $this, 'course_row_actions' ) );
    }

    function admin_menu(){

    }

    function do_bulk_actions(){
        ///

        $wp_list_table = _get_list_table('WP_Posts_List_Table');
        $action = $wp_list_table->current_action();
        if( $action != 'export' ){
            if( !empty( $_REQUEST['export']) && $action = $_REQUEST['export'] ) {

            }
        }
        if( $action == 'export' ){

            switch($action) {
                case 'export':
                    $post_ids = isset( $_REQUEST['post'] ) ? (array)$_REQUEST['post'] : array();
                    require_once( $this->_plugin_path . '/incs/lpr-export-functions.php' );
                    require_once( $this->_plugin_path . '/incs/lpr-export.php' );
                    die();
                //wp_redirect( admin_url('edit.php?post_type=lpr_course') );

            }
        }elseif( isset( $_REQUEST['reset'] ) ){

        }else{
            $import_file = isset( $_FILES['lpr_import'] ) ? $_FILES['lpr_import'] : false;

            if( !$import_file ) return;
            $message = 0;
            require_once( $this->_plugin_path . '/incs/lpr-import-functions.php' );
            require_once( $this->_plugin_path . '/incs/lpr-import.php' );

            $lpr_import = new LPR_Import();
            $message = $lpr_import->dispatch();
            if( $message >= 1 ){
                $duplication_ids = $lpr_import->get_duplication_course();
                $message .= '&post=' . join(',', $duplication_ids);
                wp_redirect( admin_url('edit.php?post_type=lpr_course&course-imported=1&message=' . $message) );
            }else{
                wp_redirect( admin_url('edit.php?post_type=lpr_course&course-imported=error&message=' . $message) );
            }
            die();
        }


        //echo admin_url('edit.php?post_type=lpr_course');

    }

    function course_bulk_actions(){
        global $post_type;
        if( 'lpr_course' == $post_type ) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('<option>').val('export').text('<?php _e('Export Courses')?>').appendTo("select[name='action']");
                    $('<option>').val('export').text('<?php _e('Export Courses')?>').appendTo("select[name='action2']");
                });
            </script>
        <?php
        }
    }

    function course_row_actions( $actions ){
        global $post;
        if( 'lpr_course' == $post->post_type ){
            //$actions['lpr-export'] = sprintf('<a href="%s">%s</a>', admin_url('edit.php?post_type=lpr_course&action=export&post=' . $post->ID ) , __('Export Course') );
            $actions[] = array(
                'link'      => admin_url('edit.php?post_type=lpr_course&action=export&post=' . $post->ID ),
                'title'     => __( 'Export this course', 'learn_press' ),
                'class'     => 'lpr-export'
            );
        }
        return $actions;
    }

    function admin_scripts(){
        global $pagenow, $post_type;
        if( 'lpr_course' != $post_type || $pagenow != 'edit.php' ) return;

        wp_enqueue_script( 'lpr_export_import', $this->_plugin_url . '/assets/js/lpr-export-import.js', array( 'jquery' ) );
    }

    function admin_styles(){
        global $pagenow, $post_type;
        if( 'lpr_course' != $post_type || $pagenow != 'edit.php' ) return;
        wp_enqueue_style( 'lpr_export_import', $this->_plugin_url . '/assets/css/lpr-export-import.css' );
    }

    function import_upload_form(){
        global $pagenow, $post_type;
        if( 'lpr_course' != $post_type || $pagenow != 'edit.php' ) return;
        ?>
        <div id="lpr-import-upload-form">
            <a href="" class="">&times;</a>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="lpr_import" />
                <br />
                <button class="button"><?php _e( 'Import' );?></button>
            </form>
        </div>
    <?php
    }

    /**
     * displays the message in admin
     */
    function admin_notice(){
        global $post_type;
        if( 'lpr_course' != $post_type ) return;

        if( empty( $_REQUEST['course-imported'] ) ) return;

        $message = isset( $_REQUEST['message'] ) ? intval( $_REQUEST['message'] ) : 0;
        if( !$message ){
            $type = "error";
            $message_text = get_transient( 'lpr_import_error_message' );
            delete_transient( 'lpr_import_error_message' );
        }else {

            $type = "";
            $message_text = null;
            switch ($message) {

                case 1: // import success with out any duplicate course
                    $type = "updated";
                    $message_text = __('Imports all courses successfully', 'learn_press');
                    break;
                case 2: // import success with some of duplicate course
                    $type = "error";
                    $message_text = __('Some courses are duplicate, please select it in the list to duplicate if you want', 'learn_press');
                    break;

                default: // no course imported
                    $type = "error";
                    $message_text = __('No course is imported. Please try again', 'learn_press');
                    break;

            }
        }

        if( !$type ) return;
        if( !empty($_REQUEST['post']) ){
            $posts = get_posts( array('include' => $_REQUEST['post'], 'post_type' => 'lpr_course') );
            $message_text .= '<p>The following courses are duplicated:</p>';
            foreach( $posts as $post ){
                $message_text .= sprintf( '<p><a href="%s">%s</a></p>', get_edit_post_link( $post->ID ), $post->post_title );
            }
        }
        if( empty( $message_text ) ) return;
        ?>
        <div class="<?php echo $type;?>">
            <p><?php echo $message_text; ?></p>
        </div>
    <?php
    }

    /**
     * Get the url of this plugin
     *
     * @var     $sub    string  Optional - The sub-path to append to url of plugin
     * @return  string
     */
    function get_plugin_url( $sub = '' ){
        return $this->_plugin_url . ( $sub ? '/' . $sub : '' );
    }

    /**
     * Get the path of this plugin
     *
     * @var     $sub    string  Optional - The sub-path to append to path of plugin
     * @return  string
     */
    function get_plugin_path( $sub = '' ){
        return $this->_plugin_path . ( $sub ? '/' . $sub : '' );
    }

    /**
     * Get an instance of main class, create a new one if it's not loaded
     *
     * @return bool|LPR_Certificate
     */
    static function instance(){
        if( !self::$_instance ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
LPR_Export_Import::instance();
/*
define( 'LPR_EXPORT_IMPORT_PATH', dirname( __FILE__ ) );

function learn_press_register_export_import_addon() {

    $addon = array(
        'name'              => __( 'Export/Import', 'learn_press' ),
        'description'       => __( 'Export and Import your courses with all lesson and quiz in easiest way', 'learn_press' ),
        'author'            => 'foobla',
        'author_url'        => 'http://thimpress.com',
        'file'              => LPR_EXPORT_IMPORT_PATH . '/load.php',
        'category'          => 'courses',
        'tag'               => 'core',
        'settings-callback' => '',
    );

    learn_press_addon_register( 'export-import-add-on', $addon );
}
add_action( 'learn_press_register_add_ons', 'learn_press_register_export_import_addon' );
*/