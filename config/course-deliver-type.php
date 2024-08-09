<?php
/**
 * Profile tabs
 *
 * @since 4.2.7
 * @version 1.0.0
 */

$options = [
	'private_1_1'     => esc_html__( 'Private 1-1', 'learnpress' ),
	'in_person_class' => esc_html__( 'In-person class', 'learnpress' ),
	'live_online'     => esc_html__( 'Live online class', 'learnpress' ),
];

return apply_filters( 'learn-press/course/deliver-type', $options );
