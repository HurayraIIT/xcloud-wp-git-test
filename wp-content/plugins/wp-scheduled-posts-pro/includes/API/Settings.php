<?php

namespace WPSP_PRO\API;

use WPSP_PRO;

class Settings
{
    /**
     * Main Setting Option Name
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $settings_name = null;

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize hooks and option name
     */
    private function __construct()
    {
        $this->settings_name = WPSP_SETTINGS_NAME;
        $this->do_hooks();
    }

    /**
     * Set up WordPress hooks and filters
     *
     * @return void
     */
    public function do_hooks()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Return an instance of this class.
     *
     * @since     0.8.1
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';
        $endpoint = apply_filters('wpsp_rest_endpoint', '/settings/');

        register_rest_route($namespace, 'activate_license', array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array($this, 'activate_license'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route($namespace, 'get_license', array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array($this, 'get_license'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route($namespace, 'deactivate_license', array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array($this, 'deactivate_license'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

    }

    /**
     * License Activation 
     * 
     * @param $data
    */
    public function activate_license($data)
    {
        $licensing = new WPSP_PRO\Admin\WPDev\Licensing(
            WPSP_PRO_SL_ITEM_SLUG,
            'SchedulePress',
            'wp-scheduled-posts-pro'
        );
        $licensing->activate_license( $data->get_params() );
    }

    /**
     * License Activation 
     * 
     * @param $data
    */
    public function deactivate_license($data)
    {
        $licensing = new WPSP_PRO\Admin\WPDev\Licensing(
            WPSP_PRO_SL_ITEM_SLUG,
            'SchedulePress',
            'wp-scheduled-posts-pro'
        );
        $licensing->deactivate_license( $data->get_params() );
    }

    /**
     * Get License 
     * 
     * @param $data
    */
    public function get_license($data)
    {
        $licensing = new WPSP_PRO\Admin\WPDev\Licensing(
            WPSP_PRO_SL_ITEM_SLUG,
            'SchedulePress',
            'wp-scheduled-posts-pro'
        );
        $licensing->get_license( $data->get_params() );
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function wpsp_permissions_check($request)
    {
        return current_user_can('manage_options');
    }
}
