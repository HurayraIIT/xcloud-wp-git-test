<?php
namespace EssentialBlocks\Pro\blocks;

use WP_Query;
use EssentialBlocks\Core\Block;
use EssentialBlocks\API\Product;
use EssentialBlocks\Pro\Utils\Helper;

class WooProductCarousel extends Block {
    protected $is_pro           = true;
    protected $editor_scripts   = 'essential-blocks-pro-editor-script';
    protected $editor_styles    = 'essential-blocks-pro-editor-style';
    protected $frontend_styles  = ['essential-blocks-pro-frontend-style', 'essential-blocks-slick-style', 'essential-blocks-fontawesome', 'essential-blocks-common-style'];
    protected $frontend_scripts = ['essential-blocks-pro-woo-product-carousel-frontend', 'essential-blocks-slickjs'];

    /**
     * Unique name of the block.
     * @return string
     */
    public function get_name() {
        return 'pro-woo-product-carousel';
    }

    /**
     * Register all other scripts
     * @return void
     */
    public function register_scripts() {

        wpdev_essential_blocks_pro()->assets->register(
            'woo-product-carousel-frontend',
            $this->path() . '/frontend/index.js',
            ['essential-blocks-pro-vendor-bundle']
        );
    }

    public function get_array_column( $data, $handle ) {
        $_no_error = true;
        if ( ! is_array( $data ) ) {
            $data      = json_decode( $data, true );
            $_no_error = json_last_error() === JSON_ERROR_NONE;
        }

        return $_no_error ? array_column( $data, $handle ) : $data;
    }

    /**
     * Render Callback
     *
     * @param mixed $attributes
     * @param mixed $content
     * @return void|string
     */
    public function render_callback( $attributes, $content ) {
        if ( ! function_exists( '\WC' ) || is_admin() ) {
            return;
        }

        $_essential_attributes = [
            'carouselPreset'   => 'preset-1',
            'saleBadgeAlign'   => 'align-left',
            'saleText'         => 'sale',
            'showRating'       => true,
            'showPrice'        => true,
            'showSaleBadge'    => true,
            'isCustomCartBtn'  => false,
            'simpleCartText'   => esc_html__( 'Buy Now', 'essential-blocks-pro' ),
            'variableCartText' => esc_html__( 'Select Options', 'essential-blocks-pro' ),
            'groupedCartText'  => esc_html__( 'View Products', 'essential-blocks-pro' ),
            'externalCartText' => esc_html__( 'Buy Now', 'essential-blocks-pro' ),
            'defaultCartText'  => esc_html__( 'Read More', 'essential-blocks-pro' ),
            'buttonText'       => esc_html__( "View Product", 'essential-blocks-pro' ),
            'dotPreset'        => 'dot-style-default',
            'adaptiveHeight'   => true,
            'imageSize'        => 'full',
            'sectionTitle'     => __( "Popular Products", "essential-blocks-pro" )
        ];

        $_essential_attrs = [];
        array_walk( $_essential_attributes, function ( $value, $key ) use ( $attributes, &$_essential_attrs ) {
            $_essential_attrs[$key] = isset( $attributes[$key] ) ? $attributes[$key] : $value;
        } );

        $_slider_settings = [
            'centerMode'          => $_essential_attrs['carouselPreset'] == 'preset-2' ? true : false,
            'arrows'              => true,
            'dots'                => true,
            'autoplaySpeed'       => 3000,
            'speed'               => 500,
            'autoplay'            => true,
            'infinite'            => true,
            'pauseOnHover'        => true,
            'slideToShowRange'    => 3,
            'TABslideToShowRange' => 2,
            'MOBslideToShowRange' => 1,
            'carouselPreset'      => "preset-1"
        ];

        $_slider_controls = [];
        array_walk( $_slider_settings, function ( $value, $key ) use ( $attributes, &$_slider_controls ) {
            $_slider_controls[$key] = isset( $attributes[$key] ) ? $attributes[$key] : $value;
        } );

        $args = isset( $attributes['queryData'] ) ? $attributes['queryData'] : [];

        $_normalize = [
            'orderby'  => 'date',
            'order'    => 'desc',
            'category' => [],
            'tag'      => []
        ];

        foreach ( $_normalize as $key => $value ) {
            $args[$key] = ! empty( $args[$key] ) ? implode( ',', $this->get_array_column( $args[$key], 'value' ) ) : $value;
        }

        $args = wp_parse_args( $args, [
            'per_page' => 10,
            'offset'   => 0
        ] );

        $query = new WP_Query( Product::query_builder( $args ) );

        $blockId   = isset( $attributes["blockId"] ) ? $attributes["blockId"] : "";
        $classHook = isset( $attributes["classHook"] ) ? $attributes["classHook"] : "";

        ob_start();

        Helper::views( 'product-carousel', array_merge( $attributes, [
            'blockId'        => $blockId,
            'classHook'      => $classHook,
            'query'          => $query,
            'essentialAttr'  => $_essential_attrs,
            'sliderSettings' => $_slider_controls,
            'queryData'      => $args
        ] ) );

        return ob_get_clean();
    }
}
