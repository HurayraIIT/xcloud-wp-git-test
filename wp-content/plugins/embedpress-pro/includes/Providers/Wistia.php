<?php

namespace Embedpress\Pro\Providers;

use EmbedPress\Plugins\Plugin;
use Embedpress\Pro\Classes\Helper;

( defined('ABSPATH') && defined('EMBEDPRESS_IS_LOADED')) or die("No direct script access allowed.");

/**
 * Entity that represents an EmbedPress plugin dedicated to Wistia embeds.
 *
 * @package     EmbedPress-pro\Wistia
 * @author      EmbedPress <help@embedpress.com>
 * @copyright   Copyright (C) 2018 EmbedPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class Wistia extends Plugin {

    /**
     * Plugin's name.
     *
     * @since   1.0.0
     *
     * @const   NAME
     */
    const NAME = 'Wistia';

    /**
     * Plugin's slug.
     *
     * @since   1.0.0
     *
     * @const   SLUG
     */
    const SLUG = 'wistia';



    /**
     * Return the plugin options schema.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function getOptionsSchema () {
        $schema = array(
            'display_fullscreen_button' => array(
                'type' => 'bool',
                'default' => true
            ),
            'display_playbar' => array(
                'type' => 'bool',
                'default' => true
            ),
            'small_play_button' => array(
                'type' => 'bool',
                'default' => true
            ),
            'display_volume_control' => array(
                'type' => 'bool',
                'default' => true
            ),
            'autoplay' => array(
                'type' => 'bool',
                'default' => false
            ),
            'volume' => array(
                'type' => 'text',
                'default' => '100'
            ),
            'player_color' => array(
                'type' => 'text',
                'default' => '#00adef',
            ),
            'plugin_resumable' => array(
                'type' => 'bool',
                'default' => false
            ),
            'plugin_captions' => array(
                'type' => 'bool',
                'default' => false
            ),
            'plugin_captions_default' => array(
                'type' => 'bool',
                'default' => false
            ),
            'plugin_focus' => array(
                'type' => 'bool',
                'default' => false
            ),
            'plugin_rewind' => array(
                'type' => 'bool',
                'default' => false
            ),
            'plugin_rewind_time' => array(
                'type' => 'text',
                'default' => '10'
            ),
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
    public static function registerEvents () {
    }

    /**
     * Callback called right before an url be embedded. If return false, EmbedPress will not embed the url.
     *
     * @param  stdclass    An object representing the embed.
     *
     * @return  mixed
     * @since   1.0.0
     * @static
     *
     */
    public static function onBeforeEmbed ($embed) {
        if (empty($embed)) {
            return false;
        }

        return $embed;
    }

    /**
     * Callback called right after a Wistia url has been embedded.
     *
     * @param  stdclass    An object representing the embed.
     *
     * @return  array
     * @since   1.0.0
     * @static
     *
     */
    public static function onAfterEmbed ($embed) {
        if (isset($embed->provider_name)
            && strtoupper($embed->provider_name) === 'WISTIA, INC.'
            && isset($embed->embed)
            && preg_match('/src=\"(.+?)\"/', $embed->embed, $match)) {
            $options = self::getOptions();

            $url_full = $match[1];

            // Parse the url to retrieve all its info like variables etc.
            $query = parse_url($embed->url, PHP_URL_QUERY);
            $url = str_replace('?'.$query, '', $url_full);

            parse_str($query, $params);

            // Set the class in the attributes
            $embed->attributes->class = str_replace('{provider_alias}', 'wistia', $embed->attributes->class);
            $embed->embed = str_replace('ose-wistia, inc.', 'ose-wistia', $embed->embed);

            // Embed Options
            $embedOptions = new \stdClass;
            $embedOptions->videoFoam = true;
            $embedOptions->fullscreenButton = (isset($options['display_fullscreen_button']) && (bool) $options['display_fullscreen_button'] === true);
            $embedOptions->playbar = (isset($options['display_playbar']) && (bool) $options['display_playbar'] === true);
            $embedOptions->smallPlayButton = (isset($options['small_play_button']) && (bool) $options['small_play_button'] === true);
            $embedOptions->volumeControl = (isset($options['display_volume_control']) && (bool) $options['display_volume_control'] === true);
            $embedOptions->autoPlay = (isset($options['autoplay']) && (bool) $options['autoplay'] === true);
            $embedOptions->playButton = true;

            if (isset($options['volume'])) {
                $volume = $options['volume'];
                if (null !== $volume) {
                    $volume = (float) $volume;

                    if ($volume > 1) {
                        $volume = $volume / 100;
                    }

                    $embedOptions->volume = $volume;
                }
            }

            if (isset($options['player_color'])) {
                $color = $options['player_color'];
                if (null !== $color) {
                    $embedOptions->playerColor = $color;
                }
            }

            // Plugins
            $pluginsBaseURL = plugins_url('assets/js/wistia/min', dirname(__DIR__).'/embedpress-Wistia.php');

            $pluginList = array();

            // Resumable
            if (isset($options['plugin_resumable'])) {
                $isResumableEnabled = $options['plugin_resumable'];
                if ($isResumableEnabled) {
                    // Add the resumable plugin
                    $pluginList['resumable'] = array(
                        'src' => $pluginsBaseURL.'/resumable.min.js',
                        'async' => false
                    );
                }
            }

            // Add a fix for the autoplay and resumable work better together
            if (isset($options->autoPlay)) {
                if ($isResumableEnabled) {
                    $pluginList['fixautoplayresumable'] = array(
                        'src' => $pluginsBaseURL.'/fixautoplayresumable.min.js'
                    );
                }
            }

            // Closed Captions plugin
            if (isset($options['plugin_captions'])) {
                $isCaptionsEnabled = $options['plugin_captions'];
                $isCaptionsEnabledByDefault = $options['plugin_captions_default'];
                if ($isCaptionsEnabled) {
                    $pluginList['captions-v1'] = array(
                        'onByDefault' => $isCaptionsEnabledByDefault
                    );
                }
                $embedOptions->captions = $isCaptionsEnabled;
                $embedOptions->captionsDefault = $isCaptionsEnabledByDefault;
            }

            // Focus plugin
            if (isset($options['plugin_focus'])) {
                $isFocusEnabled = $options['plugin_focus'];
                $pluginList['dimthelights'] = array(
                    'src' => $pluginsBaseURL.'/dimthelights.min.js',
                    'autoDim' => $isFocusEnabled
                );
                $embedOptions->focus = $isFocusEnabled;
            }

            // Rewind plugin
            if (isset($options['plugin_rewind'])) {
                if ($options['plugin_rewind']) {
                    $embedOptions->rewindTime = isset($options['plugin_rewind_time']) ? (int) $options['plugin_rewind_time'] : 10;

                    $pluginList['rewind'] = array(
                        'src' => $pluginsBaseURL.'/rewind.min.js'
                    );
                }
            }

            $embedOptions->plugin = $pluginList;
            $embedOptions = json_encode($embedOptions);

            // Get the video ID
            $videoId = self::getVideoIDFromURL($embed->url);
            $shortVideoId = substr($videoId, 0, 3);

            // Responsive?

            $class = array(
                'wistia_embed',
                'wistia_async_'.$videoId
            );

            $attribs = array(
                sprintf('id="wistia_%s"', $videoId),
                sprintf('class="%s"', join(' ', $class)),
                sprintf('style="width:%spx; height:%spx;"', $embed->width, $embed->height)
            );

            $labels = array(
                'watch_from_beginning' => __('Watch from the beginning', 'embedpress-pro'),
                'skip_to_where_you_left_off' => __('Skip to where you left off', 'embedpress-pro'),
                'you_have_watched_it_before' => __('It looks like you\'ve watched<br />part of this video before!',
                    'embedpress-pro'),
            );
            $labels = json_encode($labels);
	        preg_match('/ose-uid-([a-z0-9]*)/', $embed->embed, $matches);
	        $uid = $matches[1];
	        if ( empty( $uid) ) {
		        $uid = isset( $embed->url ) ?  md5($embed->url) : '';
	        }
            $html = "<div class=\"embedpress-wrapper ose-wistia ose-uid-{$uid} responsive\" >";
            $html .= '<script src="https://fast.wistia.com/assets/external/E-v1.js" async></script>';
            $html .= "<script>window.pp_embed_wistia_labels = {$labels};</script>\n";
            $html .= "<script>window._wq = window._wq || []; _wq.push({\"{$shortVideoId}\": {$embedOptions}});</script>\n";
            $html .= '<div '.join(' ', $attribs)." style='position:relative; text-align: left;'></div>\n";
            $html .= '</div>';
            $embed->embed = $html;
	        $embed = Helper::apply_cta_markup_for_blockeditor( $embed, $options, 'wistia');

        }

        return $embed;
    }

    /**
     * @since  2.2.0
     * @param $attributes
     */
    public static function embedpress_wistia_block_after_embed( $attributes ){
        $embedOptions= self::embedpress_wisita_pro_get_options();
        // Get the video ID
        $videoId = self::getVideoIDFromURL($attributes['url']);
        $shortVideoId = $videoId;

        $labels = array(
            'watch_from_beginning'       => __('Watch from the beginning', 'embedpress-pro'),
            'skip_to_where_you_left_off' => __('Skip to where you left off', 'embedpress-pro'),
            'you_have_watched_it_before' => __('It looks like you\'ve watched<br />part of this video before!', 'embedpress-pro'),
        );
        $labels = json_encode($labels);


        $html = '<script src="https://fast.wistia.com/assets/external/E-v1.js"></script>';
        $html .= "<script>window.pp_embed_wistia_labels = {$labels};</script>\n";
        $html .= "<script>wistiaEmbed = Wistia.embed( \"{$shortVideoId}\", {$embedOptions} );</script>\n";
        echo $html;
    }

    /**
     * @since  2.2.0
     */
    public static function embedpress_wisita_pro_get_options() {
        $options = self::getOptions();
        // Embed Options
        $embedOptions = new \stdClass;
        $embedOptions->videoFoam        = true;
        $embedOptions->fullscreenButton = (isset($options['display_fullscreen_button']) && (bool)$options['display_fullscreen_button'] === true);
        $embedOptions->playbar          = (isset($options['display_playbar']) && (bool)$options['display_playbar'] === true);
        $embedOptions->smallPlayButton  = (isset($options['small_play_button']) && (bool)$options['small_play_button'] === true);
        $embedOptions->volumeControl    = (isset($options['display_volume_control']) && (bool)$options['display_volume_control'] === true);
        $embedOptions->autoPlay         = (isset($options['autoplay']) && (bool)$options['autoplay'] === true);
        $embedOptions->playButton         = true;
        
        if (isset($options['volume'])) {
            $volume = $options['volume'];
            if (null !== $volume) {
                $volume = (float) $volume;

                if ($volume > 1) {
                    $volume = $volume / 100;
                }

                $embedOptions->volume = $volume;
            }
        }

        if (isset($options['player_color'])) {
            $color = $options['player_color'];
            if (null !== $color) {
                $embedOptions->playerColor = $color;
            }
        }

        // Plugins
        $pluginsBaseURL = plugins_url('assets/js/wistia/min', dirname(__DIR__) . '/embedpress-Wistia.php');

        $pluginList = array();

        // Resumable
        if (isset($options['plugin_resumable'])) {
            $isResumableEnabled = $options['plugin_resumable'];
            if ($isResumableEnabled) {
                // Add the resumable plugin
                $pluginList['resumable'] = array(
                    'src'   => '//fast.wistia.com/labs/resumable/plugin.js',
                    'async' => false
                );
            }
        }
        // Add a fix for the autoplay and resumable work better together
        if ($options['autoplay']) {
            if ($isResumableEnabled) {
                $pluginList['fixautoplayresumable'] = array(
                    'src' => $pluginsBaseURL . '/fixautoplayresumable.min.js'
                );
            }
        }

        // Closed Captions plugin
        if (isset($options['plugin_captions'])) {
            $isCaptionsEnabled = $options['plugin_captions'];
            $isCaptionsEnabledByDefault = $options['plugin_captions_default'];
            if ($isCaptionsEnabled) {
                $pluginList['captions-v1'] = array(
                    'onByDefault' => $isCaptionsEnabledByDefault
                );
            }
            $embedOptions->captions = $isCaptionsEnabled;
            $embedOptions->captionsDefault = $isCaptionsEnabledByDefault;
        }

        // Focus plugin
        if (isset($options['plugin_focus'])) {
            $isFocusEnabled = $options['plugin_focus'];
            $pluginList['dimthelights'] = array(
                'src'     => '//fast.wistia.com/labs/dim-the-lights/plugin.js',
                'autoDim' => $isFocusEnabled
            );
            $embedOptions->focus = $isFocusEnabled;
        }

        // Rewind plugin
        if (isset($options['plugin_rewind'])) {
            if ($options['plugin_rewind']) {
                $embedOptions->rewindTime = isset($options['plugin_rewind_time']) ? (int) $options['plugin_rewind_time'] : 10;

                $pluginList['rewind'] = array(
                    'src' => $pluginsBaseURL . '/rewind.min.js'
                );
            }
        }

        $embedOptions->plugin = $pluginList;
        $embedOptions         = json_encode($embedOptions);
        return $embedOptions;
    }

    /**
     * Get the Video ID from the URL
     *
     * @param  string  $url
     *
     * @return string
     */
    public static function getVideoIDFromURL ($url) {
        // https://fast.wistia.com/embed/medias/xf1edjzn92.jsonp
        // https://ostraining-1.wistia.com/medias/xf1edjzn92
        preg_match('#\/medias\\\?\/([a-z0-9]+)\.?#i', $url, $matches);

        $id = false;
        if (isset($matches[1])) {
            $id = $matches[1];
        }

        return $id;
    }

    public static function featureExtend () {
        add_action('admin_init', [Wistia::class, 'onLoadAdminCallback']);
        add_filter( 'ep_wistia_settings_before_save', [Wistia::class, 'save_wistia_pro_settings']);
	    add_action( 'after_embedpress_branding_save', [Wistia::class, 'save_custom_logo_settings']);
	    add_filter('embedpress:onAfterEmbed', array(Wistia::class, 'onAfterEmbed'), 90);
	    add_filter('embedpress:onBeforeEmbed', array(Wistia::class, 'onBeforeEmbed'));
	    add_action('embedpress_gutenberg_wistia_block_after_embed', array(Wistia::class,'embedpress_wistia_block_after_embed'), 90);
    }

	public static function save_wistia_pro_settings( $settings ) {
		$settings['display_volume_control'] = isset( $_POST['display_volume_control']) ? sanitize_text_field( $_POST['display_volume_control']) : 1;
		$settings['volume'] = isset( $_POST['volume']) ? intval( sanitize_text_field( $_POST['volume'])) : 100;
		$settings['plugin_captions'] = isset( $_POST['plugin_captions']) ? sanitize_text_field( $_POST['plugin_captions']) : '';
		$settings['plugin_captions_default'] = isset( $_POST['plugin_captions_default']) ? sanitize_text_field( $_POST['plugin_captions_default']) : '';

		return $settings;
    }

	public static function save_custom_logo_settings() {
		Helper::save_custom_logo_settings('wistia', 'wis');
	}
}
