<?php

namespace Embedpress\Pro\Providers;

use EmbedPress\Plugins\Plugin;
use Embedpress\Pro\Classes\Helper;

( defined( 'ABSPATH' ) && defined( 'EMBEDPRESS_IS_LOADED' )) or die( "No direct script access allowed." );

/**
 * Entity that represents an EmbedPress plugin dedicated to Vimeo embeds.
 *
 * @package     EmbedPress\Vimeo
 * @author      EmbedPress <help@embedpress.com>
 * @copyright   Copyright (C) 2018 EmbedPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class Vimeo extends Plugin {
    /**
     * Plugin's name.
     *
     * @since   1.0.0
     *
     * @const   NAME
     */
    const NAME = 'Vimeo';

    /**
     * Plugin's slug.
     *
     * @since   1.0.0
     *
     * @const   SLUG
     */
    const SLUG = 'vimeo';

    /**
     * Return the plugin options schema.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getOptionsSchema() {
        $schema = array(
            'autoplay' => array(
                'type' => 'bool',
                'default' => false
            ),
            'loop' => array(
                'type' => 'bool',
                'default' => false
            ),
            'autopause' => array(
                'type' => 'bool',
                'default' => false
            ),
            'vimeo_dnt' => array(
                'type' => 'bool',
                'default' => true,
            ),
            'color' => array(
                'type' => 'text',
                'default' => '#00adef',
            ),
            'display_title' => array(
                'type' => 'bool',
                'default' => true
            ),
            'display_author' => array(
                'type' => 'bool',
                'default' => true
            ),
            'display_avatar' => array(
                'type' => 'bool',
                'default' => true
            )
        );

        return $schema;
    }

    /**
     * Method that register all EmbedPress events.
     *
     * @return  void
     * @since   1.0.0
     * @static
     *
     */
    public static function registerEvents() {
    }

    /**
     * Callback called right before an url be embedded. If return false, EmbedPress will not embed the url.
     *
     * @param stdclass    An object representing the embed.
     *
     * @return  mixed
     * @since   1.0.0
     * @static
     *
     */
    public static function onBeforeEmbed( $embed ) {
        if ( empty( $embed ) ) {
            return false;
        }

        return $embed;
    }

    /**
     * Callback called right after a Vimeo url has been embedded.
     *
     * @param stdclass    An object representing the embed.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function onAfterEmbed( $embed ) {

        $options = self::getOptions();
        if ( isset( $embed->provider_name )
            && strtoupper( $embed->provider_name ) === 'VIMEO'
            && isset( $embed->embed )
            && preg_match( '/src=\"(.+?)\"/', $embed->embed, $match ) ) {
            $url_full = $match[1];
            $params = [];
            // Handle `display_title` option.
            if ( isset( $options['display_title'] ) && (bool)$options['display_title'] === true ) {
                $params['title'] = 1;
            } else {
                $params['title'] = 0;
            }

            // Handle `display_author` option.
            if ( isset( $options['display_author'] ) && (bool)$options['display_author'] === true ) {
                $params['byline'] = 1;
            } else {
                $params['byline'] = 0;
            }

            // Handle `autoplay` option.
            if ( isset( $options['autoplay'] ) && (bool)$options['autoplay'] === true ) {
                $params['autoplay'] = 1;
            } else {
                unset( $params['autoplay'] );
            }

            // Handle `loop` option.
            if ( isset( $options['loop'] ) && (bool)$options['loop'] === true ) {
                $params['loop'] = 1;
            } else {
                unset( $params['loop'] );
            }

            // Handle `autopause` option.
            if ( isset( $options['autopause'] ) && (bool)$options['autopause'] === true ) {
                $params['autopause'] = 1;
            } else {
                unset( $params['autopause'] );
            }

            // Handle `dnt` option.
            // $params['dnt'] = 1;
            // if ( isset( $options['vimeo_dnt'] ) && (bool)$options['vimeo_dnt'] === false ) {
            //     $params['dnt'] = 0;
            // }

            // Handle `display_avatar` option.
            if ( isset( $options['display_avatar'] ) && (bool)$options['display_avatar'] === true ) {
                $params['portrait'] = 1;
            } else {
                $params['portrait'] = 0;
            }

            // Handle `color` option.
            if ( !empty( $options['color'] ) ) {
                $params['color'] = str_replace( '#', '', $options['color'] );
            } else {
                unset( $params['color'] );
            }

            

            // Reassemble the url with the new variables.
            $url_modified = $url_full;

        
            if(is_object($embed->attributes) && !empty($embed->attributes)){
				$attributes = (array) $embed->attributes;
                if(function_exists('stringToBoolean')){
                    $attributes = stringToBoolean($attributes);
                }
				$params['title'] = !empty($attributes['data-vtitle']) ? 1 : 0;
				$params['byline']             = !empty($attributes['data-vauthor'])  ? 1 : 0;
				$params['portrait']             = !empty($attributes['data-vavatar']) ? 1 : 0;
				$params['autoplay'] 		= !empty($attributes['data-vautoplay']) ? 1 : 0;
				$params['loop'] 		= !empty($attributes['data-vloop']) ? 1 : 0;
                if(empty($attributes['data-vautopause'])) :
					$params['dnt'] 		= !empty($attributes['data-vdnt']) ? 1 : 0;
				endif;
				$params['autopause'] 		= !empty($attributes['data-vautopause']) ? 1 : 0;
				$params['color'] = !empty($attributes['data-vscheme']) ? str_replace("#", "", $attributes['data-vscheme']) : '00ADEF';
				$params['t'] 			= !empty($attributes['data-vstarttime']) ? $attributes['data-vstarttime'] : '';

			}
        
            foreach ( $params as $param => $value ) {
                $url_modified = add_query_arg( $param, $value, $url_modified );
            }

            $url_modified = str_replace("&t=", "#t=", $url_modified);

            // Replaces the old url with the new one.
            $embed->embed = str_replace( $url_full, $url_modified, $embed->embed );
	        $embed = Helper::apply_cta_markup_for_blockeditor( $embed, $options, 'vimeo');

        }

        return $embed;
    }

    /**
     * Get setting data for vimeo
     *
     * @since  2.3.1
     * @param $options
     * @return array
     */
    public static function getParams($options) {
        $params   = [];

        // Handle `display_title` option.
        if (isset($options['display_title']) && (bool)$options['display_title'] === true) {
            $params['title'] = 1;
        } else {
            $params['title'] = 0;
        }

        // Handle `display_author` option.
        if (isset($options['display_author']) && (bool)$options['display_author'] === true) {
            $params['byline'] = 1;
        } else {
            $params['byline'] = 0;
        }

        // Handle `autoplay` option.
        if (!empty($options['autoplay'])) {
            $params['autoplay'] = 1;
        } else {
            unset($params['autoplay']);
        }

        // Handle `loop` option.
        if (isset($options['loop']) && (bool)$options['loop'] === true) {
            $params['loop'] = 1;
        } else {
            unset($params['loop']);
        }

        // Handle `autopause` option.
        if (isset($options['autopause']) && (bool)$options['autopause'] === true) {
            $params['autopause'] = 1;
        } else {
            unset($params['autopause']);
        }

        // Handle `dnt` option.
        // $params['dnt'] = 1;
        // if ( isset( $options['vimeo_dnt'] ) && (bool)$options['vimeo_dnt'] === false ) {
        //     $params['dnt'] = 0;
        // }

        // Handle `display_avatar` option.
        if (isset($options['display_avatar']) && (bool)$options['display_avatar'] === true) {
            $params['portrait'] = 1;
        } else {
            $params['portrait'] = 0;
        }

        // Handle `color` option.
        if (!empty($options['color'])) {
            $params['color'] = str_replace('#', '', $options['color']);
        } else {
            unset($params['color']);
        }
        return $params;
    }

    /**
     * Load Vimeo element
     *
     * @since  2.3.1
     */
    public static function featureExtend() {
        add_action( 'admin_init', [ Vimeo::class, 'onLoadAdminCallback' ] );

        add_action( EMBEDPRESS_PLG_NAME . ':vimeo:settings:register',
            [ Vimeo::class, 'registerSettings' ] );
        add_action( EMBEDPRESS_PLG_NAME . ':settings:render:tab', [ Vimeo::class, 'renderTab' ] );

        add_filter( 'embedpress_vimeo_params', [Vimeo::class, 'embedpress_vimeo_params'], 90);
        add_filter( 'ep_vimeo_settings_before_save', [Vimeo::class, 'save_vimeo_pro_setting']);
	    add_action( 'after_embedpress_branding_save', [Vimeo::class, 'save_custom_logo_settings']);
	    add_filter( 'embedpress:onAfterEmbed', array( Vimeo::class, 'onAfterEmbed' ), 90 );
	    add_filter( 'embedpress:onBeforeEmbed', array( Vimeo::class, 'onBeforeEmbed' ) );
	    //add_action( 'init', array( get_called_class(), 'embedpress_gutenberg_register_block_vimeo' ) );
    }

	public static function embedpress_vimeo_params( $params ) {
		$vimeo_options = self::getOptions();

		return self::getParams( $vimeo_options );
    }

    /**
     * Registers the `embedpress/vimeo-block` block on server.
     *
     * @since  2.3.1
     */
    public static function embedpress_gutenberg_register_block_vimeo() {
        if ( function_exists( 'register_block_type' ) ) :
            register_block_type( 'embedpress/vimeo-block', array(
                'attributes' => array(
                    'url' => array(
                        'type' => 'string',
                        'default' => ''
                    ),
                    'iframeSrc' => array(
                        'type' => 'string',
                        'default' => ''
                    ),
                ),
                'render_callback' => [ get_called_class(), 'embedpress_gutenberg_render_block_vimeo' ]
            ) );
        endif;
    }

    /**
     *
     * Renders the `embedpress/vimeo` block on server.
     *
     * @since  2.3.1
     * @param $attributes
     * @return false|string
     */
    public static function embedpress_gutenberg_render_block_vimeo( $attributes ) {
        ob_start();
        if ( !empty( $attributes ) && !empty( $attributes['iframeSrc'] ) ) :
            $vimeo_options = self::getOptions();
            $vimeo_params = self::getParams( $vimeo_options );
            $iframeUrl = $attributes['iframeSrc'];
            $align = 'align' . ( isset( $attributes[ 'align' ] ) ? $attributes[ 'align' ] : 'center' );
            
            foreach ( $vimeo_params as $param => $value ) {
                $iframeUrl = add_query_arg( $param, $value, $iframeUrl );
            }

            ?>
            <div class="ose-vimeo wp-block-embed-vimeo <?php echo $align; ?>">
                <iframe src="<?php echo $iframeUrl; ?>" allowtransparency="true" frameborder="0" width="640"
                        height="360">
                </iframe>
            </div>
        <?php
        endif;
        return ob_get_clean();
    }

	public static function save_vimeo_pro_setting( $settings ) {
		$settings['loop'] = isset( $_POST['loop']) ? sanitize_text_field( $_POST['loop']) : '';
		$settings['autopause'] = isset( $_POST['autopause']) ? sanitize_text_field( $_POST['autopause']) : '';
		$settings['vimeo_dnt'] = isset( $_POST['vimeo_dnt']) ? sanitize_text_field( $_POST['vimeo_dnt']) : 1;

        return $settings;
    }

	public static function save_custom_logo_settings() {
		Helper::save_custom_logo_settings('vimeo', 'vm');
	}
}
