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
use NotificationX\Extensions\Freemius\FreemiusConversions as FreemiusConversionsFree;

/**
 * Freemius Extension
 */
class FreemiusConversions extends FreemiusConversionsFree {
    use Freemius;

    public $cron_schedule = 'nx_freemius_interval';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->construct();
        // add_action( 'nx_settings_saved', [Helper::get_instance(), 'freemius_connect'] );
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

    public function init(){
        parent::init();
    }

    public function fallback_data( $data, $saved_data, $settings ){
        $data['title'] = __('Anonymous', 'notificationx-pro');
        return $data;
    }


    public function get_conversions($item_id, $type = '', $plugin_name = '') {
        if (!$item_id) {
            return [];
        }
        $subscriptions = $this->freemius()->Api("/plugins/$item_id/subscriptions.json");
        $users         = $this->freemius()->Api("/plugins/$item_id/users.json");

        return Helper::get_instance()->get_sales_data($subscriptions, $users, $plugin_name);
    }
}
