<?php

/**
 * IFTTT Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\IFTTT;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\IFTTT\IFTTT as IFTTTFree;
use NotificationXPro\Feature\Maps;

/**
 * IFTTT Extension
 */
class IFTTT extends IFTTTFree {


    public $api_key = '';
    public $ifttt_fields = array();


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->ifttt_fields = array(
            'tag_email'                 => __('Email', 'notificationx-pro'),
            'tag_first_parameter'       => __('First Parameter', 'notificationx-pro'),
            'tag_second_parameter'      => __('Second Parameter', 'notificationx-pro'),
            'tag_third_parameter'       => __('Third Parameter', 'notificationx-pro'),
            'tag_fourth_parameter'      => __('Fourth Parameter', 'notificationx-pro'),
            'tag_fifth_parameter'       => __('Fifth Parameter', 'notificationx-pro'),
            'tag_sixth_parameter'       => __('Sixth Parameter', 'notificationx-pro'),
            'tag_seventh_parameter'     => __('Seventh Parameter', 'notificationx-pro'),
            'tag_eighth_parameter'      => __('Eighth Parameter', 'notificationx-pro'),
            'tag_ninth_parameter'       => __('Ninth Parameter', 'notificationx-pro'),
            'tag_tenth_parameter'       => __('Tenth Parameter', 'notificationx-pro'),
            'tag_eleventh_parameter'    => __('Eleventh Parameter', 'notificationx-pro'),
            'tag_twelfth_parameter'     => __('Twelfth Parameter', 'notificationx-pro'),
            'tag_thirteenth_parameter'  => __('Thirteenth Parameter', 'notificationx-pro'),
            'tag_fourteenth_parameter'  => __('Fourteenth Parameter', 'notificationx-pro'),
            'tag_fifteenth_parameter'   => __('Fifteenth Parameter', 'notificationx-pro'),
        );
        $common_fields = [
            'first_param'         => 'tag_first_parameter',
            'custom_first_param'  => __('Someone', 'notificationx-pro'),
            'second_param'        => __('just subscribed to', 'notificationx-pro'),
            'third_param'         => 'tag_third_parameter',
            'custom_third_param'  => __('Anonymous Title', 'notificationx-pro'),
            'fourth_param'        => 'tag_fourth_parameter',
            'custom_fourth_param' => __('Some time ago', 'notificationx-pro'),
        ];

        // $this->themes = [
        //     'theme-one'   => [
        //         'source' => NOTIFICATIONX_PRO_URL . 'assets/admin/images/themes/mailchimp-theme-two.jpg',
        //         'template' => $common_fields,
        //     ],
        //     'theme-two'   => [
        //         'source' => NOTIFICATIONX_PRO_URL . 'assets/admin/images/themes/mailchimp-theme-one.png',
        //         'template' => $common_fields,
        //     ],
        //     'theme-three' => [
        //         'source' => NOTIFICATIONX_PRO_URL . 'assets/admin/images/themes/mailchimp-theme-three.jpg',
        //         'template' => $common_fields,
        //     ],
        //     'maps_theme'  => [
        //         'source' => NOTIFICATIONX_PRO_URL . 'assets/admin/images/themes/maps-theme-subscribed.png',
        //         'template' => Maps::get_instance()->get_themes(),
        //     ],
        // ];

        $this->templates = [
            'ifttt_template_new' => [
                'first_param'  => $this->ifttt_fields,
                'third_param'  => $this->ifttt_fields,
                'fourth_param' => $this->ifttt_fields,
                '_source'      => $this->id,
            ],
        ];

        add_filter('nx_notification_template', [$this, 'hide_email_sub_options'], 999);

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

        add_action('nx_api_response_success', array($this, 'get_response'));
    }
    public function admin_actions() {
        parent::admin_actions();

    }

    public function init_fields() {
        parent::init_fields();
        // add_filter('nx_link_types', [$this, 'link_types']);
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }


    public function get_response($response) {
        if (isset($response['data']['actionFields'])) {
            $key = $this->id . '_' . $response['id'];
            $notification = $response['data']['actionFields'];
            $notification['entry_id'] = time();
            $notification['timestamp'] = time();
            $nx_id = $notification['notification_id'];

            unset($notification['api_key']);
            unset($notification['notification_id']);
            unset($notification['site_url']);
            $this->update_notification([
                'nx_id'      => $nx_id,
                'source'     => $this->id,
                'entry_key'  => $notification['entry_id'],
                'data'       => $notification,
            ]);
        }
    }

    public function api_integration_settings($options) {
        $options['ifttt_settings'] = array(
            'name'     => 'ifttt_settings',
            'type'     => 'section',
            'label'    => __('IFTTT Settings', 'notificationx-pro'),
            'priority' => 50,
            'rules' => Rules::is('modules.modules_ifttt', true),
            'fields'   => array(
                'ifttt_api_key' => array(
                    'name'     => 'ifttt_api_key',
                    'type'     => 'text',
                    'label'    => __('API Key', 'notificationx-pro'),
                    'default'  => md5(home_url('', 'http')),
                    'priority' => 5,
                    'readOnly' => true,
                )
            ),
        );

        return $options;
    }

    public function notification_image($image_data, $data, $settings) {
        if (NotificationX_Helper::get_type($settings) != 'ifttt') {
            return $image_data;
        }

        $avatar = '';
        $alt_title = isset($data['title']) ? $data['title'] : '';
        $alt_title = empty($alt_title) && isset($data['name']) ? $data['name'] : $alt_title;

        if (isset($data['email'])) {
            $avatar = get_avatar_url($data['email'], array(
                'size' => '100',
            ));
        }

        $image_data['url'] = $avatar;
        $image_data['alt'] = $alt_title;

        return $image_data;
    }

    /**
     * Instructions Content
     * @since 1.1.3
     */
    public function doc() {
        ob_start();
?>
        <div class="instructions-header">
            <i class="dashicons dashicons-info"></i>
            <span class="title"><?php _e('IFTTT setup Instructions', 'notificationx-pro'); ?></span>
            <span class="title"><?php _e('Configure Applet:', 'notificationx-pro'); ?></span>
            <ul class="email_subscription nx-template-keys">
                <li><span>API Key:</span> <strong><?php echo md5(home_url()); ?></strong></li>
                <li><span>Notification Id:</span> <strong><?php echo !empty($_GET['post']) ? $_GET['post'] : ''; ?></strong></li>
                <li><span>Site URL:</span> <strong><?php echo home_url() ?></strong></li>
            </ul>
        </div>
        <div class="instructions">
            <div class="nx-template-keys-wrapper">
                <h3><?php _e('Template Keys', 'notificationx-pro'); ?></h3>
                <ul class="email_subscription nx-template-keys">
                    <?php
                    foreach ($this->ifttt_fields as $key => $value) {
                        echo "<li><span>$value</span></li>"; // : <strong>" . str_replace('tag_', '', $key) . "</strong>
                    }
                    ?>
                </ul>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Adds options and dependency for `Notification Template` field from `Content` tab.
     *
     * @param array $templates `Notification Template` fields.
     * @return array
     */
    public function hide_email_sub_options($templates) {
        $type = $this->get_type();
        if($type){
            foreach ($type->get_templates() as $key => $tmpl) {
                unset($tmpl['_themes']);
                unset($tmpl['_source']);

                foreach ($tmpl as $param => $options) {
                    foreach ($options as $name => $label) {
                        $rule = Rules::is('source', $this->id, true);
                        $templates[$param]['options'][$name] = Rules::logicalRule([$rule], 'and', $templates[$param]['options'][$name]);
                    }
                }
            }
        }


        return $templates;
    }

}
