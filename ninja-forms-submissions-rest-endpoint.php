<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: Ninja Forms - Submissions Rest Endpoint
 * Plugin URI: https://codemiq.com
 * Description: Get NinjaForms submissions via Rest API
 * Version: 3.0.1
 * Author: codemiq
 * Author URI: https://codemiq.com
 * Text Domain: ninja-forms-submissions-rest-endpoint
 *
 * Copyright 2019 codemiq
 */

if( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3', '>' ) ) {

    /**
     * Class NF_SubmissionsRestEndpoint
     */
    final class NF_SubmissionsRestEndpoint
    {
        const VERSION = '3.0.0';
        const SLUG    = 'submissions-rest-endpoint';
        const NAME    = 'Submissions Rest Endpoint';
        const AUTHOR  = 'codemiq';
        const PREFIX  = 'NF_SubmissionsRestEndpoint';

        /**
         * @var NF_SubmissionsRestEndpoint
         * @since 3.0
         */
        private static $instance;

        /**
         * Plugin Directory
         *
         * @since 3.0
         * @var string $dir
         */
        public static $dir = '';

        /**
         * Plugin URL
         *
         * @since 3.0
         * @var string $url
         */
        public static $url = '';

        /**
         * Rest API
         *
         * @since 3.0
         * @var string $restapi
         */
        public $restapi;

        /**
         * Main Plugin Instance
         *
         * Insures that only one instance of a plugin class exists in memory at any one
         * time. Also prevents needing to define globals all over the place.
         *
         * @since 3.0
         * @static
         * @static var array $instance
         * @return NF_SubmissionsRestEndpoint Highlander Instance
         */
        public static function instance()
        {
            if (!isset(self::$instance) && !(self::$instance instanceof NF_SubmissionsRestEndpoint)) {
                self::$instance = new NF_SubmissionsRestEndpoint();

                self::$dir = plugin_dir_path(__FILE__);

                self::$url = plugin_dir_url(__FILE__);

                /*
                 * Register our autoloader
                 */
                spl_autoload_register(array(self::$instance, 'autoloader'));
            }
            
            return self::$instance;
        }

        public function __construct()
        {
            /*
             * Required for all Extensions.
             */
            add_action( 'admin_init', array( $this, 'setup_license') );
            add_filter( 'ninja_forms_plugin_settings', array($this, 'register_settings'));
            add_filter( 'ninja_forms_plugin_settings_groups', array($this, 'register_settings_group'));
            add_action( 'admin_init', array( $this, 'generate_key') );
            add_action( 'init', function(){
                $this->restapi = new NF_SubmissionsRestEndpoint_REST_RestAPI();
            });
        }


        public function generate_key(){
            $key = Ninja_Forms()->get_setting('nf_rest_key');

            if( !$key || isset( $_GET["generate-rest-api-key"] ) ) {  
                $key = hash( 'sha256', openssl_random_pseudo_bytes(20), false );
                Ninja_Forms()->update_setting( 'nf_rest_key', $key );
                if( isset( $_GET["generate-rest-api-key"] ) )
                    wp_redirect( remove_query_arg('generate-rest-api-key') );
            }
        }



        /**
		 * Register a Group on the settings page
		 */
		public function register_settings_group($groups)
		{
            $groups[ 'rest_endpoint' ] = array(
                'id' => 'rest_endpoint',
                'label' => __( 'Submissions REST API', 'ninja-forms-submissions-rest-endpoint' ),
            );				

			return $groups;
		}

		/**
		 * Register Settings fields
		 */
        public function register_settings($settings)
        {

			$settings[ 'rest_endpoint' ] = NF_SubmissionsRestEndpoint()->config( 'PluginSettings' );

			return $settings;
		}

        /*
         * Optional methods for convenience.
         */

        public function autoloader($class_name)
        {

            if (class_exists($class_name)) return;

            if ( false === strpos( $class_name, self::PREFIX ) ) return;

            $class_name = str_replace( self::PREFIX, '', $class_name );

            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

            if (file_exists($classes_dir . $class_file)) {
                require_once $classes_dir . $class_file;
            }
        }

        
        /**
         * Config
         *
         * @param $file_name
         * @return mixed
         */
        public static function config( $file_name )
        {
            return include self::$dir . 'includes/Config/' . $file_name . '.php';
        }

        /*
         * Required methods for all extension.
         */

        public function setup_license()
        {
            if ( ! class_exists( 'NF_Extension_Updater' ) ) return;

            new NF_Extension_Updater( self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG );
        }
    }

    /**
     * The main function responsible for returning The Highlander Plugin
     * Instance to functions everywhere.
     *
     * Use this function like you would a global variable, except without needing
     * to declare the global.
     *
     * @since 3.0
     * @return {class} Highlander Instance
     */
    function NF_SubmissionsRestEndpoint()
    {
        return NF_SubmissionsRestEndpoint::instance();
    }

    NF_SubmissionsRestEndpoint();
}
