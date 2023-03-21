<?php

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Single_Course extends Abstract_Block_Template
{
    public $slug = 'single-lp_course';
    public $name = 'learnpress/single-course';
    public $title = 'Single Course (LearnPress)';
    public $description = 'Single Course Block Template';
    public $path_html_block_template_file = 'html/single-lp_course.html';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Render content of block tag
     *
     * @param array $attributes | Attributes of block tag.
     *
     * @return false|string
     */
    public function render_content_block_template(array $attributes)
    {
//      Debug::var_dump($attributes);
        return parent::render_content_block_template($attributes);
    }
}
