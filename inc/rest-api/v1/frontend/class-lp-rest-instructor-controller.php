<?php

use LearnPress\Helpers\Template;

/**
 * REST API LP Instructor.
 *
 * @class LP_REST_Instructor_Controller
 * @author thimpress
 * @version 1.0.0
 */
class LP_REST_Instructor_Controller extends LP_Abstract_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'lp/v1';
        $this->rest_base = 'instructors';

        parent::__construct();
    }

    public function register_routes()
    {
        $this->routes = array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'list_instructors' ),
                'args'                => array(
                    'posts_per_page' => array(
                        'required'    => false,
                        'type'        => 'integer',
                        'description' => 'The posts per page must be an integer',
                    ),
                    'page'           => array(
                        'required'    => false,
                        'type'        => 'integer',
                        'description' => 'The page must be an integer',
                    ),
                ),
                'permission_callback' => '__return_true',
            ),
        );

        parent::register_routes();
    }

    /**
     * Get list instructor attend
     *
     * @param WP_REST_Request $request
     *
     * @return LP_REST_Response
     */
    public function list_instructors(WP_REST_Request $request): LP_REST_Response
    {
        $response = new LP_REST_Response();

        try {
            $params = $request->get_params();
            $args   = array(
                'number'  => $params['number'] ?? 10,
                'paged'   => $params['page'] ?? 1,
                'orderby' => $params['orderby'] ?? 'display_name',
                'order'   => $params['order'] ?? 'asc',
                'role'    => 'lp_teacher',
            );

            $query = new WP_User_Query($args);

            $instructors = $query->get_results();
            $template    = Template::instance();
            //Content
            ob_start();
            if (empty($instructors)) {
                throw new Exception(__('No Instructors found.', 'learnpress'));
            } else {
                foreach ($instructors as $instructor) {
                    $template->get_frontend_template(
                        apply_filters(
                            'learnpress/instructor-list/instructor-item',
                            'instructor-list/instructor-item.php'
                        ),
                        compact('instructor')
                    );
                }
            }
            $response->data->content = ob_get_clean();

            //Paginate
            $total = $query->get_total();

            $response->data->pagination = learn_press_get_template_content(
                'loop/course/pagination.php',
                array(
                    'total' => $total,
                    'paged' => $args['paged'],
                )
            );

            $response->status = 'success';
        } catch (Throwable $e) {
            ob_end_clean();
            $response->status        = 'error';
            $response->data->content = $e->getMessage();
            $response->message       = $e->getMessage();
        }

        return $response;
    }
}
