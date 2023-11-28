<?php

namespace Embedpress\Pro\Classes;

use Elementor\Group_Control_Image_Size;
use EmbedPress\Includes\Classes\Elementor_Enhancer;

if ( !defined('ABSPATH') ) {
    exit;
} // Exit if accessed directly

class Helper {

    public static function is_plugin_installed( $basename ) {
        if ( !function_exists('get_plugins') ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        return isset($plugins[$basename]);
    }

	/**
	 * Get plugin data from WP.org repository
	 *
	 * @since 2.0.0
	 */
	public static function get_plugin_data($slug = '')
	{
		$args = array(
			'slug' => $slug,
			'fields' => array(
				'version' => false,
			),
		);

		$response = wp_remote_post(
			'http://api.wordpress.org/plugins/info/1.0/',
			array(
				'body' => array(
					'action' => 'plugin_information',
					'request' => serialize((object) $args),
				),
			)
		);

		if (is_wp_error($response)) {
			return false;
		} else {
			$response = unserialize(wp_remote_retrieve_body($response));

			if ($response) {
				return $response;
			} else {
				return false;
			}
		}
	}

	/**
	 * Check if a plugin is installed
	 *
	 * @since 2.0.0
	 */
	public static function get_plugin_version($basename)
	{
		if (!function_exists('get_plugins')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		return $plugins[$basename]['Version'];
	}

	/**
	 * Install plugin from url
	 *
	 * @since 2.0.0
	 */
	public static function install_plugin($plugin_url)
	{
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

		$skin = new \Automatic_Upgrader_Skin;
		$upgrader = new \Plugin_Upgrader($skin);
		$upgrader->install($plugin_url);

		// activate plugin
		activate_plugin($upgrader->plugin_info(), '', false, true);

		return $skin->result;
	}

	/**
	 * Upgrade plugin
	 *
	 * @since 2.0.0
	 */
	public static function upgrade_plugin($basename) {
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

		$skin = new \Automatic_Upgrader_Skin;
		$upgrader = new \Plugin_Upgrader($skin);
		$upgrader->upgrade($basename);

		return $skin->result;
	}

	/**
	 * Generate safe path
	 *
	 * @since v2.0.0
	 */
	public static function safe_path($path)
	{
		$path = str_replace(['//', '\\\\'], ['/', '\\'], $path);

		return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
	}

    public static function make_lite_available(){
	    $basename = 'embedpress/embedpress.php';
	    $plugin_data = self::get_plugin_data('embedpress');

	    if (self::is_plugin_installed($basename)) {
		    // upgrade plugin - attempt for once
		    if (isset($plugin_data->version) && self::get_plugin_version($basename) != $plugin_data->version) {
			    self::upgrade_plugin($basename);
		    }

		    // activate plugin
		    if (is_plugin_active($basename)) {
			    return delete_transient('embedpress_install');
		    } else {
			    activate_plugin(self::safe_path(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $basename), '', false, true);
			    return delete_transient('embedpress_install');
		    }
	    } else {
		    // install & activate plugin
		    $download_link = isset($plugin_data->download_link) ? $plugin_data->download_link : EMBEDPRESS_PRO_PLUGIN_URL . '/library/embedpress.zip';

		    if (self::install_plugin($download_link)) {
			    return delete_transient('embedpress_install');
		    }
	    }

	    return false;
    }

    public static function save_custom_logo_settings($provider='', $prefix='') {

	    $option_name = EMBEDPRESS_PLG_NAME.":$provider";
	    $settings = (array) get_option( $option_name, []);
	    $settings['branding'] = isset( $_POST[$prefix.'_branding']) ? sanitize_text_field( $_POST[$prefix.'_branding']) : 'no';
	    $settings['logo_xpos'] = isset( $_POST[$prefix.'_logo_xpos']) ? intval( $_POST[$prefix.'_logo_xpos']) : 10;
	    $settings['logo_ypos'] = isset( $_POST[$prefix.'_logo_ypos']) ? intval( $_POST[$prefix.'_logo_ypos']) : 10;
	    $settings['logo_opacity'] = isset( $_POST[$prefix.'_logo_opacity']) ? intval( $_POST[$prefix.'_logo_opacity']) : 0;
	    $settings['logo_id'] = isset( $_POST[$prefix.'_logo_id']) ? intval( $_POST[$prefix.'_logo_id']) : '';
	    $settings['logo_url'] = isset( $_POST[$prefix.'_logo_url']) ? esc_url_raw( $_POST[$prefix.'_logo_url']) : '';
	    $settings['cta_url'] = isset( $_POST[$prefix.'_cta_url']) ? esc_url_raw( $_POST[$prefix.'_cta_url']) : '';
	    $settings = apply_filters( "ep_{$provider}_branding_before_save", $settings);
	    update_option( $option_name, $settings);
	    do_action( "ep_{$provider}_branding_after_save", $settings);
    }

	public static function apply_cta_markup_for_blockeditor( $embed, $settings, $provider_name='' ) {
		if ( empty( $settings['logo_url'] )) {
			return $embed;
		}
		if ( isset( $settings['branding'] ) && 'no' == $settings['branding']) {
			return $embed;
		}
		$cta = '';
		$url = !empty( $settings['cta_url']) ? $settings['cta_url'] : '' ;
		$x = isset( $settings['logo_xpos']) ? $settings['logo_xpos'].'%': '10%';
		$y = isset( $settings['logo_ypos']) ? $settings['logo_ypos'].'%': '10%';
		$opacity = isset( $settings['logo_opacity']) ? ($settings['logo_opacity']/100): 1;
		$clean_provider = str_replace( ' ', '-', $provider_name);
		$cssClass = isset( $embed->url ) ? '.ose-uid-'. md5($embed->url) : '.ose-'.$clean_provider;
		ob_start();
		?>
		<style>
			<?php echo esc_html($cssClass); ?>{
                text-align: left;
                position: relative;
            }
			<?php echo esc_html($cssClass); ?> .watermark {
                border: 0;
                position: absolute;
                bottom: <?php echo esc_html($y); ?>;
                right:  <?php echo esc_html($x); ?>;
                max-width: 150px; max-height: 75px;
                opacity: <?php echo esc_html($opacity); ?>;
                z-index: 5;-o-transition: opacity 0.5s ease-in-out;-moz-transition: opacity 0.5s ease-in-out;-webkit-transition: opacity 0.5s ease-in-out;transition: opacity 0.5s ease-in-out;
            }
			<?php echo esc_html($cssClass); ?> .watermark:hover {
                                                   opacity: 1;
                                               }
		</style>
		<?php
		$style = ob_get_clean();

        $inline_style = 'border:0;position:absolute;bottom:'.esc_html($y).';right:'.esc_html($x).';opacity:1;max-width: 150px; max-height: 75px;z-index: 5;-o-transition: opacity 0.5s ease-in-out;-moz-transition: opacity 0.5s ease-in-out;-webkit-transition: opacity 0.5s ease-in-out;transition: opacity 0.5s ease-in-out;';

		if ($url){
			$cta .= sprintf( '<a href="%s" target="_blank">', esc_url( $url));
		}

		if ( !empty( $settings['logo_url']) ) {
			if ( 'wistia' == $provider_name ) {
				$cta .=  '<img class="watermark" style="'.$inline_style.'" src="'.esc_url( $settings['logo_url']).'"/>';
			}else{
				$cta .=  '<img class="watermark" src="'.esc_url( $settings['logo_url']).'"/>';
            }
		}

		if ($url){
			$cta .= '</a>';
		}


		if ( !class_exists( '\simple_html_dom') ) {
			include_once EMBEDPRESS_PATH_CORE . 'simple_html_dom.php';
		}
		$dom = str_get_html($embed->embed);
		$wrapDiv = $dom->find( 'div.ose-'.$clean_provider, 0);
		if (!empty( $wrapDiv) && is_object( $wrapDiv)){
			$wrapDiv->innertext .= $cta ;
			ob_start();
			echo $wrapDiv;
			$markup = ob_get_clean();
			$dom->clear();
			unset($dom, $wrapDiv);
			if ( 'wistia' == $provider_name ) {
				$embed->embed =  $markup;
			}else{
				$embed->embed =  $style . $markup;

			}
		}


		return $embed;
	}

	public static function apply_youtube_subscriber_markup( $embed_content, $settings ) {

		if ( empty( $settings['yt_sub_channel']) )  return  $embed_content;

			$channel_id = $settings['yt_sub_channel'];
			// if link provided, extract the channel id
			$match = array(); 
			if ( strpos( $channel_id, 'youtube.com' ) !== false ) {
				preg_match( '/channel\/(.+)/', $channel_id, $match );
				if ( empty( $match[1] ) ) {
					return $embed_content;
				}
				$channel_id = $match[1];
			}

			$channel_id = esc_attr( $channel_id );
			$theme      = ! empty( $settings['yt_sub_theme'] ) ? esc_attr( $settings['yt_sub_theme'] ) : 'default';
			$layout     = ! empty( $settings['yt_sub_layout'] ) ? esc_attr( $settings['yt_sub_layout'] ) : 'default';
			$sub_count  = ! empty( $settings['yt_sub_count'] ) && ( 'yes' === $settings['yt_sub_count'] || '1' == $settings['yt_sub_count'] ) ? 'default' : 'hidden';

			$start = '<div class="embedpress-yt-subscribe ' . $theme . '">';
			$end   = '</div>';
			$btn   = $start . '<script src="https://apis.google.com/js/platform.js"></script>';
			if ( ! empty( $settings['yt_sub_text'] ) ) {
				$btn .= "<p class='embedpress-yt-sub-text'>" . esc_html( $settings['yt_sub_text'] ) . "</p>";
			}

			$btn .= "<div class=\"g-ytsubscribe\" data-channel=\"$channel_id\" data-channelid=\"$channel_id\" data-layout=\"$layout\" data-theme=\"$theme\" data-count=\"$sub_count\"></div>";
			$btn .= $end;
			if ( is_admin() && ! isset( $settings['embedpress_pro_embeded_source'] ) ) {
				$btn = '<p>' . esc_html__( 'Subscriber button will show in the frontend', 'embedpress-pro' ) . '</p>';
			}
			if ( is_object( $embed_content ) && isset( $embed_content->embed ) ) {
				$embed_content->embed = '<div>'.$embed_content->embed . $btn.'</div>';
			}

			return $embed_content;
	}

	public static function apply_youtube_livechat_markup( $embed_content, $settings ) {
	    $show_chat = false;
		if ( isset( $embed_content->url ) ) {
			$pars_url_yt = self::parse_query( $embed_content->url );

			    if (
				!empty( $settings['yt_lc_show'] )
				&& 'yes' == $settings['yt_lc_show']
			){
					$show_chat = true;
			}
			$video_id = array_shift( $pars_url_yt);

			if (empty($video_id)) {
				$url = $embed_content->url;
				$html = wp_remote_retrieve_body( wp_remote_get( $url ) );
				preg_match( '/"videoId":"([^"]+)"/', $html, $matches );
				if(!empty($matches[1])){
					$video_id = $matches[1];
				}
			}

			if ( $show_chat ) {
				$pars_url    = wp_parse_url( get_site_url() );
				$host = ! empty( $pars_url['host'] ) ? $pars_url['host'] : '';
				$chat_markup = '<div class="embedpress-yt-live-chat-wrap" style="width:'.$embed_content->attributes->{'data-width'}.'px; min-height:450px; max-width:100%;margin-top:20px; "><iframe width="'.$embed_content->attributes->{'data-width'}.'" height="550" src="https://www.youtube.com/live_chat?v='.esc_attr( $video_id ).'&embed_domain='.esc_attr( $host ).'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
				$embed_content->embed .= $chat_markup;
			}

        }
        return $embed_content;
	}

	/**
	 * Parse a query string into an associative array.
	 *
	 * If multiple values are found for the same key, the value of that key
	 * value pair will become an array. This function does not parse nested
	 * PHP style arrays into an associative array (e.g., foo[a]=1&foo[b]=2 will
	 * be parsed into ['foo[a]' => '1', 'foo[b]' => '2']).
	 *
	 * @param string   $str         Query string to parse
	 * @param int|bool $urlEncoding How the query string is encoded
	 *
	 * @return array
	 */
	public static function parse_query($str, $urlEncoding = true)
	{
		$result = [];

		if ($str === '') {
			return $result;
		}

		if ($urlEncoding === true) {
			$decoder = function ($value) {
				return rawurldecode(str_replace('+', ' ', $value));
			};
		} elseif ($urlEncoding === PHP_QUERY_RFC3986) {
			$decoder = 'rawurldecode';
		} elseif ($urlEncoding === PHP_QUERY_RFC1738) {
			$decoder = 'urldecode';
		} else {
			$decoder = function ($str) { return $str; };
		}

		foreach (explode('&', $str) as $kvp) {
			$parts = explode('=', $kvp, 2);
			$key = $decoder($parts[0]);
			$value = isset($parts[1]) ? $decoder($parts[1]) : null;
			if (!isset($result[$key])) {
				$result[$key] = $value;
			} else {
				if (!is_array($result[$key])) {
					$result[$key] = [$result[$key]];
				}
				$result[$key][] = $value;
			}
		}

		return $result;
	}


	public static function print_cta_markup_for_document( $settings, $url, $id, $obj ) {

		if ( empty( $settings["embedpress_pro_document_logo"]) || empty( $settings["embedpress_pro_document_logo"]['url'])  ) {
			return null;
		}
		$img = Group_Control_Image_Size::get_attachment_image_html( $settings, "embedpress_pro_document_logo" );
		if ( empty( $img) ) {
			return null;
		}

		$cta = '';
		$url = '';
		$target = '';
		$x = !empty( $settings["embedpress_pro_document_logo_xpos"]) && !empty( $settings["embedpress_pro_document_logo_xpos"]['unit']) ? $settings["embedpress_pro_document_logo_xpos"]['unit'].$settings["embedpress_pro_document_logo_xpos"]['size']: '10%';

		$y = !empty( $settings["embedpress_pro_document_logo_ypos"]) && !empty( $settings["embedpress_pro_document_logo_ypos"]['unit']) ? $settings["embedpress_pro_document_logo_ypos"]['unit'].$settings["embedpress_pro_document_logo_ypos"]['size']: '10%';
		$cssClass = '.ep-doc-'.md5( $id);
		ob_start();
		?>
        <style type="text/css">
            <?php echo esc_html($cssClass); ?>{
                text-align: left;
                position: relative;
            }
            <?php echo esc_html($cssClass); ?> .watermark {
                border: 0;
                position: absolute;
                bottom: <?php echo esc_html($y); ?>;
                right:  <?php echo esc_html($x); ?>;
                max-width: 150px;
                max-height: 75px;
                opacity: 0.25;
                z-index: 5;
                -o-transition: opacity 0.5s ease-in-out;
                -moz-transition: opacity 0.5s ease-in-out;
                -webkit-transition: opacity 0.5s ease-in-out;
                transition: opacity 0.5s ease-in-out;
            }
            <?php echo esc_html($cssClass); ?> .watermark:hover {
                                                   opacity: 1;
                                               }
        </style>
		<?php
		$style = ob_get_clean();

		if ( !class_exists( '\simple_html_dom') ) {
			include_once EMBEDPRESS_PATH_CORE . 'simple_html_dom.php';
		}

		if ( !empty( $settings["embedpress_pro_document_cta"]) && !empty( $settings["embedpress_pro_document_cta"]['url']) ) {
			$url = $settings["embedpress_pro_document_cta"]['url'];
		}

		if ($url){
			$atts = Elementor_Enhancer::get_link_attributes( $settings["embedpress_pro_document_cta"]);
			$attributes = '';
			foreach ( $atts as $att => $value ) {
				$attributes .=$att.'="'.esc_attr($value).'" ';
			}
			$cta .= sprintf( '<a %s>', trim( $attributes));
		}


		$imgDom = str_get_html( $img);
		$imgDom = $imgDom->find( 'img', 0);
		$imgDom->setAttribute( 'class', 'watermark');
		$imgDom->removeAttribute( 'style');
		$imgDom->setAttribute( 'width', 'auto');
		$imgDom->setAttribute( 'height', 'auto');
		ob_start();
		echo  $imgDom;
		$cta .= ob_get_clean();
		$imgDom->clear();
		unset( $img, $imgDom);

		if ($url){
			$cta .= '</a>';
		}


		echo $style . $cta;

	}
	public static function print_cta_markup_for_gutenberg_document( $hash, $provider_name = 'document' ) {
		$settings = (array) get_option(EMBEDPRESS_PLG_NAME . ':document');

		if ( empty( $settings["logo_url"])  ) {
			return null;
		}

		$cta = '';
		$url = !empty( $settings['cta_url']) ? $settings['cta_url'] : '' ;
		$x = isset( $settings['logo_xpos']) ? $settings['logo_xpos'].'%': '10%';
		$y = isset( $settings['logo_ypos']) ? $settings['logo_ypos'].'%': '10%';
		$opacity = isset( $settings['logo_opacity']) ? ($settings['logo_opacity']/100): 1;
		$cssClass = '.ep-doc-'.$hash;
		ob_start();
		?>
        <style>
            <?php echo esc_html($cssClass); ?>{
                text-align: left;
                position: relative;
            }
            <?php echo esc_html($cssClass); ?> .watermark {
                border: 0;
                position: absolute;
                bottom: <?php echo esc_html($y); ?>;
                right:  <?php echo esc_html($x); ?>;
                max-width: 150px; max-height: 75px;
                opacity: <?php echo esc_html($opacity); ?>;
                z-index: 5;-o-transition: opacity 0.5s ease-in-out;-moz-transition: opacity 0.5s ease-in-out;-webkit-transition: opacity 0.5s ease-in-out;transition: opacity 0.5s ease-in-out;
            }
            <?php echo esc_html($cssClass); ?> .watermark:hover {
                                                   opacity: 1;
                                               }
        </style>
		<?php
		$style = ob_get_clean();

		if ($url){
			$cta .= sprintf( '<a href="%s" target="_blank">', esc_url( $url));
		}


		if ( !empty( $settings['logo_url']) ) {
			if ( 'wistia' == $provider_name ) {
				$inline_style = 'border:0;position:absolute;bottom:'.esc_html($y).';right:'.esc_html($x).';opacity:1;max-width: 150px; max-height: 75px;z-index: 5;-o-transition: opacity 0.5s ease-in-out;-moz-transition: opacity 0.5s ease-in-out;-webkit-transition: opacity 0.5s ease-in-out;transition: opacity 0.5s ease-in-out;';
				$cta .=  '<img class="watermark" style="'.$inline_style.'" src="'.esc_url( $settings['logo_url']).'"/>';
			}else{
				$cta .=  '<img class="watermark" src="'.esc_url( $settings['logo_url']).'"/>';
			}
		}

		if ($url){
			$cta .= '</a>';
		}

		echo $style . $cta;

	}


}
