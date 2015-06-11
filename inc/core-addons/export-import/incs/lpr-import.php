<?php
// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}

// include WXR file parsers
require dirname( __FILE__ ) . '/parsers.php';

if ( !class_exists( 'LPR_Import' ) ) {
    class LPR_Import
    {
        private $processed_posts = array();
        private $author_mapping = array();
        private $post_orphans = array();
        private $processed_authors = array();
        private $processed_terms = array();
        private $import_data = null;
        private $processed_thumbnails = array();

        private $posts_count    = 0;
        private $posts_imported = 0;
        private $posts_duplication = array();

        function __construct()
        {
            //$this->dispatch();
        }

        function dispatch()
        {

            $file = (array)$this->handle_upload();
            if( !empty( $file['file'] ) ) {
                return $this->import($file['file']);
            }else{
                if( is_array( $file ) && !empty( $file['error'] ) ){
                    set_transient( 'lpr_import_error_message', $file['error'], 60 * 60 );
                }
            }
            return 0;
        }

        private function reset_data(){
            return;
            global $wpdb;
            // delete all old courses in test mode
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE user_id NOT IN(1)");
            $wpdb->query("DELETE FROM {$wpdb->users} WHERE ID NOT IN(1)");
            $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type NOT IN('post', 'page')");
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key NOT IN('_wp_page_template')");

            // delete all old categories in test mode
            if ($terms = get_terms('course_category', array('hide_empty' => 0))) {
                foreach ($terms as $term)
                    wp_delete_term($term->term_id, $term->taxonomy);
            }

            // delete all old tags in test mode
            if ($terms = get_terms('course_tag', array('hide_empty' => 0))) {
                foreach ($terms as $term)
                    wp_delete_term($term->term_id, $term->taxonomy);
            }
        }
        function import($file)
        {
            $this->import_data = $this->parse($file);

            $posts_count    = 0;
            $posts_imported = 0;
            if ($authors = $this->import_data['authors']) {
                foreach ($this->import_data['authors'] as $old_author) {
                    $user_data = array(
                        'user_login' => $old_author['author_login'],
                        'user_pass' => wp_generate_password(),
                        'user_email' => isset($old_author['author_email']) ? $old_author['author_email'] : '',
                        'display_name' => $old_author['author_display_name'],
                        'first_name' => isset($old_author['author_first_name']) ? $old_author['author_first_name'] : '',
                        'last_name' => isset($old_author['author_last_name']) ? $old_author['author_last_name'] : '',
                    );
                    $user_id = wp_insert_user($user_data);

                    if (!is_wp_error($user_id)) {
                        if ($old_author['author_id'])
                            $this->processed_authors[$old_author['author_id']] = $user_id;
                        //$this->author_mapping[$santized_old_login] = $user_id;
                    } else {
                        if ($old_author['author_id'])
                            $this->processed_authors[$old_author['author_id']] = (int)get_current_user_id();
                    }
                }
            }

            if ($posts = $this->import_data['posts']) {
                // if have posts then import the categories and/or tags first
                // if success, store the new ID of category/tag into an array to map the old ID with new ID
                foreach ($posts as $post) {
                    if ( !empty( $post['terms'] ) ):
                        foreach ($post['terms'] as $term) {
                            if ($term['domain'] == 'course_category') {
                                $this->process_category($term, $post);
                            } elseif ($term['domain'] == 'course_tag') {
                                $this->process_tag($term);
                            }
                        }
                    endif;
                }

                // then import posts and map the new ID of category/tag/author for new post
                $this->posts_count    = count( $posts );
                $this->posts_imported = 0;
                foreach ($posts as $post) {
                    $post_id = $this->process_post($post);

                } //end foreach
            }
            // remove uploaded file
            @unlink($file);
            if( $this->posts_imported == 0 ) return 3;
            if( $this->posts_imported != $this->posts_count ) return 2;
            return 1;
        }

        function get_duplication_course(){
            return $this->posts_duplication;
        }

        /**
         * Create new category for course and return the ID if success
         *
         * @param   $cat    array
         * @param   $post   array
         * @return  mixed
         */
        function process_category($cat, $post)
        {
            $term_id = term_exists($cat['slug'], 'course_category');

            if ($term_id) {
                if (is_array($term_id)) $term_id = $term_id['term_id'];
                if (isset($cat['id'])) {
                    $this->processed_terms[intval($cat['id'])] = (int)$term_id;
                    return $this->processed_terms[intval($cat['id'])];
                }
            }

            $category_parent = empty($cat['parent']) ? 0 : term_exists($cat['parent'], 'course_category');
            if ($category_parent) {
                if (is_array($category_parent))
                    $category_parent = $category_parent['term_id'];
            } else {
                if (!empty($cat['parent']) && $cat['parent'] > 0) {
                    foreach ($post['terms'] as $t) {
                        if ($t['id'] == $cat['parent']) {
                            $category_parent = $this->process_category($t, $post);
                            break;
                        }
                    }
                }
            }
            $category_description = isset($cat['description']) ? $cat['description'] : '';
            $catarr = array(
                //'slug' => $cat['nicename'],
                'parent' => $category_parent ? (is_array($category_parent) ? $category_parent['term_id'] : $category_parent) : 0,
                'description' => $category_description
            );

            $term = wp_insert_term($cat['name'], 'course_category', $catarr);

            if (!is_wp_error($term)) {
                if (isset($cat['id'])) {
                    $this->processed_terms[intval($cat['id'])] = (int)$term['term_id'];
                    return $this->processed_terms[intval($cat['id'])];
                }
            } else {
            }

        }

        /**
         * Create new tag for course and return the ID if success
         *
         * @param   $tag    array
         * @param   $post   array
         * @return  mixed
         */
        function process_tag($tag, $post = array())
        {
            $term_id = term_exists($tag['slug'], 'course_tag');
            if ($term_id) {
                if (is_array($term_id)) $term_id = $term_id['term_id'];
                if (isset($tag['id']))
                    $this->processed_terms[intval($tag['id'])] = (int)$term_id;
                return;
            }

            $category_parent = empty($tag['parent']) ? 0 : term_exists($tag['parent']);
            $category_description = isset($tag['description']) ? $tag['description'] : '';
            $catarr = array(
                //'slug' => $tag['nicename'],
                'parent' => $category_parent ? (is_array($category_parent) ? $category_parent['term_id'] : $category_parent) : 0,
                'description' => $category_description
            );

            $term = wp_insert_term($tag['name'], 'course_tag', $catarr);
            if (!is_wp_error($term)) {
                if (isset($tag['id']))
                    $this->processed_terms[intval($tag['id'])] = (int)$term['term_id'];

            } else {
            }
        }

        /**
         * Create new thumbnail for course
         *
         * @param   $attachment     array
         * @param   $post_id        int
         * @return  void
         */
        function process_attachment( $attachment, $post_id ){
            if( !isset( $this->processed_thumbnails[ $attachment['id'] ] ) ) {
                // if it is an url, try to read it
                if( preg_match('!^https?://!', $attachment['data'] ) ){
                    $data = @file_get_contents( $attachment['data'] );
                }else{
                    $data = base64_decode( $attachment['data'] );
                }

                // create a temp file to upload
                if( $data ){
                    $ext = '';
                    switch( $attachment['mime_type'] ){
                        case 'image/jpeg': $ext = 'jpg'; break;
                        case 'image/png': $ext = 'png'; break;
                    }
                    if( $ext ) {
                        $wp_upload = wp_upload_dir();
                        $tmp = $wp_upload['path'] . '/' . $attachment['filename'] . '.' . $ext;
                        @file_put_contents($tmp, $data);
                        if( file_exists( $tmp ) ){
                            $filename = basename( $tmp );
                            $upload_file = wp_upload_bits( $filename, null, $data );
                            if (!$upload_file['error']) {
                                $wp_filetype = wp_check_filetype($filename, null );
                                $new_attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_parent' => $post_id,
                                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                                    'post_content' => '',
                                    'post_status' => 'inherit'
                                );
                                $attachment_id = wp_insert_attachment( $new_attachment, $upload_file['file'], $post_id );
                                if (!is_wp_error($attachment_id)) {
                                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                                    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                                    wp_update_attachment_metadata( $attachment_id,  $attachment_data );

                                    $this->processed_thumbnails[ $attachment['id'] ] = $attachment_id;
                                }
                            }
                        }
                        // remove tmp file
                        @unlink($tmp);
                    }
                }
            }

            // ensure the thumbnail is exists
            if( !empty( $this->processed_thumbnails[ $attachment['id'] ] ) ) {
                set_post_thumbnail($post_id, $this->processed_thumbnails[$attachment['id']]);
            }
        }

        function process_post($post)
        {
            if( $duplication_id = $this->post_exists( array('post_name' => $post['post_name'], 'post_title' => $post['post_title'] ) ) ){
                $this->posts_duplication[] = $duplication_id;
                return 0;
            }
            $post_id = 0;
            if (isset($this->processed_posts[$post['post_id']]) && !empty($post['post_id']))
                return $post_id;

            if ($post['status'] == 'auto-draft')
                return $post_id;

            if ('nav_menu_item' == $post['post_type']) {
                //$this->process_menu_item( $post );
                return $post_id;
            }
            $post_type_object = get_post_type_object($post['post_type']);
            $post_exists = post_exists($post['post_title'], '', $post['post_date']);
            if ($post_exists && get_post_type($post_exists) == $post['post_type']) {

            } else {
                $post_parent = (int)$post['post_parent'];
                if ($post_parent) {
                    // if we already know the parent, map it to the new local ID
                    if (isset($this->processed_posts[$post_parent])) {
                        $post_parent = $this->processed_posts[$post_parent];
                        // otherwise record the parent for later
                    } else {
                        $this->post_orphans[intval($post['post_id'])] = $post_parent;
                        $post_parent = 0;
                    }
                }

                // map the post author
                $author = sanitize_user($post['post_author'], true);
                if (isset($this->processed_authors[$post['post_author_id']]))
                    $author = $this->processed_authors[$post['post_author_id']];
                else
                    $author = (int)get_current_user_id();

                $postdata = array(
                    'import_id' => $post['post_id'], 'post_author' => $author, 'post_date' => $post['post_date'],
                    'post_date_gmt' => $post['post_date_gmt'], 'post_content' => $post['post_content'],
                    'post_excerpt' => $post['post_excerpt'], 'post_title' => $post['post_title'],
                    'post_status' => $post['status'], 'post_name' => $post['post_name'],
                    'comment_status' => $post['comment_status'], 'ping_status' => $post['ping_status'],
                    'guid' => $post['guid'], 'post_parent' => $post_parent, 'menu_order' => $post['menu_order'],
                    'post_type' => $post['post_type'], 'post_password' => $post['post_password']
                );

                $original_post_ID = $post['post_id'];
                $comment_post_ID = $post_id = wp_insert_post($postdata, true);

                if( $post_id ){
                    $this->posts_imported++;
                }
                if ($post['is_sticky'] == 1)
                    stick_post($post_id);

                if( !empty( $post['attachment'] ) && $attachment = $post['attachment'] ){
                    $this->process_attachment( $attachment, $post_id );
                }
            } // end if

            $this->processed_posts[intval($post['post_id'])] = (int)$post_id;

            // set tag or category for post
            if (!empty($post['terms'])) {
                foreach ($post['terms'] as $term) {
                    if ($term['domain'] == 'course_category') {
                        if (isset($this->processed_terms[$term['id']])) {
                            wp_set_object_terms($post_id, (int)$this->processed_terms[$term['id']], 'course_category', true);
                        }
                    } elseif ($term['domain'] == 'course_tag') {
                        if (isset($this->processed_terms[$term['id']])) {
                            wp_set_object_terms($post_id, (int)$this->processed_terms[$term['id']], 'course_tag', true);
                        }
                    }
                }
            }

            if (!empty($post['postmeta'])) {
                foreach ($post['postmeta'] as $meta) {
                    $key = apply_filters('import_post_meta_key', $meta['key'], $post_id, $post);
                    $value = false;

                    if ('_edit_last' == $key) {
                        if (isset($this->processed_authors[intval($meta['value'])]))
                            $value = $this->processed_authors[intval($meta['value'])];
                        else
                            $key = false;
                    }

                    if ($key) {
                        // export gets meta straight from the DB so could have a serialized string
                        if (!$value)
                            $value = maybe_unserialize($meta['value']);
                        switch ($key) {
                            case '_lpr_course_lesson_quiz':
                                break;
                            case '_lpr_course_certificate':
                                break;
                            case '_lpr_course_prerequisite':
                                continue;
                                if (!in_array($value, $this->processed_posts)) {
                                    foreach ($this->import_data['posts'] as $post2) {
                                        if ($post2['post_id'] == $value) {
                                            $value = $this->process_post($post2);
                                            break;
                                        }
                                    }
                                }
                                break;
                            case '_lpr_course_teacher':
                                continue;
                                break;
                        }
                        update_post_meta($post_id, $key, $value);
                        do_action('import_post_meta', $post_id, $key, $value);

                    } // end if key
                } // end foreach meta
            }
            return $post_id;
        }

        private function post_exists( $fields ) {
            global $wpdb;

            $fields = wp_parse_args(
                $fields,
                array(
                    'post_name'     => '',
                    'post_type'     => 'lpr_course',
                    'post_title'    => ''
                )
            );
            $query = "SELECT ID FROM $wpdb->posts WHERE 1=1";
            $args = array();
            extract( $fields );
            if ( !empty ( $post_name ) ) {
                $query .= " AND post_name LIKE '%s' ";
                $args[] = $post_name;
            }
            if ( !empty ( $post_type ) ) {
                $query .= " AND post_type = '%s' ";
                $args[] = $post_type;
            }

            if( !empty( $post_title ) ){
                $query .= " AND post_title = '%s' ";
                $args[] = $post_title;
            }
            //echo $wpdb->prepare($query, $args);die();
            if ( !empty ( $args ) )
                return $wpdb->get_var( $wpdb->prepare($query, $args) );

            return 0;
        }

        function parse($file)
        {
            $parser = new LPR_Export_Import_Parser();

            return $parser->parse($file);
        }

        function handle_upload()
        {
            $file = lpr_import_handle_upload($_FILES['lpr_import'], array('mimes' => array('xml' => 'text/xml')));
            return $file;
        }

    }
}
