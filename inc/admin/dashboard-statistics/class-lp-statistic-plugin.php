<?php

defined( 'ABSPATH' ) || exit();

if ( !class_exists( 'LP_Statistic_Plugin' ) ) :

    class LP_Statistic_Plugin {

        /**
         * LearnPress statistic layout
         * 
         * @since 2.0
         */
        public static function render() {
            $plugins_data = self::get_data();
            if ( ! $plugins_data ) {
                ?>
                    <div class="rss-widget">
                        <ul>
                            <li>
                                <p><?php _e( 'No results found', 'learnpress' )  ?></p>
                            </li>
                        </ul>
                    </div>
                <?php
            } else {
                ?>
                    <div class="rss-widget">
                        <ul>
                            <li>
                                <a href="<?php echo esc_url( $plugins_data->homepage ) ?>" class="rsswidget" target="_blank"><?php echo esc_html( $plugins_data->name ) ?></a>
                            </li>
                            <li>
                                <?php printf( '<span class="rss-date"><strong>%s</strong></span>: %s', __( 'Downloaded', 'learnpress' ), number_format( $plugins_data->downloaded ) ) ?>
                                <?php printf( '<span class="rss-date"><strong>%s</strong></span>: %s', __( 'Active Installed', 'learnpress' ), number_format( $plugins_data->active_installs ) ) ?>
                            </li>
                        </ul>
                    </div>
                    <div class="rss-widget">
                        <ul>
                            <li>
                                <div class="rssSummary">
                                    <?php echo esc_html( $plugins_data->short_description ) ?>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <ul class="subsubsub">
                        <li>
                            <?php printf( '<strong>%s</strong>: %s', __( 'Published', 'learnpress' ), date_i18n( get_option( 'date_format' ), strtotime( $plugins_data->added ) ) ) ?> | 
                        </li>
                        <li>
                            <?php printf( '<strong>%s</strong>: %s', __( 'Updated', 'learnpress' ), date_i18n( get_option( 'date_format' ), strtotime( $plugins_data->last_updated ) ) ) ?> | 
                        </li>
                        <li>
                            <?php printf( '<strong>%s</strong>: %s', __( 'Current Version', 'learnpress' ), $plugins_data->version ) ?>
                        </li>
                    </ul>
                <?php //var_dump( $plugins_data );
            }
        }

        /**
         * Get data from wordpress.org
         * 
         * @since 2.0
         */
        public static function get_data() {

            if ( !function_exists( 'plugins_api' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            }

            if ( false === get_transient( 'learn_press_statistic_plugin' ) ) {
                // get plugin information from wordpress.org
                $api = plugins_api( 'plugin_information', array( 'slug' => 'learnpress', 'fields' => array(
                        'active_installs' => true,
                        'short_description' => true,
                        'description' => true,
                        'ratings'   => true
                    ) ) );
                set_transient( 'learn_press_statistic_plugin', $api, 12 * HOUR_IN_SECONDS );
            }

            return apply_filters( 'learn_press_statistic_plugin', get_transient( 'learn_press_statistic_plugin' ) );
        }

    }

endif;