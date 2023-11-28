<?php
namespace EssentialBlocks\Pro\Integrations;
use EssentialBlocks\Integrations\ThirdPartyIntegration;

class DataTable extends ThirdPartyIntegration {

    public function __construct() {
        $this->add_ajax( [
            'eb_data_table_post_meta' => [
                'callback' => 'get_post_data_table_meta',
                'public'   => true
            ]
        ] );
    }

    /**
     * regenerate_assets
     */
    public function get_post_data_table_meta() {

        /**
         * Nonce verification
         */
        if ( isset( $_POST['data_table_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['data_table_nonce'] ), 'eb-data-table-nonce' ) ) {
            die( esc_html__( 'Nonce did not match', 'essential-blocks' ) );
        }

        if ( empty( $_POST ) ) {
            $response_data = ['messsage' => __( 'No post meta data found!', 'essential-blocks' )];
            wp_send_json_error( $response_data );
        }

        $post_id  = '';
        $block_id = '';
        if ( isset( $_POST['post_id'] ) ) {
            $post_id   = trim( $_POST['post_id'] );
            $block_id  = trim( $_POST['block_id'] );
            $post_meta = get_post_meta( $post_id, "_eb_data_table", true );
            if ( ! empty( $post_meta ) && $post_meta ) {
                $block_data = json_decode( $post_meta );
                wp_send_json_success( $block_data );
            } else {
                wp_send_json_error( esc_html__( "No data found", "essential-blocks-pro" ) );
            }
        } else {
            wp_send_json_error( esc_html__( "Post ID not found", "essential-blocks-pro" ) );
        }
    }
}
