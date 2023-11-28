<?php

namespace WPDeveloper\BetterDocs\REST;

use WPDeveloper\BetterDocs\Admin\ReportEmail;
use WP_REST_Request;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class Settings extends BaseAPI {

    public function permission_check() {
        return current_user_can( 'edit_docs_settings' );
    }

    public function register() {
        $this->get( 'settings', [$this, 'get_settings'] );
        $this->post( 'settings', [$this, 'save_settings'] );

        $this->post( 'reporting-test', [$this, 'test_reporting'] );
    }

    public function get_settings() {
        return betterdocs()->settings->get_all( true );
    }

    public function save_settings( WP_REST_Request $request ) {
        if ( betterdocs()->settings->save_settings( $request->get_params() ) ) {
            return $this->success( __( 'Settings Saved!', 'betterdocs' ) );
        }

        return $this->error( 'nothing_changed', __( 'There are no changes to be saved.', 'betterdocs' ), 200 );
    }

    public function test_reporting( $request ){
        return $this->container->get(ReportEmail::class)->test_email_report( $request );
    }
}
