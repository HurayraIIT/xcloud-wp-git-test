<?php
namespace EssentialBlocks\Pro\Core\DynamicTags\Site;

use EssentialBlocks\Traits\HasSingletone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SiteFields {
    use HasSingletone;

    public function __construct() {
        add_filter( "essential-blocks-pro/site/fields", [$this, 'get_fields'], 1 );
    }

    public static function get_fields( $fields = [] ) {
        return array_merge(
            $fields,
            [
                'site-tagline' => __( 'Site Tagline', ESSENTIAL_BLOCKS_PRO_NAME ),
                'site-title'   => __( 'Site Title', ESSENTIAL_BLOCKS_PRO_NAME ),
                'site-url'     => __( 'Site URL', ESSENTIAL_BLOCKS_PRO_NAME )
            ]
        );
    }

    public static function get_values( $field ) {

        switch ( $field ) {
            case 'site-tagline':
                return self::get_site_tagline();
            case 'site-title':
                return self::get_site_title();
            case 'site-url':
                return self::get_site_url();
            default:
                return __( 'The field doesn\'t exists.', ESSENTIAL_BLOCKS_PRO_NAME );
        }
    }

    /**
     * Function for getting site tagline
     *
     * @return string site tagline
     */
    public static function get_site_tagline() {
        return wp_kses_post( get_bloginfo( 'description' ) );
    }

    /**
     * Function for getting site-title.
     *
     * @return string site title
     */
    public static function get_site_title() {
        return wp_kses_post( get_bloginfo( 'title' ) );
    }

    /**
     * Function for getting the site URL.
     *
     * @return string site url
     */
    public static function get_site_url() {
        return get_bloginfo( 'url' );
    }
}
