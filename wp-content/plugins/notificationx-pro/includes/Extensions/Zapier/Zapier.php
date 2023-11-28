<?php

/**
 * Zapier Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Zapier;

use NotificationX\Core\Rules;

/**
 * Common functions for Zapier
 */
trait Zapier {



    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        /**
         * @since 1.1.3
         */
        add_action("nx_api_response_success_{$this->id}", array($this, 'get_response'));
    }

    public function init() {
        parent::init();
        add_filter( 'nx_settings', [ $this, 'save_settings' ] );
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        // settings page
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     *
     * @return void
     */
    public function public_actions() {
        parent::public_actions();

    }

    public function admin_actions() {
        parent::admin_actions();

    }

    public function save_settings( $settings ) {
        if ( isset( $settings['zapier_api_key'] ) ) {
            unset( $settings['zapier_api_key'] );
        }
        return $settings;
    }

    public function api_integration_settings($fields) {
        if(!isset($fields['zapier_settings_section'])){
            $fields['zapier_settings_section'] = [
                'type' => 'section',
                'name' => 'zapier_settings_section',
                'label' => __('Zapier Settings', 'notificationx-pro'),
                'modules' => 'modules_zapier',
                'priority' => 40,
                'rules' => Rules::is('modules.modules_zapier', true),
                'fields' => [
                    'zapier_api_key' => [
                        'name' => 'zapier_api_key',
                        'description'    => sprintf('<a target="_blank" rel="nofollow" href="%3$s">%1$s</a> %2$s',
                                            __('Click here', 'notificationx-pro'),
                                            __('to get invitation.', 'notificationx-pro'),
                                            'https://zapier.com/developer/public-invite/28239/62de3486b323cd5830e27b251183a456/'
                                        ),
                        'type'      => 'text',
                        'label'     => __('API Key', 'notificationx-pro'),
                        'default'    => md5(home_url('', 'http')),
                        'priority'    => 5,
                        'readOnly' => true,
                    ],
                ],
            ];
        }
        return $fields;
    }


    public function get_response($response) {
        // error_log(print_r($response, true));
        if (isset($response['id'])) {
            $display_type           = null;
            $nx_id                  = $response['id'];
            $key                    = $this->id . '_' . $nx_id;

            if (!isset($response['timestamp'])) {
                $response['timestamp']  = time();
            }
            if (isset($response['rest_route'])) {
                unset($response['rest_route']);
            }
            if (isset($response['display_type'])) {
                $display_type = $response['display_type'];
                unset($response['display_type']);
            }

            if ($display_type == 1) {
                $products = $this->extract_data($response['products']);
                unset($response['products']);
                if (!empty($products)) {
                    foreach ($products as $product) {
                        $product_item = array_merge($response, $product);
                        $this->update_notification([
                            'nx_id'      => $nx_id,
                            'source'     => $this->id,
                            'entry_key'  => $key,
                            'data'       => $product_item,
                            'created_at' => date("Y-m-d H:i:s", $response['timestamp']),
                        ]);
                    }
                    return true;
                }
            }

            if ($display_type == 4) {
                if (isset($response['custom_data']) && !empty($response['custom_data'])) {
                    $custom_data = $response['custom_data'];
                    unset($response['custom_data']);
                    $response = array_merge($response, $custom_data);
                }
            }

            $this->update_notification([
                'nx_id'     => $nx_id,
                'source'    => $this->id,
                'entry_key' => $key,
                'data'      => $response,
                'created_at' => date("Y-m-d H:i:s", $response['timestamp']),
            ]);
        }
    }

    protected function extract_data($data) {
        if (empty($data)) {
            return [];
        }

        $new_data = [];
        $i = 0;

        $data = explode("\n", $data);
        foreach ($data as $value) {
            if (empty($value)) {
                $i++;
                continue;
            }
            $inner_array = explode(":", $value);
            if (is_array($inner_array)) {
                if ($inner_array[0] === 'product_id') {
                    $new_data[$i]['product_id'] = trim($inner_array[1]);
                }
                if ($inner_array[0] === 'name') {
                    $new_data[$i]['title'] = trim($inner_array[1]);
                }
            }
            $inner_array = [];
        }

        return $new_data;
    }

    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image']) {
            $avatar = '';
            if (empty($alt_title) && isset($data['plugin_name'])) {
                $image_data['alt'] = $data['plugin_name'];
            }

            if (isset($data['review_from']) && $data['review_from'] == 'twitter' && !isset($data['avatar'])) {
                $avatar = NOTIFICATIONX_PRO_URL . 'assets/images/icons/twitter.png';
            }

            if (isset($data['review_from']) && $data['review_from'] == 'facebook' && !isset($data['avatar'])) {
                $avatar = NOTIFICATIONX_PRO_URL . 'assets/images/icons/facebook.png';
            }

            if (isset($data['avatar']) && !empty($data['avatar'])) {
                $avatar = $data['avatar'];
            }

            $image_data['url'] = $avatar;
        }
        return $image_data;
    }

    /**
     * This method is used for fallback data
     * @since 1.1.2
     */
    public function fallback_data($data, $saved_data, $settings) {
        $data['username'] = __('Someone', 'notificationx-pro');
        $data['plugin_name'] = __('', 'notificationx-pro');
        $data['rating'] = 5;
        return $data;
    }


    public function doc() {
        ob_start();
?>
        <div class="instructions-wrapper">
            <div class="instructions">
                <p><?php
                    // translators: %1$s: Zapier docs link, %2$s: Zapier invitation link.
                    echo sprintf(__('Make sure that you have <a target="_blank" rel="nofollow" href="%1$s">created a Zap</a> with your preferred Application. From Zap Builder, you need to choose <strong>NotificationX</strong> as your Action App. You can get the public invitation link from <a href="%2$s" target="_blank" rel="nofollow">here</a>', 'notificationx-pro'), 'https://zapier.com/learn/getting-started-guide/build-zap-workflow', 'https://zapier.com/developer/public-invite/28239/62de3486b323cd5830e27b251183a456');
                    ?></p>
                <p><?php
                    // translators: %s: Zapier docs link.
                    echo sprintf(__("For further assistance, check out this <a href='%s' rel='nofollow'>Documentation</a>", 'notificationx-pro'), "https://notificationx.com/docs/zapier-notification-alert/");
                    ?>
                </p>
                <div class="nx-template-keys-wrapper">
                    <h3><?php _e('Template Keys', 'notificationx-pro'); ?></h3>
                    <?php echo $this->_doc(); ?>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
