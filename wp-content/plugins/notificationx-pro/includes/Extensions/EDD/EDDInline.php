<?php
/**
 * EDD Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\EDD;

use NotificationX\Extensions\EDD\EDDInline as EDDInlineFree;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Types\Conversions;
use NotificationX\Core\Inline;

/**
 * EDD Extension
 * @todo normalize data for frontend.
 * @todo show_purchaseof && excludes_product
 */
class EDDInline extends EDDInlineFree {

    use _EDD;

    /**
     * __construct__ is for revoke first time to get ready
     *
     * @return void
     */
    public function __construct() {
        if ( ! class_exists( 'NotificationX\Core\Inline' ) ) {
            return;
        }

        parent::__construct();
        add_action( 'edd_purchase_link_top', array( $this, 'edd_purchase_link_top' ), 10, 2 );
        add_filter( 'nx_inline_hook_options', array( $this, 'inline_hook_options' ), 10 );
        add_filter( 'nx_content_heading_preview_errors', array( $this, 'preview_errors' ) );
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_conversion_product_list', [$this, 'products']);
        add_filter('nx_conversion_category_list', [$this, 'categories']);

    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        add_filter( "nx_can_entry_{$this->id}", array( Conversions::get_instance(), 'nx_can_entry' ), 10, 3 );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function public_actions() {
        parent::public_actions();
        // @todo deprecated remove in the future.
        add_filter( "nx_filtered_data_{$this->id}", array( Conversions::get_instance(), 'show_exclude_product' ), 11, 2 );
        add_filter( 'nx_preview_url', array( $this, 'preview_url' ) );
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function inline_hook_options( $_options ) {
        $options = [
            'edd_archive' => __( 'Archive Page', 'notificationx-pro' ),
            'edd_single'  => __( 'Single Page', 'notificationx-pro' ),
        ];
        $_options = GlobalFields::get_instance()->normalize_fields( $options, 'source', $this->id, $_options );
        return $_options;
    }

    /**
     * This method is responsible for output inline notification
     */
    public function show_inline_notification( $args = [] ) {
        return $this->edd_purchase_link_top( $args['product_id'],[
            'current_action' => 'from_inline',
        ]);
    }

    /**
     * This method is responsible for checking woocommerce inline notification for grouth alert
     */
    public function check_edd_inline( $args = [] ) {
        return ( isset( $args['current_action'] ) && $args['current_action'] == 'from_inline' ) ? true : false;
    }
 

    /**
     * This method is responsible for output the shortcode.
     */
    public function edd_purchase_link_top( $download_id, $args ) {
        if( isset($args['current_action']) && $args['current_action'] == 'from_inline' && empty( $download_id ) && get_post_type( get_the_ID() ) == 'download' ) {
            $download_id = get_the_ID();
        }
        $result         = Inline::get_instance()->get_notifications_data( $this->id, $download_id );
        $current_action = is_archive() ? 'edd_archive' : 'edd_single';
        $output         = '';
        if ( ! empty( $result['shortcode'] ) ) {
            foreach ( $result['shortcode'] as $key => $value ) {
                $entries  = $value['entries'];
                $entries  = array_values( $entries );
                $settings = $value['post'];
                
                if( !$this->check_edd_inline( $args ) ) {
                    if ( 
                        empty($settings['is_preview']) &&
                        (empty($settings['inline_location']) ||
                            !in_array($current_action, $settings['inline_location']))
                     ) {
                        continue;
                    }
                }

                $template = Inline::get_instance()->get_template( $settings );
                foreach ( $entries as $key => $entry ) {
                    if ( empty( $entry['product_id'] ) || $entry['product_id'] != $download_id ) {
                        continue;
                    }
                    $_template = $template;
                    foreach ( $entry as $key => $val ) {
                        if ( ! is_array( $val ) ) {
                            $_template = str_replace( "{{{$key}}}", $val, $_template );
                        }
                    }
                    $output .= "<div class='{$settings['themes']}' style='margin-bottom: 1rem'>$_template</div>";
                    break;
                }
            }
            
            // $this->shortcode_nx_ids[] = $atts['id'];
            if(!empty($output)){
                // Remove inline notification margin for woocommerce inline notification
                $rm_inline_margin  = "";
                if( $this->check_edd_inline($args) ) {
                    $rm_inline_margin = "
                        .notificationx-woo-shortcode-inline-wrapper .{$settings['themes']} {
                            margin-bottom: 0 !important;
                        }
                    ";
                }
                $output = "
                <style>
                .notificationx-woo-shortcode-inline-wrapper p{
                    margin-bottom: 0;
                }
                {$rm_inline_margin}
                </style>
                <div id='notificationx-woo-shortcode-inline-{$download_id}' class='notificationx-woo-shortcode-inline-wrapper'>$output</div>";
                
                if( !$this->check_edd_inline( $args ) ) {
                    echo $output;
                }else {
                    return $output;
                }
            }
        }
    }

    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            'image_data'    => array(
                'url'     => NOTIFICATIONX_PUBLIC_URL . 'image/icons/pink-face-looped.gif',
                'alt'     => '',
                'classes' => 'greview_icon',
            ),
        ]);
        return $entry;
    }

    public function preview_errors($errors){
        $errors[$this->id] = __("Create a product to get the preview.", 'notificationx-pro');
        return $errors;
    }

    public function preview_url($urls){
        $args = array(
            'fields'    => 'ids',
            'post_type' => 'download',
            'limit'     => 1,
            'orderby'   => 'date',
            'order'     => 'DESC',
        );

        $downloads  = get_posts( $args );
        $product_id = end($downloads);
        if(!empty($product_id)){
            $urls[$this->id] = get_permalink($product_id);
        }
        return $urls;
    }
}
