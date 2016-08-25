<div class="upw-tabs">
    <a class="upw-tab-item active" data-toggle="upw-tab-general">General</a>
    <a class="upw-tab-item" data-toggle="upw-tab-display">Display</a>
    <a class="upw-tab-item" data-toggle="upw-tab-actions">Actions</a>
</div>
<div class="upw-tab upw-tab-general upw-show">
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e('Title:' , 'learnpress' ); ?></label>
        <input
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
            name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
            type="text"
            value="<?php echo esc_attr( $instance['title'] ); ?>">
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php _e( 'Number of posts:', 'learnpress' ) ; ?></label>
        <input
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"
            name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"
            type="number"
            value="<?php echo esc_attr( $instance['limit'] ); ?>">
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'css_class' ) ); ?>"><?php _e( 'CSS class:', 'learnpress' ) ; ?></label>
        <input
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'css_class' ) ); ?>"
            name="<?php echo esc_attr( $this->get_field_name( 'css_class' ) ); ?>"
            type="text"
            value="<?php echo esc_attr( $instance['css_class'] ); ?>">
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>"><?php _e( 'Choose template: ', 'learnpress' ); ?>&nbsp;</label>
        <select id="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'template' ) ); ?>" >
            <?php foreach ($this->templates as $template): ?>
                <option
                    <?php if ($template == $instance['template']) echo 'selected'; ?>
                    value="<?php echo $template ?>" >
                    <?php echo $template ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
</div>
<div class="upw-tab upw-tab-display upw-hide">
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_desc' ) ); ?>"><?php _e( 'Show description: ' , 'learnpress' ); ?>&nbsp;</label>
        <input type="checkbox"
            <?php if($instance['show_desc']) echo 'checked' ?>
               class="widefat"
               id="<?php echo esc_attr( $this->get_field_id( 'show_desc' ) ); ?>"
               name="<?php echo esc_attr( $this->get_field_name( 'show_desc' ) ); ?>" >
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'desc_length' ) ); ?>"><?php _e( 'Description max length:' , 'learnpress' ); ?></label>
        <input
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'desc_length' ) ); ?>"
            name="<?php echo esc_attr( $this->get_field_name( 'desc_length' ) ); ?>"
            type="number"
            value="<?php echo esc_attr( $instance['desc_length'] ); ?>">
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>"><?php _e( 'Show thumbnail: ' , 'learnpress'); ?>&nbsp;</label>
        <input type="checkbox"
            <?php if($instance['show_thumbnail']) echo 'checked' ?>
            <?php checked( $instance['show_thumbnail'] ) ?>
               class="widefat"
               id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>"
               name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnail' ) ); ?>" >
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_teacher' ) ); ?>"><?php _e( 'Show Teacher name: ', 'learnpress' ); ?>&nbsp;</label>
        <input type="checkbox"
            <?php if($instance['show_teacher']) echo 'checked' ?>
               class="widefat"
               id="<?php echo esc_attr( $this->get_field_id( 'show_teacher' ) ); ?>"
               name="<?php echo esc_attr( $this->get_field_name( 'show_teacher' ) ); ?>" >
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_lesson' ) ); ?>"><?php _e( 'Show number of lessons: ', 'learnpress' ); ?>&nbsp;</label>
        <input type="checkbox"
            <?php if($instance['show_lesson']) echo 'checked' ?>
               class="widefat"
               id="<?php echo esc_attr( $this->get_field_id( 'show_lesson' ) ); ?>"
               name="<?php echo esc_attr( $this->get_field_name( 'show_lesson' ) ); ?>" >
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_enrolled_students' ) ); ?>"><?php _e( 'Show enrolled number: ' , 'learnpress' ); ?>&nbsp;</label>
        <input type="checkbox"
            <?php if ($instance['show_enrolled_students']) echo 'checked' ?>
               class="widefat"
               id="<?php echo esc_attr( $this->get_field_id( 'show_enrolled_students' ) ); ?>"
               name="<?php echo esc_attr( $this->get_field_name( 'show_enrolled_students' ) ); ?>" >
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"><?php _e( 'Show price: ', 'learnpress' ); ?>&nbsp;</label>
        <input type="checkbox"
            <?php if ($instance['show_price']) echo 'checked' ?>
               class="widefat"
               id="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"
               name="<?php echo esc_attr( $this->get_field_name( 'show_price' ) ); ?>" >
    </p>
</div>
<div class="upw-tab upw-tab-actions upw-hide">
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'bottom_link' ) ); ?>"><?php _e( 'Bottom link: ' ,'learnpress'); ?>&nbsp;</label>
        <select
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'bottom_link' ) ); ?>"
            name="<?php echo esc_attr( $this->get_field_name( 'bottom_link' ) ); ?>">
            <option
                value=""><?php _e('hide bottom link', 'learnpress') ?></option>
            <option
                <?php if($instance['bottom_link'] == 'all_course'){ echo 'selected'; } ?>
                value="all_course"><?php _e('all course'); ?>
            </option>
        </select>
    </p>
    <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'bottom_link_text' ) ); ?>"><?php _e('Bottom link text: ' , 'learnpress'); ?>&nbsp;</label>
        <input
            type="text"
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'bottom_link_text' ) ); ?>"
            name="<?php echo esc_attr( $this->get_field_name( 'bottom_link_text' ) ); ?>"
            value="<?php echo $instance['bottom_link_text'] ?>" >
    </p>
</div>