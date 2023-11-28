<?php

namespace Embedpress\Pro\Classes;

use Embedpress\Pro\Elementor\Extender;
use Embedpress\Pro\Traits\Enqueue;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Exit if accessed directly

class Bootstrap {
    use Enqueue;

    // instance container
    private static $instance = null;

    /**
     * Singleton instance
     *
     * @since 2.0.0
     */
    public static function instance() {
        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor of plugin class
     *
     * @since 2.0.0
     */
    private function __construct() {
	    add_filter( 'ep_general_settings_before_save', [$this, 'save_general_pro_settings'], 10);
	    // for document branding
	    //add_action( 'after_embedpress_branding_save', [static::class, 'save_custom_logo_settings_for_document']);
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        $this->embedpress_plugin_licensing();
        $this->load_provider();

        add_action( 'elementor/widget/embedpres_elementor/skins_init', [ $this, 'elementor_setting_init' ] );
        add_filter('embedpress:onAfterEmbed', [$this, 'add_pro_feature']);
        add_filter('embedpress:onBeforeEmbed', [$this, 'apple_podcast'], 99, 2);

        add_filter( 'embedpress_boomplay_content_type', [$this, 'add_boomplay_album_playlists_support'], 10, 2);
        add_action( 'embedpress_pdf_gutenberg_after_embed', [Helper::class, 'print_cta_markup_for_gutenberg_document']);
    }

	public function add_boomplay_album_playlists_support( $endpoint_type, $type ) {
    	return in_array( $type, ['albums', 'playlists']) ? 'COL': 'MUSIC';
    }

    /**
     * Load Provider class
     *
     * @since  2.0.0
     */
    public function load_provider() {
	    $provider_arr = apply_filters( 'emebedpress_pro_providers', [ 'Youtube', 'Vimeo', 'Wistia', 'Twitch', 'Dailymotion', 'Soundcloud', 'Document' ]);
        $name_space   = '\Embedpress\Pro\Providers';
        array_walk( $provider_arr, function ( $value ) use ( $name_space ) {
            $class_name = $name_space . '\\' . $value;
            $class_name::featureExtend();
        } );
    }

    /**
     * @since  2.3.0
     */
    public function embedpress_plugin_licensing() {
        if ( is_admin() ) {
            // Setup the settings page and validation
            new EmbedPress_Licensing(
                EMBEDPRESS_SL_ITEM_SLUG,
                EMBEDPRESS_SL_ITEM_NAME,
                'embedpress-pro'
            );
        }
    }

    /**
     * Load Elementor control
     *
     * @since  2.3.3
     */
    public function elementor_setting_init() {
        Extender::embedpress_elementor_init();
    }

	/**
	 * @param object $embed_object it contains url, attributes, embed, provider name etc. @see line 247 in Shortcode.php
	 *
	 * @return object
	 */
	public function add_pro_feature( $embed_object ) {
		if ( empty( $embed_object->embed) ) {
			return $embed_object;
		}

		if ( !class_exists( '\simple_html_dom') && defined( 'EMBEDPRESS_PATH_CORE') ) {
			include_once EMBEDPRESS_PATH_CORE . 'simple_html_dom.php';
		}
		if ( function_exists( 'str_get_html') ) {
			$dom = str_get_html($embed_object->embed);
			$ifDom = $dom->find( 'iframe', 0);
			if (!empty( $ifDom) && is_object( $ifDom)){
				$ifDom->setAttribute( 'loading', 'lazy');
			}
			ob_start();
			echo $dom;
			$m = ob_get_clean();
			$dom = str_get_html($m);

			$imgDom = $dom->find( 'img', 0);
			if (!empty( $imgDom) && is_object( $imgDom)){
				$imgDom->setAttribute( 'loading', 'lazy');
			}

			ob_start();
			echo $dom;
			$embed_object->embed = ob_get_clean();
		}


		return $embed_object;
    }

	public function apple_podcast( $url_data, $url ) {

		if ( strpos( $url, 'podcasts.apple.com') ) {
			$iframe_url = str_replace( 'podcasts.apple.com', 'embed.podcasts.apple.com', $url);
			$html = '<iframe allow="autoplay *; encrypted-media *; fullscreen *" frameborder="0" height="175" style="width:100%;max-width:660px;overflow:hidden;background:transparent;" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-storage-access-by-user-activation allow-top-navigation-by-user-activation" src="'.esc_url( $iframe_url).'"></iframe>';
			$url_data = [
				'type'          => 'html',
				'provider_name' => 'Apple Podcast',
				'provider_url'  => 'https://podcasts.apple.com',
				'url'           => $url,
				'html'          => $html,
			];
		}
		return $url_data;
	}

	public function save_general_pro_settings( $settings ) {
		$settings['g_lazyload'] = isset( $_POST['g_lazyload']) ? intval( $_POST['g_lazyload']) : 0;

		return $settings;
    }
	public static function save_custom_logo_settings_for_document() {
		Helper::save_custom_logo_settings('document', 'doc');
	}

}
