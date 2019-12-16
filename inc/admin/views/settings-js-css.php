<?php
/**
 * @since 3.2.6
 */

defined( 'ABSPATH' ) or die;
$exclude_admin_libraries    = LP()->settings()->get( 'exclude_admin_libraries' );
$exclude_frontend_libraries = LP()->settings()->get( 'exclude_frontend_libraries' );

if ( ! $exclude_admin_libraries ) {
	$exclude_admin_libraries = array();
}

if ( ! $exclude_frontend_libraries ) {
	$exclude_frontend_libraries = array();
}

$admin_libraries = array(
	'vue'           => __( 'Vue', 'learnpress' ),
	'vuex'          => __( 'Vuex', 'learnpress' ),
	'vue-resource'  => __( 'Vue Resource', 'learnpress' ),
	'vue-draggable' => __( 'Vue Draggable', 'learnpress' ),
	'jquery-tipsy'  => __( 'jQuery tipsy', 'learnpress' ),
	'chartjs'       => __( 'Chart JS', 'learnpress' ),
	'font-awesome'  => __( 'Font awesome', 'learnpress' ),
);

$frontend_libraries = array(
	'vue'              => __( 'Vue', 'learnpress' ),
	'vuex'             => __( 'Vuex', 'learnpress' ),
	'vue-resource'     => __( 'Vue Resource', 'learnpress' ),
	'jquery-alert'     => __( 'jQuery alert', 'learnpress' ),
	'jquery-appear'    => __( 'jQuery appear', 'learnpress' ),
	'jquery-scrollto'  => __( 'jQuery scrollTo', 'learnpress' ),
	'jquery-scrollbar' => __( 'jQuery scrollbar', 'learnpress' ),
	'jquery-tipsy'     => __( 'jQuery tipsy', 'learnpress' ),
	'jquery-timer'     => __( 'jQuery timer', 'learnpress' ),
	'watch'            => __( 'Watch JS', 'learnpress' ),
	'font-awesome'     => __( 'Font awesome', 'learnpress' ),
);

?>
<input type="checkbox"
       id="learn-press-toggle-settings-js-css" <?php checked( LP()->settings()->get( 'hide_admin_js_css' ), 'yes' ); ?>>
<table width="100%">
    <tbody>
    <tr>
        <td valign="top">
            <h4><?php esc_html_e( 'Frontend', 'learnpress' ); ?></h4>
            <ul>
				<?php foreach ( $frontend_libraries as $k => $v ) { ?>
                    <li>
                        <label>
                            <input type="checkbox"
                                   name="frontend_libraries[<?php echo $k; ?>]" <?php checked( in_array( $k, $exclude_frontend_libraries ) ); ?>>
							<?php echo $v; ?>
                        </label>
                    </li>
				<?php } ?>
            </ul>
        </td>
        <td valign="top">
            <h4><?php esc_html_e( 'Admin', 'learnpress' ); ?></h4>
            <ul>
				<?php foreach ( $admin_libraries as $k => $v ) { ?>
                    <li>
                        <label>
                            <input type="checkbox"
                                   name="admin_libraries[<?php echo $k; ?>]" <?php checked( in_array( $k, $exclude_admin_libraries ) ); ?>>
							<?php echo $v; ?>
                        </label>
                    </li>
				<?php } ?>
            </ul>
        </td>
    </tr>
    </tbody>
</table>

<a href="javascript:void(0)">
    <label for="learn-press-toggle-settings-js-css">
		<?php esc_html_e( 'Show/Hide', 'learnpress' ); ?>
    </label>
</a>
<script>
    jQuery(function ($) {
        $(document).on('change', '#learn-press-toggle-settings-js-css', function (e) {
            e.preventDefault();
            var hide = !$(this).is(':checked');
            $.ajax({
                url: '../wp-json/lp/v1/settings/hide_admin_js_css',
                type: 'post',
                data: {
                    data: hide ? 'no' : 'yes'
                }
            });
        });
    })
</script>
