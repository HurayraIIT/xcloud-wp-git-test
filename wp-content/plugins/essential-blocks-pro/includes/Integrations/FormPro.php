<?php
namespace EssentialBlocks\Pro\Integrations;

use EssentialBlocks\Pro\Utils\Helper;
use EssentialBlocks\Pro\Utils\FormBlockHandler;
use EssentialBlocks\Integrations\ThirdPartyIntegration;

class FormPro extends ThirdPartyIntegration {
    /**
     * Base URL for Adv Search
     * @var string
     */

    public function __construct() {
        $this->add_ajax( [
            'get_mailchimp_list' => [
                'callback' => 'mailchimp_list',
                'public'   => false
            ],
            'export_csv'         => [
                'callback' => 'export_csv_callback',
                'public'   => false
            ]
        ] );
    }

    /**
     * Search query
     */
    public function mailchimp_list() {
        if ( ! wp_verify_nonce( $_POST['admin_nonce'], 'eb-pro-admin-nonce' ) ) {
            die( esc_html__( 'Nonce did not match', 'essential-blocks-pro' ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'You are not authorized!', 'essential-blocks' ) );
        }

        $mailchimpApi = Helper::get_mailchimp_api();
        if ( strlen( $mailchimpApi ) === 0 ) {
            wp_send_json_error( 'api' );
        }
        $mailchimpList = FormBlockHandler::get_mailchimp_lists( $mailchimpApi );

        if ( is_array( $mailchimpList ) && count( $mailchimpList ) > 0 ) {
            wp_send_json_success( $mailchimpList );
        } else {
            wp_send_json_error( 'list' );
        }
    }

    /**
     * Search query
     */
    public function export_csv_callback() {
        if ( ! wp_verify_nonce( $_GET['admin_nonce'], 'eb-pro-admin-nonce' ) ) {
            die( esc_html__( 'Nonce did not match', 'essential-blocks-pro' ) );
        }

        if ( ! isset( $_GET['form_id'] ) ) {
            die( esc_html__( 'Invalid Form Id!', 'essential-blocks-pro' ) );
        }

        if ( ! current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error( __( 'You are not authorized!', 'essential-blocks' ) );
        }

        $title   = Helper::get_form_title( $_GET['form_id'] );
        $columns = Helper::get_form_columns( $_GET['form_id'] );
        $data    = Helper::form_response_table_data( $_GET['form_id'] );

        $result = Helper::prepare_csv_data( $data, $columns );

        echo json_encode( (object) [
            'title' => $title,
            'data'  => $result
        ] );
        wp_die();
    }
}
