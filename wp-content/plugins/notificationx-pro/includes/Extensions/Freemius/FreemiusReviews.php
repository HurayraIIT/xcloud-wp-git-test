<?php

/**
 * Freemius Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Freemius;

use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\Freemius\FreemiusReviews as FreemiusReviewsFree;

/**
 * FreemiusReviews Extension
 */
class FreemiusReviews extends FreemiusReviewsFree {
    use Freemius;
    public $cron_schedule = 'nx_freemius_interval';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->construct();
    }

    public function source_error_message($messages) {
        if ( empty( $this->dev_id ) || empty( $this->dev_public_key ) || empty( $this->dev_secret_key )) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#freemius_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your Dev ID, Public Key, Secret Key from', 'notificationx-pro' ),
                    $url,
                    __( 'settings', 'notificationx-pro' )
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function fallback_data($data, $saved_data, $settings) {

        $data['username']         = __('Someone', 'notificationx-pro');
        $data['plugin_name']      = __('Anonymous', 'notificationx-pro');
        $data['plugin_review']    = __('', 'notificationx-pro');
        $data['plugin_name_text'] = __('try it out', 'notificationx-pro');
        $data['anonymous_title']  = __('Anonymous', 'notificationx-pro');

        return $data;
    }

    public function get_reviews($item_id, $plugin_name) {
        if (!$item_id) {
            return [];
        }
        $reviews = $this->freemius()->Api("/plugins/$item_id/reviews.json");
        return Helper::get_instance()->get_reviews_ready($reviews, $plugin_name);
    }
}
