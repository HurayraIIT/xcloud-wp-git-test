<?php

namespace BetterLinksPro\Frontend;

class AffiliateDisclosure {
    private $link_options;
    public static function init()
    {
        $self = new self();
        $self->link_options = defined('BETTERLINKS_LINKS_OPTION_NAME') ? json_decode(get_option(BETTERLINKS_LINKS_OPTION_NAME), true) : [];
        add_filter('the_content', [$self, 'add_affiliate_disclosure'], 100);
        add_filter('get_the_excerpt', [$self, 'add_affiliate_disclosure'], 100);
        add_action( 'wp_enqueue_scripts', [$self, 'add_styles'] );
    }

    public function is_using_gutenberg_block() {
        $current_screen = get_current_screen();
        $is_using_block_editor = $current_screen->is_block_editor || (function_exists( 'is_gutenberg_page' ) && is_gutenberg_page());
        return $is_using_block_editor;
    }

    public function add_styles() {
        $is_enabled_affiliate_disclosure = !empty( $this->link_options['affiliate_link_disclosure'] ) ? $this->link_options['affiliate_link_disclosure'] : false;
        if( defined('BETTERLINKS_ASSETS_URI') && $is_enabled_affiliate_disclosure) {
            wp_enqueue_style( 'betterlinks-quil-editor', BETTERLINKS_ASSETS_URI . 'css/ql-editor.css', false );
        }
    }

