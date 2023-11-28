<?php

/**
 * FrontEnd Class
 *
 * @package NotificationX\FrontEnd
 */

namespace NotificationXPro\FrontEnd;

use NotificationX\FrontEnd\FrontEnd as FrontEndFree;
use NotificationX\GetInstance;
use NotificationX\HooksLoader;
use NotificationXPro\Core\Helper;

/**
 * This class is responsible for all Front-End actions.
 */
class FrontEnd extends FrontEndFree {
    /**
     * Assets Path and URL
     */
    const PRO_ASSET_URL  = NOTIFICATIONX_PRO_ASSETS . 'public/';
    const PRO_ASSET_PATH = NOTIFICATIONX_PRO_ASSETS_PATH . 'public/';
    /**
     * Initially Invoked
     * when its initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter('nx_frontend_localize_data', [$this, 'get_localize_data_pro']);
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function init() {
        parent::init();
        add_action('notificationx_scripts', [$this, 'enqueue_scripts_pro']);
        add_action('nx_notification_link', array($this, 'add_utm_control'), 10, 2);
        // @todo Something
        // add_filter('nx_pressbar_link', array($this, 'add_utm_control'), 10, 2);
    }

    public function enqueue_scripts_pro() {
        wp_enqueue_style('notificationx-pro-public', Helper::pro_file('public/css/frontend.css', true), [ 'notificationx-public' ], NOTIFICATIONX_PRO_VERSION, 'all');
    }

    public function get_localize_data_pro($data){
        $data['pro_assets'] = self::PRO_ASSET_URL;
        $data['is_pro'] = true;
        return $data;
    }

    public function add_utm_control($link, $settings) {
        $utm_campaign = !empty($settings['utm_campaign']) ? "utm_campaign={$settings['utm_campaign']}" : '';
        $utm_medium   = !empty($settings['utm_medium']) ? "utm_medium={$settings['utm_medium']}" : '';
        $utm_source   = !empty($settings['utm_source']) ? "utm_source={$settings['utm_source']}" : '';
        $parsed_url   = parse_url($link);
        $query        = isset($parsed_url['query']) ? rtrim($parsed_url['query'], '&') : '';

        $query .=  !empty($query) ? '&' : '';
        if (
            $utm_campaign
        ) {
            $query .= "$utm_campaign&";
        }
        if (
            $utm_medium
        ) {
            $query .= "$utm_medium&";
        }
        if (
            $utm_source
        ) {
            $query .= "$utm_source&";
        }

        $query = !empty($query) ? rtrim($query, '&') : '';
        if (
            $query
        ) {
            $parsed_url['query'] = $query;
        }

        if (
            empty($parsed_url)
        ) {
            return $link;
        }
        $link = $this->unparse_url($parsed_url);
        return $link;
    }

    /**
     * Unparse URL
     * @param array $parsed_url
     * @return string of url
     * @since 1.3.0
     */
    public function unparse_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public function get_sounds() {
        $output = "\n"; // if sounds
        $params = $this->get_notifications_ids(true);
        $params = wp_parse_args($params, [
            'global' => [],
            'active' => [],
        ]);
        $global        = $params['global'];
        $active        = $params['active'];
        $notifications = array_merge($global, $active);

        foreach ($notifications as $key => $post) {
            if(!empty($post['sound']) && $post['sound'] != 'none'){
                $volume = !empty($post['volume']) ? $post['volume'] : 3;
                $url = self::PRO_ASSET_URL . 'sounds/' . $post['sound'] . '.mp3';
                $output .= "
                <audio id='nx-id-{$post['nx_id']}' controls data-volume='$volume'>\n
                    <source src='$url' type='audio/mpeg'>\n
                </audio>\n";
            }
        }
        return $output;
    }
}
