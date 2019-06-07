<?php if ( ! defined( 'ABSPATH' ) ) exit;


return apply_filters( 'ninja_forms_plugin_settings_rest_endpoint', array(
    'nf_rest_key' => array(
        'id'    => 'nf_rest_key',
        'type'  => 'textbox',
        'label' => __( 'REST API Access key', 'ninja-forms-submissions-rest-endpoint' ),
        'desc'  => __('Send this key as "NF-REST-Key" header with your API request.','ninja-forms-submissions-rest-endpoint'),
    ),
    'nf_rest_regenerate' => array(
        'id'    => 'nf_rest_regenerate',
        'type'  => 'html',
        'html' => '<a href="' . add_query_arg('generate-rest-api-key',1) . '" class="button">' . __( 'Regenerate API key', 'ninja-forms-submissions-rest-endpoint' ) . '</a>',
        'label' => '',
        'desc'  => __( 'ATTENTION: This button disconnects all your existing connections.', 'ninja-forms-submissions-rest-endpoint' ),
    ),
));
