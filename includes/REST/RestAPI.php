<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_SubmissionsRestEndpoint_REST_RestAPI
 */
final class NF_SubmissionsRestEndpoint_REST_RestAPI{
    public function __construct(){
        add_action('rest_api_init', array( $this, 'register_endpoint' ) );
    }


    public function register_endpoint(){
        
        register_rest_route( 'nf-submissions/v1', 'form/(?P<form_id>\d+)',array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_submissions_callback' )
        ));

        register_rest_route( 'nf-submissions/v1', 'form/(?P<form_id>\d+)/fields',array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_field_meta_callback' )
        ));
    }




    private function check_api_key( $request ){
        $request_key = $request->get_header('NF-REST-Key');
        if( !$request_key ){
            $response = new WP_REST_Response( array( 'error' => 'Please provide your access key as NF-REST-Key header') );
            $response->set_status(403);
            return $response;
        }
        if( $request_key != Ninja_Forms()->get_setting('nf_rest_key') ){
            $response = new WP_REST_Response( array( 'error' => 'Wrong access key, please check your NF-REST-Key header') );
            $response->set_status(403);
            return $response;
        }

        return;
    }




    private function get_submissions($form_id,$args){
        $query_args = array(
            'post_type'         => 'nf_sub',
            'posts_per_page'    => -1,
            'date_query'        => array(
                'inclusive'     => true,
            ),
            'meta_query'        => array(
                array(
                    'key' => '_form_id',
                    'value' => $form_id,
                )
            )
        );

        foreach ($args as $key => $value) {
            if( $key == 'date_from' && $value ){
                if( strpos( $value, ':' ) === false )
                    $value .= ' 00:00:00';
                $query_args['date_query']['after'] = $value;
            }
            if( $key == 'date_to' && $value ){
                if( strpos( $value, ':' ) === false )
                    $value .= ' 23:59:59';
                $query_args['date_query']['before'] = $value;
            }
        }

        $submission_query = new WP_Query( $query_args );
        
        $sub_objects = array();

        if ( is_array( $submission_query->posts ) && ! empty( $submission_query->posts ) ) {
            foreach ( $submission_query->posts as $sub_index => $sub ) {
                $sub_objects[$sub_index] = Ninja_Forms()->form( $form_id )->get_sub( $sub->ID )->get_field_values();

                // remove duplicate entries
                // "_field_1036": "two",
                // "listradio_1513101309427": "two",
                // "_field_1037": "one",
                // "listselect_1513101310057": "one",
                // we keep the "_field_XXX" values instead of the "listselect_1513101310057" ones 
                // because the field keys sometimes do not match those retrieved from the fields_meta endpoint if
                // the form is updated afterwards

                foreach( $sub_objects[$sub_index] as $field_key => $field_value){
                    if( (
                        strpos( $field_key, '_field_' ) !== 0 
                        && strpos( $field_key, '_seq_num' ) !== 0 )
                        || $field_key == '_field_'
                    )
                        unset( $sub_objects[$sub_index][$field_key] );
                    
                }

                $sub_objects[$sub_index]['user_id'] = $sub->post_author;
                $sub_objects[$sub_index]['submission_date'] = $sub->post_date;
            }           
        }

        return $sub_objects;
    }



    /**
     * the REST callback function. 
     * Checks permissions and parameters and returns the rest response
     */
    public function get_submissions_callback( $request ){
        $key_result = $this->check_api_key( $request );
        if( $key_result instanceof WP_REST_Response )
            return $key_result;

        $args = array(
            'date_from' => ( isset( $_GET['date_from'] ) ? $_GET['date_from'] : false ),
            'date_to'   => ( isset( $_GET['date_to'] ) ? $_GET['date_to'] : false )
        );


        $form = false;
        if( isset( $request['form_id'] ) ){
            $form_id = $request['form_id'];
            if( is_numeric( $form_id ) ){
                $form = Ninja_Forms()->form( intval($form_id) );
            }
        }

        if( !$form ){
            $response = new WP_REST_Response( array( 'error' => 'Form not found') );
            $response->set_status(404);
            return $response;
        }

        $submissions = $this->get_submissions($form_id,$args);
        $response = new WP_REST_Response( array( 'submissions' => $submissions ) );
        $response->set_status(200);

        return $response;
    }



    /**
     * get field names REST callback
     */
    public function get_field_meta_callback( $request ){
        $key_result = $this->check_api_key( $request );
        if( $key_result instanceof WP_REST_Response )
            return $key_result;

        $form = false;
        if( isset( $request['form_id'] ) ){
            $form_id = $request['form_id'];
            if( is_numeric( $form_id ) ){
                $form = Ninja_Forms()->form( intval($form_id) );
            }
        }

        if( !$form ){
            $response = new WP_REST_Response( array( 'error' => 'Form not found') );
            $response->set_status(404);
            return $response;
        }

        
        $args = array(
            'use_admin_label'   =>  true,   // either use admin labels (if present) or the user labels
        );

        if( in_array( strtolower( $request['use_admin_label'] ), array( '0', 'false' ) ) )
            $args['use_admin_label'] = false;
        
        $response_meta = array();
        $response_meta['submission_date'] = array( 
                    'key'  =>   'submission_date',
                    'label' =>  'Submission Date',      
            );
        $response_meta['_seq_num'] = array( 
                    'key'  =>   '_seq_num',
                    'label' =>  'Submission ID',      
            );
            

        $fields_meta = $form->get_fields();
        foreach($fields_meta as $field_id => $field){
            $field_settings = $field->get_settings();
            
            //error_log( print_r( $field_settings,true ) );
            $response_meta[$field_settings['key']] = array( 
                    'id'    =>  $field_id,
                    'type'  =>  $field_settings['type'],
                    'key'  =>   $field_settings['key'],
                    'label' =>  ( isset($field_settings['admin_label']) && $field_settings['admin_label'] && $args['use_admin_label'] ? $field_settings['admin_label'] : $field_settings['label']),      
            );

            $list_fields_types = array( 'listcheckbox', 'listmultiselect', 'listradio', 'listselect' );
            if( in_array( $field_settings[ 'type' ], $list_fields_types ) ) {
                $response_meta[$field_settings['key']]['options'] = $this->get_list_options( $field_settings );
            }
        }

        $response = new WP_REST_Response( $response_meta );
        $response->set_status(200);

        return $response;
    }



    public function get_list_options( $field ){
        // Build our array to store our labels.
        $labels = array();
        // Loop over our options...
        foreach( $field[ 'options' ] as $options ) {
            $labels[ $options[ 'value' ] ] = $options[ 'label' ];
        }
        return $labels;
    }
}
