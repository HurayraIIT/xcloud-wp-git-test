<?php
namespace EssentialBlocks\Pro\Integrations;

use EssentialBlocks\Pro\Utils\Helper;
use EssentialBlocks\Pro\Core\DynamicTags\Acf\AcfData;
use EssentialBlocks\Integrations\ThirdPartyIntegration;
use EssentialBlocks\Pro\Core\DynamicTags\Post\PostFields;
use EssentialBlocks\Pro\Core\DynamicTags\Site\SiteFields;
use EssentialBlocks\Pro\Core\DynamicTags\HandleTagsResult;

class DynamicFields extends ThirdPartyIntegration {

    public function __construct() {
        $this->add_ajax( [
            'all_fields_by_group' => [
                'callback' => 'all_fields_by_group',
                'public'   => true
            ],
            'acf_fields_by_group' => [
                'callback' => 'acf_fields_by_group',
                'public'   => true
            ],
            'dynamic_field_value' => [
                'callback' => 'get_dynamic_field_value',
                'public'   => true
            ],
            'post_by_id'          => [
                'callback' => 'get_post_by_id',
                'public'   => true
            ]
        ] );
    }

    public function all_fields_by_group( $group_name = 'all' ) {
        $fields  = [];
        $source  = isset( $_POST['source'] ) ? $_POST['source'] : 'current';
        $post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;

        $post_type = get_post_type( $post_id );

        if ( $source === 'site' ) {
            //Site
            $site_fields = SiteFields::get_fields();
            if ( is_array( $site_fields ) ) {
                array_push( $fields, [
                    "label"   => "Site",
                    "options" => Helper::modify_array_key( $site_fields, 'site' )
                ] );
            }
        } else {
            //Post
            $post_fields = PostFields::get_fields();
            if ( is_array( $post_fields ) ) {
                array_push( $fields, [
                    "label"   => "Post",
                    "options" => Helper::modify_array_key( $post_fields, 'post' )
                ] );
            }

            //ACF
            $acf_fileds = AcfData::acf_get_fields_by_groups( $post_type );
            if ( is_array( $acf_fileds ) ) {
                $fields = array_merge( $fields, $acf_fileds );
            }
        }

        wp_send_json_success( $fields );
        wp_die();
    }

    /**
     * Function: ACF fields by group
     *
     * @return
     * @since 1.0.0
     */
    public function acf_fields_by_group() {
        if ( ! wp_verify_nonce( $_POST['admin_nonce'], 'eb-pro-admin-nonce' ) ) {
            die( esc_html__( 'Nonce did not match', 'essential-blocks-pro' ) );
        }

        $post_type = 'post';
        if ( isset( $_POST['post_type'] ) && is_string( $_POST['post_type'] ) ) {
            $post_type = $_POST['post_type'];
        }

        $acfFiledsbyGroup = AcfData::acf_get_fields_by_groups( $post_type );

        wp_send_json_success( $acfFiledsbyGroup );
        wp_die();
    }

    /**
     * Ajax callback for get dynamic value from dynamic key
     */
    public function get_dynamic_field_value() {
        if ( isset( $_POST['value'] ) && isset( $_POST['post_id'] ) ) {
            $value = HandleTagsResult::get_value_form_dynamic_tag( $_POST['value'], $_POST['post_id'] );
            wp_send_json_success( $value );
        } else {
            wp_send_json_error();
        }
        wp_die();
    }

    public function get_post_by_id() {
        if ( ! wp_verify_nonce( $_POST['admin_nonce'], 'eb-pro-admin-nonce' ) ) {
            die( esc_html__( 'Nonce did not match', 'essential-blocks-pro' ) );
        }
        if ( isset( $_POST['post_id'] ) ) {
            $post_id = $_POST["post_id"];
            $post    = get_post( $post_id );
            $object  = (object) [
                'name'  => $post->post_title,
                'value' => $post_id
            ];
            wp_send_json_success( $object );
        } else {
            wp_send_json_error();
        }
        wp_die();
    }
}