    public function add_affiliate_disclosure($content) {
        if (is_attachment() || is_feed()) {
            return $content;
        }
        
        $ID = get_the_ID();
        // $settings = json_decode(get_option(BETTERLINKS_LINKS_OPTION_NAME), true);
        $settings = $this->link_options;
        
        $is_enabled_affiliate_disclosure = !empty( $settings['affiliate_link_disclosure'] ) ? $settings['affiliate_link_disclosure'] : false;
        $affiliate_link_position = !empty( $settings['affiliate_link_position'] ) ? sanitize_text_field( $settings['affiliate_link_position'] ) : '';
        $affiliate_disclosure_text = !empty( $settings['affiliate_disclosure_text'] ) ? $settings['affiliate_disclosure_text'] : '';
        $is_this_post_enabled = get_post_meta( $ID, 'betterlinks_enable_affiliate_link_disclosure' );
        
        // Advanced options for affliate disclosure styles
        $is_advance_option_enabled = !empty( $settings['affiliate_advanced_options'] ) ? sanitize_text_field( $settings['affiliate_advanced_options'] ) : '0';
        
        $affiliate_disclosure_bg_color = (!empty($is_advance_option_enabled) && !empty($settings['affiliate_disclosure_bg_color'] ) ? $settings['affiliate_disclosure_bg_color'] : '');

        $affiliate_disclosure_css = sprintf('background-color: %1$s;', $affiliate_disclosure_bg_color);

        $want_border = (!empty($settings['affiliate_disclosure_want_border'])) ? filter_var($settings['affiliate_disclosure_want_border'], FILTER_VALIDATE_BOOLEAN) : false;
        $border_size = (!empty( $settings['affiliate_disclosure_border_size'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_size']) : '';

        $border_style = (!empty( $settings['affiliate_disclosure_border_style'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_style']) : 'solid';

        $border_color = (!empty( $settings['affiliate_disclosure_border_color'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_color']) : '';

        $font_unit = (isset( $settings['affiliate_disclosure_font_unit'] )) ? sanitize_text_field($settings['affiliate_disclosure_font_unit']) : 'px';
        $font_size =  (!empty( $settings['affiliate_disclosure_font_size'] )) ? sprintf(
                'font-size: %1$s%2$s;', 
                esc_attr($settings['affiliate_disclosure_font_size']), 
                esc_attr($font_unit)
            ) : '';
        
        $width_unit =  (isset( $settings['affiliate_disclosure_width_unit'] )) ? sanitize_text_field( $settings['affiliate_disclosure_width_unit'] ) : '%';
        $width =  (!empty( $settings['affiliate_disclosure_width'] )) ? sprintf(
                'width: %1$s%2$s;', 
                esc_attr($settings['affiliate_disclosure_width']),
                esc_attr($width_unit)
            ) : '';
        
        //PADDING
        $padding_unit =  (!empty( $settings['affiliate_disclosure_padding_unit'] )) ? sanitize_text_field($settings['affiliate_disclosure_padding_unit']) : 'px';
        $padding_top =  (!empty( $settings['affiliate_disclosure_padding_top'] )) ? sprintf('padding-top: %1$s%2$s;', esc_attr($settings['affiliate_disclosure_padding_top']), esc_attr($padding_unit)) : '';
        $padding_right =  (!empty( $settings['affiliate_disclosure_padding_right'] )) ? sprintf('padding-right: %1$s%2$s;', esc_attr($settings['affiliate_disclosure_padding_right']), esc_attr($padding_unit)) : '';
        $padding_bottom =  (!empty( $settings['affiliate_disclosure_padding_bottom'] )) ? sprintf('padding-bottom: %1$s%2$s;', esc_attr($settings['affiliate_disclosure_padding_bottom']), esc_attr($padding_unit)) : '';
        $padding_left =  (!empty( $settings['affiliate_disclosure_padding_left'] )) ? sprintf('padding-left: %1$s%2$s;', esc_attr($settings['affiliate_disclosure_padding_left']), esc_attr($padding_unit)) : '';

        // BORDER RADIUS
        $radius_unit = !empty( $settings['affiliate_disclosure_border_radius_unit'] ) ? sanitize_text_field( $settings['affiliate_disclosure_border_radius_unit'] ) : 'px';
        $radius_top = (!empty( $settings['affiliate_disclosure_border_radius_top'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_radius_top']) : '5';
        $radius_right = (!empty( $settings['affiliate_disclosure_border_radius_right'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_radius_right']) : '5';
        $radius_bottom = (!empty( $settings['affiliate_disclosure_border_radius_bottom'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_radius_bottom']) : '5';
        $radius_left = (!empty( $settings['affiliate_disclosure_border_radius_left'] )) ? sanitize_text_field($settings['affiliate_disclosure_border_radius_left']) : '5';
        $radius_css = sprintf('border-radius: %1$s%5$s %2$s%5$s %3$s%5$s %4$s%5$s;', 
            esc_attr($radius_top),    
            esc_attr($radius_right),    
            esc_attr($radius_bottom),    
            esc_attr($radius_left),
            esc_attr($radius_unit),
        );
        
        $affiliate_border = ($is_advance_option_enabled && $want_border) ? sprintf(
            'border: %1$spx %2$s %3$s; %4$s', 
            esc_attr($border_size), 
            esc_attr($border_style), 
            esc_attr($border_color),
            $radius_css
            ) : '';
        // Advanced options for affliate disclosure styles
        
        $post_saved_disclosure_text = get_post_meta( $ID, 'betterlinks_enable_affiliate_link_disclosure_text' );
        // $post_saved_disclosure_text = json_decode(html_entity_decode($post_saved_disclosure_text[0]), true);
        
        if( is_array($post_saved_disclosure_text) && count($post_saved_disclosure_text) > 0 ) {
            $post_saved_disclosure_text = json_decode( $post_saved_disclosure_text[0], true );
            
            $affiliate_disclosure_text = isset( $post_saved_disclosure_text['affiliate_disclosure_text'] ) ?  wp_kses_post($post_saved_disclosure_text['affiliate_disclosure_text']) : '';
            $affiliate_link_position = isset( $post_saved_disclosure_text['affiliate_link_position'] ) ? sanitize_text_field( $post_saved_disclosure_text['affiliate_link_position'] ) : '';
        }
        
        $affiliate_disclosure = sprintf(
            '<div class="ql-snow">
            <div class="betterlinks_affiliate_disclosure_post ql-editor">
                %1$s 
            </div>
            </div>
            <style>
                .betterlinks_affiliate_disclosure_post{
                    line-height:1em;
                    white-space: unset;
                    %2$s
                    %3$s
                    %4$s
                    %5$s
                    %6$s
                    %7$s
                    %8$s
                    %9$s
                }
                .betterlinks_affiliate_disclosure_post p{
                    padding: 0;
                    margin: 0;
                    font-size: inherit;
                }

                .betterlinks_affiliate_disclosure_post .ql-align-center{
                    text-align: center;
                }
                .betterlinks_affiliate_disclosure_post .ql-align-right{
                    text-align: right;
                }
                .betterlinks_affiliate_disclosure_post .ql-align-left{
                    text-align: left;
                }
            </style>', 
            // $affiliate_disclosure_text, 
            str_replace(' rn ','',$affiliate_disclosure_text), 
            $affiliate_disclosure_css,
            $affiliate_border,
            $font_size,
            $width,
            $padding_top,
            $padding_right,
            $padding_bottom,
            $padding_left,
        );
        
        if( $is_enabled_affiliate_disclosure && is_array($is_this_post_enabled) && in_array( 'true', $is_this_post_enabled ) ) {
            if( $affiliate_link_position == 'top' ) {
                $content = $affiliate_disclosure . $content;
            }else if($affiliate_link_position == 'bottom') {
                $content = $content . $affiliate_disclosure;
            }else if($affiliate_link_position == 'top-bottom') {
                $content = $affiliate_disclosure . $content . $affiliate_disclosure;
            }
        }
        return $content;
    }

}