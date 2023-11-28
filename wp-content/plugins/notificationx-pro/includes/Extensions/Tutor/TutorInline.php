<?php

/**
 * Tutor Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Tutor;

use NotificationX\Core\Inline;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Tutor\TutorInline as TutorInlineFree;

/**
 * Tutor Extension
 */
class TutorInline extends TutorInlineFree {

    use _Tutor;
    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        if ( ! class_exists( 'NotificationX\Core\Inline' ) ) {
            return;
        }
        parent::__construct();
        add_filter( 'nx_inline_hook_options', array( $this, 'inline_hook_options' ), 10 );
        add_filter( 'tutor/course/single/entry-box/is_public', array( $this, 'single_entry_box' ), 10, 2 );
        add_filter( 'tutor/course/single/entry-box/fully_booked', array( $this, 'single_entry_box' ), 10, 2 );
        add_filter( 'tutor/course/single/entry-box/purchasable', array( $this, 'single_entry_box' ), 10, 2 );
        add_filter( 'tutor/course/single/entry-box/free', array( $this, 'single_entry_box' ), 10, 2 );
        add_filter( 'tutor_course/single/entry/after', array( $this, 'single_entry_after' ), 10, 2 );
        add_action( 'tutor_course/loop/after_title', array( $this, 'tutor_course_hooks' ), 10, 2 );
        add_filter( 'nx_content_heading_preview_errors', array( $this, 'preview_errors' ) );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function public_actions() {
        parent::public_actions();
        add_filter( 'nx_preview_url', array( $this, 'preview_url' ) );
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function inline_hook_options( $_options ) {
        $options = [
            'tutor_course/loop/after_title'              => __( 'Archive Page', 'notificationx-pro' ),
            'tutor/course/single/entry-box/free'         => __( 'Single Page Free', 'notificationx-pro' ),
            'tutor/course/single/entry-box/is_public'    => __( 'Single Page Public', 'notificationx-pro' ),
            'tutor/course/single/entry-box/fully_booked' => __( 'Single Page Fully Booked', 'notificationx-pro' ),
            'tutor/course/single/entry-box/purchasable'  => __( 'Single Page Purchasable', 'notificationx-pro' ),
            'tutor_course/single/entry/after'            => __( 'Single Page', 'notificationx-pro' ),
        ];
        $_options = GlobalFields::get_instance()->normalize_fields( $options, 'source', $this->id, $_options );
        return $_options;
    }

    /**
     * This method is responsible for output inline notification
     */
    public function show_inline_notification( $args = [] ) {
        return $this->tutor_course_hooks( '', $args['product_id'] ,[
            'current_action' => 'from_inline',
        ]);
    }

    /**
     * This method is responsible for checking woocommerce inline notification for grouth alert
     */
    public function check_tutor_inline( $args = [] ) {
        return ( isset( $args['current_action'] ) && $args['current_action'] == 'from_inline' ) ? true : false;
    }

    /**
     * This method is responsible for output the shortcode.
     */
    public function tutor_course_hooks($return = false,$download_id = '',$args = []) {

        if( !empty( $args ) && !empty( $args['product_id'] ) && empty( $download_id ) && get_post_type( get_the_ID() ) == 'courses' ) {
            $download_id = $args['product_id'];
        }else{
            $download_id = get_the_ID();
        }
        do_action( 'nx_ignore_analytics' );
        $result         = Inline::get_instance()->get_notifications_data( $this->id, $download_id );
        $current_action = current_action();
        $output         = '';
        if ( ! empty( $result['shortcode'] ) ) {
            foreach ( $result['shortcode'] as $key => $value ) {
                $entries  = $value['entries'];
                $entries  = array_values( $entries );
                $settings = $value['post'];
                if( !$this->check_tutor_inline( $args ) ) {
                    if ( empty($settings['is_preview']) && (empty( $settings['inline_location'] ) || ! in_array( $current_action, $settings['inline_location'] )) ) {
                        continue;
                    }
                }
                
                $template = Inline::get_instance()->get_template( $settings );
                foreach ( $entries as $key => $entry ) {
                    if ( empty( $entry['product_id'] ) || $entry['product_id'] != $download_id ) {
                        continue;
                    }

                    $_template = $template;
                    foreach ( $entry as $key => $val ) {
                        if ( ! is_array( $val ) ) {
                            $_template = str_replace( "{{{$key}}}", $val, $_template );
                        }
                    }
                    $output .= "<div class='{$settings['themes']}' style='margin-bottom: 1rem'>$_template</div>";
                    break;
                }
                
            }
            // $this->shortcode_nx_ids[] = $atts['id'];
            if(!empty($output)){
                // Remove inline notification margin for woocommerce inline notification
                $rm_inline_margin  = "";
                if( $this->check_tutor_inline($args) ) {
                    $rm_inline_margin = "
                        .notificationx-woo-shortcode-inline-wrapper .{$settings['themes']} {
                            margin-bottom: 0 !important;
                        }
                    ";
                }
                $output = "
                <style>
                .notificationx-woo-shortcode-inline-wrapper p{
                    margin-bottom: 0;
                }
                {$rm_inline_margin}
                </style>
                <div id='notificationx-woo-shortcode-inline-{$download_id}' class='notificationx-woo-shortcode-inline-wrapper'>$output</div>";
                if( !$this->check_tutor_inline( $args ) ) {
                    echo $output;
                }else{
                    return $output;
                }

            }
        }
    }

    public function single_entry_box($return, $id){
        $return .= $this->tutor_course_hooks(true);
        return $return;
    }

    public function single_entry_after($id){
        $this->tutor_course_hooks();
    }

    public function preview_errors($errors){
        $errors[$this->id] = __("Create a course to get the preview.", 'notificationx-pro');
        return $errors;
    }

    public function preview_url($urls){
        $args = array(
            'fields'    => 'ids',
            'post_type' => 'courses',
            'limit'     => 1,
            'orderby'   => 'date',
            'order'     => 'DESC',
        );

        $downloads  = get_posts( $args );
        $product_id = end($downloads);
        if(!empty($product_id)){
            $urls[$this->id] = get_permalink($product_id);
        }

        return $urls;
    }
}
