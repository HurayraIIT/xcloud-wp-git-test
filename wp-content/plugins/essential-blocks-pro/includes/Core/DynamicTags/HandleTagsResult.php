<?php
namespace EssentialBlocks\Pro\Core\DynamicTags;

use EssentialBlocks\Traits\HasSingletone;
use EssentialBlocks\Pro\Core\DynamicTags\Acf\AcfData;
use EssentialBlocks\Pro\Core\DynamicTags\Post\PostFields;
use EssentialBlocks\Pro\Core\DynamicTags\Site\SiteFields;

class HandleTagsResult {
    use HasSingletone;

    public function __construct() {
        add_filter( 'render_block', [$this, 'handle_dynamic_tag_result'], 10, 2 );
        add_filter( 'eb_dynamic_tag_value', [$this, 'eb_dynamic_tag_value_result'], 10, 3 );
    }

    public function eb_dynamic_tag_value_result( $attrValue, $final_content, $return_value = false ) {

        $pattern = "/\b" . ESSENTIAL_BLOCKS_DYNAMIC_TAGS . "\/[\w\/\-\_]+\b/";
        // Find matches
        preg_match_all( $pattern, $attrValue, $matches );
        // Display the matches
        if ( ! empty( $matches[0] ) ) {
            foreach ( $matches[0] as $match ) {
                $value = self::get_value_form_dynamic_tag( $match );
                if ( is_string( $value ) ) {
                    if ( $return_value ) {return $value;}
                    $final_content = str_replace( $match, $value, $final_content );
                }
            }
        }

        return $final_content;
    }

    public function handle_dynamic_tag_result( $block_content, $block ) {

        $final_content = $block_content;
        if ( isset( $block['blockName'] ) && str_contains( $block['blockName'], 'essential-blocks/' ) ) {
            if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
                foreach ( $block['attrs'] as $index => $attrValue ) {
                    if ( is_string( $attrValue ) ) {
                        $final_content = apply_filters( 'eb_dynamic_tag_value', $attrValue, $final_content, false );
                    }
                }
            }
        }
        return $final_content;
    }

    public static function get_value_form_dynamic_tag( $tag, $post_id = false ) {
        if ( ! is_string( $tag ) ) {
            return $tag;
        }

        $keys = explode( "/", $tag );
        if ( is_array( $keys ) && count( $keys ) > 0 ) {
            //First array should be ESSENTIAL_BLOCKS_DYNAMIC_TAGS
            if ( $keys[0] !== ESSENTIAL_BLOCKS_DYNAMIC_TAGS ) {
                return $tag;
            }

            //Second array should be either "current || other || site"
            $isSite = false;
            switch ( $keys[1] ) {
            case "current":
                if ( ! $post_id ) {
                    global $post;
                    $post_id = isset( $post->ID ) ? $post->ID : get_the_ID();
                }
                break;
            case "other":
                $post_id = (int) ( $keys[2] );
                break;
            case "site":
                $isSite = true;
                break;
            default:
                return $tag;
            }

            $itemsToImplode = array_slice( $keys, 3 );

            // Implode array items with "/"
            $dynamic_key = implode( "/", $itemsToImplode );
            if ( $isSite ) {
                $value = SiteFields::get_values( str_replace( 'site/', '', $dynamic_key ) );
            } else {
                $value = self::get_value_for_post( $dynamic_key, $post_id );
            }

            return is_string( $value ) ? $value : $tag;
        }
    }

    public static function get_value_for_post( $tag, $post_id ) {
        $keys  = explode( "/", $tag );
        $value = false;
        if ( is_array( $keys ) && count( $keys ) > 0 ) {
            switch ( $keys[0] ) {
            case 'acf':
                $value = AcfData::acf_get_value_from_dynamic_key( $tag, $post_id );
                break;
            case 'post':
                $value = PostFields::get_values( $keys[1], $post_id );
                break;
            case 'site':
                $value = SiteFields::get_values( $keys[1] );
                break;
            default:
                $value = false;
            }
            $value = strval( $value );
        }
        return $value;
    }
}
