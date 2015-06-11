<?php /*

<div class=" col-md-9">
    <div class="quiz-main" course-id="">
        <header class="entry-header row">
            <div class="entry-title col-md-8">
                <h2><?php the_title(); ?></h2>

                <h3><?php the_sub_title(); ?></h3>
            </div>
            <div class="entry-author col-md-4">
                <div class="description">
                    <div class="author-role">
                        <?php $user_roles = get_the_author_meta( 'roles' );
                        if ( is_array( $user_roles ) ) {
                            echo ucfirst( $user_roles[0] );
                        } else {
                            echo ucfirst( $user_roles );
                        }
                        ?>
                    </div>
                    <div class="author-name">
                        <?php the_author_meta( 'first_name' ); ?>
                        <?php the_author_meta( 'last_name' ); ?>
                    </div>
                </div>
                <div class="avatar">
                    <?php echo get_avatar( get_the_author_meta( 'user_email' ), '80', '' ); ?>
                </div>
            </div>
        </header>
        <div class="entry-content">
            <div class="content">
                <div class="quiz-question">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="nav_button_group">
        <button class="button-prev-question btn hidden"><i class="fa fa-arrow-left"></i></button>
        <button
            class="button-save-answer btn hidden"><?php esc_attr_e( "SAVE QUESTION ANSWER", 'learn_press' ) ?></button>
        <button class="button-next-question btn hidden"><i class="fa fa-arrow-right"></i></button>
    </div>
</div>
 */ ?>