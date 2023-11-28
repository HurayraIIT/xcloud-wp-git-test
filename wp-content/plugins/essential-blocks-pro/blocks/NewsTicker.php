<?php
namespace EssentialBlocks\Pro\blocks;

use EssentialBlocks\Pro\Utils\Helper;
use EssentialBlocks\blocks\PostBlock;

class NewsTicker extends PostBlock {
    protected $is_pro           = true;
    protected $editor_scripts   = 'essential-blocks-pro-editor-script';
    protected $editor_styles    = 'essential-blocks-pro-editor-style';
    protected $frontend_scripts = [
        'essential-blocks-pro-news-ticker-frontend',
        'essential-blocks-slickjs'
    ];
    protected $frontend_styles = [
        'essential-blocks-pro-frontend-style',
        'essential-blocks-slick-style',
        'essential-blocks-fontawesome'
    ];

    protected static $default_attributes = [
        'arrows'             => true,
        'dots'               => false,
        'autoplaySpeed'      => 0,
        'speed'              => 3500,
        'adaptiveHeight'     => true,
        'autoplay'           => true,
        'infinite'           => true,
        'pauseOnHover'       => false,
        'slideToShowRange'   => 2,
        'titleLength'        => '10',
        'contentSource'      => 'custom-content',
        'news'               => [],
        'newsLabel'          => 'Trending Today',
        'showLabelIcon'      => true,
        'animationType'      => 'animation-scroll',
        'arrowPrevIcon'      => 'fas fa-angle-left',
        'arrowNextIcon'      => 'fas fa-angle-right',
        'animationDirection' => 'false',
        'showReadMore'       => false,
        'labelIcon'          => 'fab fa-gripfire'

    ];

    public function get_default_attributes() {
        return array_merge( parent::$default_attributes, self::$default_attributes );
    }

    /**
     * Unique name of the block.
     * @return string
     */
    public function get_name() {
        return 'news-ticker';
    }

    /**
     * Register all other scripts
     * @return void
     */
    public function register_scripts() {
        $this->assets_manager->register( 'slickjs', 'js/slick.min.js' );
        wpdev_essential_blocks_pro()->assets->register(
            'news-ticker-frontend',
            $this->path() . '/frontend/index.js',
            ['jquery', 'essential-blocks-slickjs']
        );
    }

    /**
     * Block render callback.
     *
     * @param mixed $attributes
     * @param mixed $content
     * @return mixed
     */
    public function render_callback( $attributes, $content ) {
        if ( is_admin() ) {
            return;
        }

        $queryData = $attributes["queryData"];

        //Query Result
        $result = $this->get_posts( $queryData );
        $query  = [];
        if ( isset( $result->posts ) && is_array( $result->posts ) && count( $result->posts ) > 0 ) {
            $query = apply_filters( 'eb_news_ticker_query_results', $result->posts );
        }

        $attributes = wp_parse_args( $attributes, $this->get_default_attributes() );

        $className = isset( $attributes["className"] ) ? $attributes["className"] : "";
        $classHook = isset( $attributes['classHook'] ) ? $attributes['classHook'] : '';

        $_default_attributes = array_keys( parent::$default_attributes );
        $_essential_attrs    = [];
        array_walk( $_default_attributes, function ( $key ) use ( $attributes, &$_essential_attrs ) {
            $_essential_attrs[$key] = $attributes[$key];
        } );

        $_slider_attributes = self::$default_attributes;

        unset( $_slider_attributes['dotPreset'] );
        unset( $_slider_attributes['titleLength'] );

        $_slider_attributes['TABslideToShowRange'] = 2;
        $_slider_attributes['MOBslideToShowRange'] = 1;

        $_slider_settings = [];
        array_walk( $_slider_attributes, function ( $value, $key ) use ( $attributes, &$_slider_settings ) {
            $_slider_settings[$key] = isset( $attributes[$key] ) ? $attributes[$key] : $value;
        } );

        ob_start();
        Helper::views( 'news-ticker', array_merge( $attributes, [
            'essentialAttr'  => $_essential_attrs,
            'sliderSettings' => $_slider_settings,
            'className'      => $className,
            'classHook'      => $classHook,
            'posts'          => $query,
            'block_object'   => $this
        ] ) );

        return ob_get_clean();
    }
}
