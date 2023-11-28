<?php

namespace Embedpress\Pro\Elementor;

use Embedpress\Pro\Classes\Helper;

( defined( 'ABSPATH' ) && defined( 'EMBEDPRESS_IS_LOADED' ) ) or die( "No direct script access allowed." );

class Extender
{
    
    public static function embedpress_elementor_init()
    {
        //remove youtube,vimeo,wistia filter when Elementor widget loaded
        self::remove_embed_hook();
        add_filter( 'embedpress_elementor_embed', [Helper::class, 'apply_youtube_subscriber_markup'], 10, 2);
        add_filter( 'embedpress_elementor_embed', [Helper::class, 'apply_youtube_livechat_markup'], 10, 2);
        add_action( 'embedpress_document_after_embed', [Helper::class, 'print_cta_markup_for_document'], 10, 4);
        add_action( 'embedpress_pdf_after_embed', [Helper::class, 'print_cta_markup_for_document'], 10, 4);


	    add_filter( 'embedpress_document_powered_by_control', [Extender::class, 'embedpress_document_powered_by_control'] );
    }
    public static function remove_embed_hook()
    {
        remove_filter( 'embedpress:onAfterEmbed', ['Embedpress\Pro\Providers\Vimeo', 'onAfterEmbed'], 90 );
        remove_filter( 'embedpress:onAfterEmbed', ['Embedpress\Pro\Providers\Youtube', 'onAfterEmbed'], 90 );
        remove_filter( 'embedpress:onAfterEmbed', ['Embedpress\Pro\Providers\Wistia', 'onAfterEmbed'], 90 );
        remove_filter( 'embedpress:onAfterEmbed', ['Embedpress\Pro\Providers\Twitch', 'onAfterEmbed'], 90 );
        remove_filter( 'embedpress:onAfterEmbed', ['Embedpress\Pro\Providers\Dailymotion', 'onAfterEmbed'], 90 );
        remove_filter( 'embedpress:onAfterEmbed', ['Embedpress\Pro\Providers\Soundcloud', 'onAfterEmbed'], 90 );
    }
	public static function embedpress_document_powered_by_control($value){
		return 'no';
	}

}
