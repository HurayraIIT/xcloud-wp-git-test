<?php

/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\WooCommerce;

use NotificationX\Core\Analytics;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\WooCommerce\WooInline as WooInlineFree;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Core\Inline;
use NotificationXPro\Types\Conversions;

/**
 * WooCommerce Extension Class
 */
class WooInline extends WooInlineFree {
    protected $hooks              = array();

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        if ( ! class_exists( 'NotificationX\Core\Inline' ) ) {
            return;
        }

        $this->hooks = array(
            'woocommerce_before_add_to_cart_form'    => [
                'priority' => 22,
                'label'    => __( 'Single Product Page', 'notificationx-pro' ),
            ],
            'woocommerce_after_shop_loop_item_title' => [
                'priority' => 22,
                'label'    => __( 'Shop Archive Page - After Product Title', 'notificationx-pro' ),
            ],
            'woocommerce_after_shop_loop_item'       => [
                'priority' => 22,
                'label'    => __( 'Shop Archive Page - After Product Container', 'notificationx-pro' ),
            ],
            'woocommerce_after_cart_item_name'       => [
                'priority' => 22,
                'label'    => __( 'Shop Cart Page', 'notificationx-pro' ),
            ],
        );
        foreach ( $this->hooks as $hook => $args ) {
            add_action( $hook, array( $this, 'before_add_to_cart_form' ), $args['priority'] );
        }
        add_filter( 'nx_inline_hook_options', array( $this, 'inline_hook_options' ), 10 );
        add_filter( "nx_can_entry_{$this->id}", array( $this, 'nx_can_entry' ), 10, 3 );
        add_filter( 'nx_filtered_notice', array( $this, 'nx_filtered_notice' ), 10, 2 );
        add_filter( 'nx_content_fields', array( $this, 'content_fields' ) );
        add_filter( 'nx_content_heading_preview_errors', array( $this, 'preview_errors' ) );

