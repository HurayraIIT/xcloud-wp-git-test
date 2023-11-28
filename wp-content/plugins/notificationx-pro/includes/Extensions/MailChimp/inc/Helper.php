<?php

/**
 * This class is a Helper Class for Mailchimp
 *
 * @package NotificationX Pro
 * @subpackage  NotificationX Pro/extensions
 */

namespace NotificationXPro\Extensions\MailChimp;

use NotificationX\Admin\Settings;

class Helper {
    public static function get_members($api_key = '', $list_id = '', $limit = 20) {
        $response = array(
            'error'      => false,
            'members'    => array()
        );

        // Make sure we have an API key.
        if (empty($api_key)) {
            $response['error'] = __('Error: You must provide an API key.', 'notificationx-pro');
        }

        // Make sure we have list id.
        if (empty($list_id)) {
            $response['error'] = __('Error: You must provide a list ID.', 'notificationx-pro');
        }

        if (!$response['error']) {
            try {
                $api = self::mailchimp($api_key);
                $members = $api->get('lists/' . $list_id . '/members', [
                    'count'      => $limit,
                    'sort_dir'   => 'DESC',
                    'sort_field' => 'timestamp_opt',
                    'status'     => 'subscribed',
                ]);

                $response['members'] = $members;
            } catch (\Exception $e) {
                $response['error'] = __('Error: Something wrong ', 'notificationx-pro');
            }
        }

        return $response;
    }

    public static function connect($params) {
        if (isset($params['api_key'])) {
            Settings::get_instance()->set('settings.mailchimp_api_key', $params['api_key']);
            Settings::get_instance()->set('settings.mailchimp_cache_duration', $params['cache_duration']);

            $mailchimp_key = $params['api_key'];
            if (!empty($mailchimp_key)) {
                $connection = self::mailchimp($mailchimp_key);
                if ($connection instanceof API) {
                    $last_status = $connection->getLastError();
                    if (!$last_status) {
                        $lists = self::get_lists($connection);
                        if ($lists) {
                            update_option('nxpro_mailchimp_lists', $lists, 'no');
                            Settings::get_instance()->set('settings.mailchimp_connect', true);
                        }
                        return array(
                            'status' => 'success',
                        );
                    } else {
                        return array(
                            'status' => 'error',
                            'message' => $last_status
                        );
                    }
                } else {
                    return array(
                        'status' => 'error',
                        'message' => 'Something went wrong.'
                    );
                }
            }
        }
        return array(
            'status' => 'error',
            'message' => 'Please insert a valid API key.'
        );
    }

    private static function get_lists($mailchimp) {
        $options = [];
        if (!empty($mailchimp)) {
            $results = $mailchimp->get('lists');
            foreach ($results['lists'] as $list) {
                $options[$list['id']] = $list['name'];
            }
            return $options;
        } else {
            return false;
        }
    }

    private static function mailchimp($api_key) {
        if (empty($api_key)) {
            return false;
        }
        try {
            $mailchimp = new API($api_key);
            $mailchimp->get('ping');
        } catch (\Exception $e) {
            return false;
        }

        return $mailchimp;
    }
}
