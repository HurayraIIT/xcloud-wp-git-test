<?php

namespace Embedpress\Pro\Providers;

use EmbedPress\Plugins\Plugin;
use Embedpress\Pro\Classes\Helper;

( defined( 'ABSPATH' ) && defined( 'EMBEDPRESS_IS_LOADED' ) ) or die( "No direct script access allowed." );

/**
 * Entity that represents an EmbedPress plugin dedicated to YouTube embeds.
 *
 * @package     EmbedPress-pro\YouTube
 * @author      EmbedPress<help@embedpress.com>
 * @copyright   Copyright (C) 2019 EmbedPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class Youtube extends Plugin
{
    
    /**
     * Plugin's name.
     *
     * @since   1.0.0
     *
     * @const   NAME
     */
    const NAME = "Youtube";
    
    /**
     * Plugin's slug.
     *
     * @since   1.0.0
     *
     * @const   SLUG
     */
    const SLUG = 'youtube';
    
    /**
     * Return the plugin options schema.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getOptionsSchema()
    {
	    return [
	        'autoplay'       => [
	            'type'        => 'bool',
	            'default'     => false
	        ],
	        'color'          => [
	            'type'        => 'string',
	            'default'     => 'red'
	        ],
	        'cc_load_policy' => [
	            'type'        => 'bool',
	            'default'     => false
	        ],
	        'controls'       => [
	            'type'        => 'string',
	            'options'     => [
	                '1' => 'Display immediately',
	                '2' => 'Display after user initiation',
	                '0' => 'Hide controls',
	            ],
	            'default'     => '1'
	        ],
	        'fs'             => [
	            'type'        => 'bool',
	            'default'     => true
	        ],
	        'iv_load_policy' => [
	            'type'        => 'radio',
	            'default'     => '1'
	        ],
	        'rel'            => [
	            'type'        => 'bool',
	            'default'     => true
	        ],
	        'modestbranding' => [
	            'type'        => 'string',
	            'default'     => '0'
	        ],
	        'yt_lc_show' => [
		        'type'        => 'string',
		        'default'     => ''
	        ],
	        'logo_url' => [
		        'type'        => 'url',
	        ],
	        'logo_xpos' => [
		        'type'        => 'number',
		        'default'     => 10
	        ],
	        'logo_ypos' => [
		        'type'        => 'number',
		        'default'     => 10
	        ],
	        'cta_url' => [
		        'type'        => 'url',
	        ],
	        'yt_sub_channel' => [
		        'type'        => 'url',
	        ],
	        'yt_sub_text' => [
		        'type'        => 'string',
	        ],
	        'yt_sub_layout' => [
		        'type'        => 'string',
	        ],
	        'yt_sub_theme' => [
		        'type'        => 'string',
	        ],
	        'yt_sub_count' => [
		        'type'        => 'bool',
	        ],
	    ];
    }
    
    /**
     * Method that register all EmbedPress events.
     *
     * @return  void
     * @since   1.0.0
     * @static
     *
     */
    public static function registerEvents()
    {
        
    }
    
    /**
     * Callback called right before an url be embedded. If return false, EmbedPress will not embed the url.
     *
     * @param \stdClass    An object representing the embed.
     *
     * @return  mixed
     * @since   1.0.0
     * @static
     *
     */
    public static function onBeforeEmbed( $embed )
    {
        if ( empty( $embed ) ) {
            return false;
        }
        
        return $embed;
    }
    
    /**
     * Callback called right after a YouTube url has been embedded.
     *
     * @param stdclass    An object representing the embed.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function onAfterEmbed( $embed )
    {
	    $options = self::getOptions();
        $isYoutube = ( isset($embed->provider_name) && strtoupper( $embed->provider_name ) === 'YOUTUBE' ) || (isset( $embed->url) && isset( $embed->{$embed->url}) && isset( $embed->{$embed->url}['provider_name']) && strtoupper($embed->{$embed->url}['provider_name'] ) === 'YOUTUBE');

        if ( $isYoutube && isset( $embed->embed )
            && preg_match( '/src=\"(.+?)\"/', $embed->embed, $match ) ) {
	        // Parse the url to retrieve all its info like variables etc.
            $url_full = $match[ 1 ];
            $query = parse_url( $url_full, PHP_URL_QUERY );
            parse_str( $query, $params );
            
            // Handle `autoplay` option.
            if ( isset( $options[ 'autoplay' ] ) && (bool)$options[ 'autoplay' ] === true ) {
                $params[ 'autoplay' ] = 1;
            } else {
                unset( $params[ 'autoplay' ] );
            }
            
            // Handle `color` option.
            if ( !empty( $options[ 'color' ] ) ) {
                $params[ 'color' ] = $options[ 'color' ];
            } else {
                unset( $params[ 'color' ] );
            }
            
            // Handle `cc_load_policy` option.
            if ( isset( $options[ 'cc_load_policy' ] ) && (bool)$options[ 'cc_load_policy' ] === true ) {
                $params[ 'cc_load_policy' ] = 1;
            } else {
                unset( $params[ 'cc_load_policy' ] );
            }
            
            // Handle `controls` option.
            if ( isset( $options[ 'controls' ] ) && in_array( (int)$options[ 'controls' ], [0, 1, 2] ) ) {
                $params[ 'controls' ] = (int)$options[ 'controls' ];
            } else {
                unset( $params[ 'controls' ] );
            }
            
            // Handle `fs` option.
            if ( isset( $options[ 'fs' ] ) && in_array( (int)$options[ 'fs' ], [0, 1] ) ) {
                $params[ 'fs' ] = (int)$options[ 'fs' ];
            } else {
                unset( $params[ 'fs' ] );
            }
            
            // Handle `iv_load_policy` option.
            if ( isset( $options[ 'iv_load_policy' ] ) && in_array( (int)$options[ 'iv_load_policy' ], [1, 3] ) ) {
                $params[ 'iv_load_policy' ] = (int)$options[ 'iv_load_policy' ];
            } else {
                unset( $params[ 'iv_load_policy' ] );
            }
            
            // Handle `rel` option.
            if ( isset( $options[ 'rel' ] ) && in_array( (int)$options[ 'rel' ], [0, 1] ) ) {
                $params[ 'rel' ] = (int)$options[ 'rel' ];
            } else {
                unset( $params[ 'rel' ] );
            }
            
            // Handle `modestbranding` option.
            if ( isset( $options[ 'modestbranding' ] ) && (bool)$options[ 'modestbranding' ] === true ) {
                $params[ 'modestbranding' ] = 1;
            } else {
                unset( $params[ 'modestbranding' ] );
            }
            
            preg_match( '/(.+)?\?/', $url_full, $url );
            $url = $url[ 1 ];

            if(is_object($embed->attributes) && !empty($embed->attributes)){
				$attributes = (array) $embed->attributes;

				$params['controls']       = !empty($attributes['data-controls']) ? $attributes['data-controls'] : '0';
				$params['iv_load_policy'] = !empty($attributes['data-videoannotations']) && ($attributes['data-videoannotations'] == 'true') ? 1 : 0;
				$params['fs']             = !empty($attributes['data-fullscreen']) && ($attributes['data-fullscreen'] == 'true') ? 1 : 0;
				$params['rel']             = !empty($attributes['data-relatedvideos']) && ($attributes['data-relatedvideos'] == 'true') ? 1 : 0;
				$params['end']            = !empty($attributes['data-endtime']) ? $attributes['data-endtime'] : '';
				$params['autoplay'] 		= !empty($attributes['data-autoplay']) && ($attributes['data-autoplay'] == 'true') ? 1 : 0;
				$params['start'] 			= !empty($attributes['data-starttime']) ? $attributes['data-starttime'] : '';
				$params['color'] = !empty($attributes['data-progressbarcolor']) ? $attributes['data-progressbarcolor'] : 'red';
				$params['modestbranding'] = empty($attributes['data-modestbranding']) ? 0 : 1; // Reverse the condition value for modestbranding. 0 = display, 1 = do not display
				$params['cc_load_policy'] = !empty($attributes['data-closedcaptions']) && ($attributes['data-closedcaptions'] == 'true') ? 0 : 1;
			}
            
            // Reassemble the url with the new variables.
            $url_modified = $url . '?';
            foreach ( $params as $paramName => $paramValue ) {
                $url_modified .= $paramName . '=' . $paramValue . '&';
            }

            // Replaces the old url with the new one.
            $embed->embed = str_replace( $url_full, rtrim( $url_modified, '&' ), $embed->embed );
	        $embed = Helper::apply_cta_markup_for_blockeditor( $embed, $options, 'youtube');
	        $embed = Helper::apply_youtube_subscriber_markup( $embed, $options);
	        $embed = Helper::apply_youtube_livechat_markup( $embed, $options);

        }
        
        return $embed;
    }

    
    /**
     * Method to get params
     *
     * @since 1.0.0
     * @static
     */
    public static function getParams( $options )
    {
        $params = [];
        
        // Handle `autoplay` option.
        if ( isset( $options[ 'autoplay' ] ) && (bool)$options[ 'autoplay' ] === true ) {
            $params[ 'autoplay' ] = 1;
        } else {
            unset( $params[ 'autoplay' ] );
        }
        
        // Handle `color` option.
        if ( !empty( $options[ 'color' ] ) ) {
            $params[ 'color' ] = $options[ 'color' ];
        } else {
            unset( $params[ 'color' ] );
        }
        
        // Handle `cc_load_policy` option.
        if ( isset( $options[ 'cc_load_policy' ] ) && (bool)$options[ 'cc_load_policy' ] === true ) {
            $params[ 'cc_load_policy' ] = 1;
        } else {
            unset( $params[ 'cc_load_policy' ] );
        }
        
        // Handle `controls` option.
        if ( isset( $options[ 'controls' ] ) && in_array( (int)$options[ 'controls' ], [0, 1, 2] ) ) {
            $params[ 'controls' ] = (int)$options[ 'controls' ];
        } else {
            unset( $params[ 'controls' ] );
        }
        
        // Handle `fs` option.
        if ( isset( $options[ 'fs' ] ) && in_array( (int)$options[ 'fs' ], [0, 1] ) ) {
            $params[ 'fs' ] = (int)$options[ 'fs' ];
        } else {
            unset( $params[ 'fs' ] );
        }
        
        // Handle `iv_load_policy` option.
        if ( isset( $options[ 'iv_load_policy' ] ) && in_array( (int)$options[ 'iv_load_policy' ], [1, 3] ) ) {
            $params[ 'iv_load_policy' ] = (int)$options[ 'iv_load_policy' ];
        } else {
            unset( $params[ 'iv_load_policy' ] );
        }
        
        // Handle `rel` option.
        if ( isset( $options[ 'rel' ] ) && in_array( (int)$options[ 'rel' ], [0, 1] ) ) {
            $params[ 'rel' ] = (int)$options[ 'rel' ];
        } else {
            unset( $params[ 'rel' ] );
        }
        
        // Handle `modestbranding` option.
        if ( isset( $options[ 'modestbranding' ] ) && (bool)$options[ 'modestbranding' ] === true ) {
            $params[ 'modestbranding' ] = 1;
        } else {
            unset( $params[ 'modestbranding' ] );
        }
        return $params;
    }
    
    public static function featureExtend()
    {
        
        add_action( 'admin_init', [static::class, 'onLoadAdminCallback'] );
	    add_filter( 'embedpress:onAfterEmbed', [static::class, 'onAfterEmbed'], 90 );
	    add_filter( 'embedpress:onBeforeEmbed', [static::class, 'onBeforeEmbed'] );
	    add_filter( 'embedpress_gutenberg_youtube_params', [static::class, 'embedpress_youtube_params'], 90 );
	    add_filter( 'embedpress_youtube_params', [static::class, 'embedpress_youtube_params'], 90 );
        add_filter( 'ep_youtube_settings_before_save', [static::class, 'save_youtube_pro_setting']);
        add_action( 'after_embedpress_branding_save', [static::class, 'save_custom_logo_settings']);
    }
    
    /**
     * Registers the `embedpress/youtube-block` block on server.
     *
     * @since  2.3.1
     */
    public static function embedpress_youtube_params( $youtube_params ) {
        $youtube_options = self::getOptions();
	    return self::getParams( $youtube_options );
    }

	public static function save_youtube_pro_setting( $settings ) {
		$settings['cc_load_policy'] = isset( $_POST['cc_load_policy']) ? sanitize_text_field( $_POST['cc_load_policy']) : '';
		$settings['modestbranding'] = isset( $_POST['modestbranding']) ? sanitize_text_field( $_POST['modestbranding']) : 0;
		$settings['yt_lc_show'] = isset( $_POST['yt_lc_show']) ? sanitize_text_field( $_POST['yt_lc_show']) : '';
		$settings['yt_sub_channel'] = isset( $_POST['yt_sub_channel']) ? sanitize_text_field( $_POST['yt_sub_channel']) : '';
		$settings['yt_sub_text'] = isset( $_POST['yt_sub_text']) ? sanitize_text_field( $_POST['yt_sub_text']) : '';
		$settings['yt_sub_layout'] = isset( $_POST['yt_sub_layout']) ? sanitize_text_field( $_POST['yt_sub_layout']) : '';
		$settings['yt_sub_theme'] = isset( $_POST['yt_sub_theme']) ? sanitize_text_field( $_POST['yt_sub_theme']) : '';
		$settings['yt_sub_count'] = isset( $_POST['yt_sub_count']) ? sanitize_text_field( $_POST['yt_sub_count']) : '';
        return $settings;
	}

	public static function save_custom_logo_settings() {
        Helper::save_custom_logo_settings('youtube', 'yt');
	}

}