        /*
        add_action( 'woocommerce_after_cart_item_name', function($cart_item){
            print_r( $cart_item );
            echo "<p>dfsdfsdfsdf df sdfds d sfsdf</p>";
        });
        */
        parent::__construct();
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
        $options = [];
        foreach ( $this->hooks as $key => $value ) {
            $options[ $key ] = $value['label'];
        }
        $_options = GlobalFields::get_instance()->normalize_fields( $options, 'source', $this->id, $_options );
        return $_options;
    }

    /**
     * This method is responsible for output inline notification
     */
    public function show_inline_notification( $atts = [], $settings = [] ) {
        return $this->before_add_to_cart_form(null,[
            'product_id'        => !empty($atts['product_id']) ? $atts['product_id'] : '',
            'current_action'    => 'from_inline',
        ], $settings);

    }

    /**
     * This method is responsible for checking woocommerce inline notification for grouth alert
     */
    public function check_woo_inline( $args ) {
       return ( isset( $args['current_action'] ) && $args['current_action'] == 'from_inline' ) ? true : false;
    }

    /**
     * This method is responsible for output the shortcode.
     */
    public function before_add_to_cart_form( $cart_item = null, $args = [], $nx_settings = [] ) {
        // @var $product WC_Product_Simple
        global $product;
        if( !empty( $args['post_type'] ) && 'wp_template' == $args['post_type'] ) {
            $product_id = rand();
        }elseif( !empty( $args['product_id'] ) ) {
            $product_id = $args['product_id'];
        } elseif ( ! empty( $cart_item['product_id'] ) ) {
            $product_id = $cart_item['product_id'];
        } elseif ( ! empty( $product ) ) {
            $product_id = $product->get_id();
        } else {
			$output = '';
			$output .=  '
            <style>
            .notificationx-woo-shortcode-inline-wrapper a{
                color: #6a4bff;
            }
            </style>
            <div class="notificationx-woo-shortcode-inline-wrapper">Product Page only supports Inline Growth Alert Shortcodes. <a href="https://notificationx.com/docs/notificationx-growth-alert-configuration/" target="_blank">Learn More.</a></div>
            ';
			if (!$this->check_woo_inline($args)) {
				echo $output;
			} else {
				return $output;
			}
		}

        $current_action = current_action();
        if( !$this->check_woo_inline( $args ) ) {
            if ( empty( $product_id ) || ! array_key_exists( $current_action, $this->hooks ) ) {
                return;
            }
        }

        do_action( 'nx_ignore_analytics' );
        $result = Inline::get_instance()->get_notifications_data( $this->id, $product_id, $nx_settings );
        $output = '';
        if ( ! empty( $result['shortcode'] ) ) {
            foreach ( $result['shortcode'] as $key => $value ) {
                $_output  = '';
                $entries  = $value['entries'];
                $entries  = array_values( $entries );
                $settings = $value['post'];
                if( !$this->check_woo_inline( $args ) ) {
                    if ( empty( $settings['is_preview'] ) && (empty( $settings['inline_location'] ) || ! in_array( $current_action, $settings['inline_location'] ))) {
                        continue;
                    }
                }

                $template = Inline::get_instance()->get_template( $settings );
                foreach ( $entries as $key => $entry ) {
                    if ( ! $this->is_stock_theme( $settings['themes'] ) && ( empty( $entry['product_id'] ) || $entry['product_id'] != $product_id ) ) {
                        continue;
                    }

                    if (empty($settings['is_preview']) && isset( $entry['stock_count'] ) && $this->is_stock_theme( $settings['themes'] ) ) {
                        $max_stock = ! empty( $settings['max_stock'] ) ? $settings['max_stock'] : 10;
                        $_product = wc_get_product( $product_id );
                        $product_arr = [ 'product_id' => $product_id ];
                        if (
                            $_product &&
                            $_product->get_stock_quantity() &&
                            $_product->get_stock_quantity() <= $max_stock &&
                            Conversions::get_instance()->_excludes_product( $product_arr, $settings ) &&
                            Conversions::get_instance()->_show_purchaseof( $product_arr, $settings )
                        ) {
                            $entry['stock_count'] = $_product->get_stock_quantity();
                        } else {
                            break;
                        }

                    }
                    // Analytics::get_instance()->insert_analytics( $settings['nx_id'], 'views' );
                    $_template = $template;
                    foreach ( $entry as $key => $val ) {
                        if ( ! is_array( $val ) ) {
                            $_template = str_replace( "{{{$key}}}", $val, $_template );
                        }
                    }
                    $_output .= "<div class='{$settings['themes']}' style='margin-bottom: 1rem'>$_template</div>";
                    break;
                }

            // $this->shortcode_nx_ids[] = $atts['id'];
                if(!empty($_output)){
                    // Remove inline notification margin for woocommerce inline notification
                    $rm_inline_margin  = "";
                    if( $this->check_woo_inline($args) ) {
                        $rm_inline_margin = "
                            .notificationx-woo-shortcode-inline-wrapper .{$settings['themes']} {
                                margin-bottom: 0 !important;
                            }
                        ";
                    }
                    $output .= "
                    <style>
                    .notificationx-woo-shortcode-inline-wrapper p{
                        margin-bottom: 0;
                    }
                    {$rm_inline_margin}
                    </style>
                    <div id='notificationx-woo-shortcode-inline-{$product_id}' class='notificationx-woo-shortcode-inline-wrapper'>$_output</div>";

                }
            }
            if( !$this->check_woo_inline( $args ) ) {
                echo $output;
            }else {

                return $output;
            }
        }
    }

    public function is_stock_theme( $theme ) {
        $themes = [ 'woo_inline_stock-theme-one', 'woo_inline_stock-theme-two' ];
        if ( in_array( $theme, $themes, true ) ) {
            return true;
        }
        return false;
    }

    public function nx_can_entry( $result, $entry, $settings ) {
        if ( $this->is_stock_theme( $settings['themes'] ) ) {
            return false;
        }
        return $result;
    }

    public function fallback_data($data, $entry) {
        $data                  = parent::fallback_data($data, $entry);
        $data['left_in_stock'] = __( 'left in stock', 'notificationx-pro' );
        $data['left']          = __( 'left', 'notificationx-pro' );
        $data['order_soon']    = __( '- order soon.', 'notificationx-pro' );
        $data['on_our_site']   = __( 'on our site!', 'notificationx-pro' );
        return $data;
    }

    public function nx_filtered_notice( $result, $params ) {
        if ( ! empty( $params['shortcode'] ) && is_array( $params['shortcode'] ) ) {
            foreach ( $params['shortcode'] as $key => $nx_id ) {
                if ( ! array_key_exists( $nx_id, $result['shortcode'] ) ) {
                    $settings = PostType::get_instance()->get_post( $nx_id );
                    if ( $settings && $this->is_stock_theme( $settings['themes'] ) ) {
                        $result['shortcode'][ $nx_id ]['post']    = $settings;
                        $result['shortcode'][ $nx_id ]['entries'] = [
                            [
                                'stock_count'   => 0,
                                'left_in_stock' => __( 'left in stock', 'notificationx-pro' ),
                                'left'          => __( 'left', 'notificationx-pro' ),
                                'order_soon'    => __( '- order soon.', 'notificationx-pro' ),
                                'on_our_site'   => __( 'on our site!', 'notificationx-pro' ),
                            ],
                        ];
                    }
                }
            }
        }
        return $result;
    }
    public function content_fields( $fields ) {
        $content_fields = &$fields['content']['fields'];

        $content_fields['max_stock'] = array(
            'type'     => 'number',
            'name'     => 'max_stock',
            'label'    => __( 'Low Stock Threshold', 'notificationx-pro' ),
            'priority' => 290,
            'default'  => 10,
            'help'     => __( 'The notice will appear when the product stock reaches this amount. By default the limit is 10.', 'notificationx-pro' ),
            'rules'    => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::includes( 'themes', [ 'woo_inline_stock-theme-one', 'woo_inline_stock-theme-two' ] ),
            ]),
        );

        return $fields;
    }

    public function preview_entry($entry, $settings){
        $entry = array_merge($entry, [
            "stock_count" => rand(2, 5),
            'image_data'  => array(
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
        if(class_exists('WC_Product_Query')){
            $query = new \WC_Product_Query( array(
                'limit'   => 1,
                'orderby' => 'date',
                'order'   => 'DESC',
                'return'  => 'ids',
            ) );
            $products   = $query->get_products();
            $product_id = end($products);
            if(!empty($product_id)){
                $urls[$this->id] = get_permalink($product_id);
            }
        }
        if(empty($urls[$this->id]) && function_exists('wc_get_page_id')){
            $urls[$this->id] = get_permalink( \wc_get_page_id( 'shop' ) );
        }
        return $urls;
    }
}
