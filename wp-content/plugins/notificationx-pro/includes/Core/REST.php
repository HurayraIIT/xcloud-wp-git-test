<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Core;

use NotificationXPro\Core\Licensing\Licensing;
use NotificationX\Core\REST as RESTFree;
use NotificationX\Types\ContactForm;
use NotificationX\Admin\Settings;
use NotificationX\CoreInstaller;
use NotificationX\Extensions\PressBar\PressBar;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;
use NotificationX\NotificationX;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST Class
 */
class REST extends RESTFree {

    public function __construct(){
        parent::__construct();
    }

    public function register_routes() {
        parent::register_routes();
        $namespace = self::_namespace();
        register_rest_route( $namespace, '/license', [
            array(
                'methods'   => WP_REST_Server::READABLE,
                'callback'  => array( $this, 'get_license' ),
                'permission_callback' => array($this, 'settings_permission'),
            ),
            array(
                'methods'   => WP_REST_Server::EDITABLE,
                'callback'  => array( $this, 'license' ),
                'permission_callback' => array($this, 'settings_permission'),
            )
        ]);
    }

    public function get_license( $request ){
        $license = NotificationX::get_instance()->licensing();
        return array_merge( (array) $license->get_license_data(), [
            'license_key' => $license->get_license_key(),
            'hidden_license_key' => $license->get_hidden_license_key()
        ]);
    }

    public function license( $request ){
        $license = NotificationX::get_instance()->licensing();
        if( $request->has_param('type') && $request->get_param('type') === 'activate' ) {
            return $license->activate_license( $request );
        }
        if( $request->has_param('type') && $request->get_param('type') === 'deactivate' ) {
            return $license->deactivate_license( $request );
        }
    }

}