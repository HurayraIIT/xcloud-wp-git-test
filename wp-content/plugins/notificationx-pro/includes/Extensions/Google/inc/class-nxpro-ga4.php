<?php
namespace NotificationXPro\Extensions\Google_Analytics;

use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;

/**
 * Google_Analytics_V4
 * Handles google analytics extension
 * @since 1.4.11
 */
class Google_Analytics_V4{

    /**
     * google api token
     * @var array
     */
    private static $token = array();

    /**
     * meta key for saving google analytics data in the notificationx post
     * @var string
     */
    private static $property_list_url = 'https://content-analyticsadmin.googleapis.com/v1alpha/accountSummaries';
    private static $ga_report = 'https://analyticsdata.googleapis.com/v1beta/{property_id}:runReport';
    private static $ga_report_live = 'https://analyticsdata.googleapis.com/v1beta/{property_id}:runRealtimeReport';

    public function __construct(){

    }
    
    public static function get_property_list($token_info){
        $properties = array();
        try {
            Helper::write_log('Get property list start at - ' . current_time('Y-m-d h:ia'));

            $nx_google_client =  Google_Client::getInstance();
            $client = $nx_google_client->getClient();
            $response = wp_remote_get( self::$property_list_url, array(
                // 'body'    => $data,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token_info['access_token'],
                ),
            ) );

            $responseBody = wp_remote_retrieve_body( $response );
            $result = json_decode( $responseBody );
            if(!empty($result->accountSummaries) && is_array($result->accountSummaries)){
                foreach($result->accountSummaries as $account){
                    if(!empty($account->propertySummaries) && is_array($account->propertySummaries)){
                        foreach($account->propertySummaries as $property){
                            $properties[$property->property] = $account->displayName . ' => ' . $property->displayName;
                        }
                    }
                }
            }
            else{
                Helper::write_log('No properties found.');
                if(!empty($result->error->message)){
                    Google_Analytics::$error_message = $result->error->message;
                    Helper::write_log($result->error->message);
                }
            }
        } catch (\Exception $e) {
            Google_Analytics::$error_message = $e->getMessage();
            Helper::write_log('Error:' . $e->getMessage());
        }

        Helper::write_log('Get property list end at - ' . current_time('Y-m-d h:ia'));
        return $properties;
    }

    public static function get_view($report_type, $report_args){
        $token_info = self::get_token();
        try {
            Helper::write_log('Get views start at - ' . current_time('Y-m-d h:ia'));

            if ($report_type == 'siteview') {
                $request_uri = str_replace('{property_id}', $report_args['view_id'], self::$ga_report);
                $request_body = [
                    "dateRanges" => [
                        [
                            "startDate" => $report_args['date_range']['start'],
                            "endDate" => $report_args['date_range']['end']
                        ]
                    ],
                    "metrics" => [
                        [
                            "name" => "totalUsers"
                        ]
                    ],
                ];

            }elseif( $report_type == 'current_page_view' ) {
                $request_uri = str_replace('{property_id}', $report_args['view_id'], self::$ga_report);
                $request_body = [
                    "dateRanges" => [
                        [
                            "startDate" => $report_args['date_range']['start'],
                            "endDate"   => $report_args['date_range']['end']
                        ]
                    ],
                    "dimensions" => [
                        [
                            "name"  => "pagePath",
                        ]
                    ],
                    "metrics" => [
                        [
                            "name" => "activeUsers"
                        ]
                    ],
                ];
                if( !empty( $report_args['pages'] ) ) {
                    $request_body["dimensionFilter"] = [
                        "filter"            => [
                            "fieldName"     => "pagePath",
                            "stringFilter"  => [
                                "value"     => $report_args['pages'],
                                "matchType" => "EXACT",
                            ],
                        ],
                    ];
                }
            }else{
                $request_uri = str_replace('{property_id}', $report_args['view_id'], self::$ga_report_live);
                $request_body = [
                    "metrics" => [
                        [
                            "name" => "activeUsers"
                        ]
                    ],
                ];
            }

            $response = wp_remote_post( $request_uri, array(
                'body'    => json_encode($request_body),
                'headers' => [
                    'Authorization' => 'Bearer ' . $token_info['access_token'],
                    'Content-Type' => 'application/json; charset=utf-8'
                ],
            ));

            $responseBody = wp_remote_retrieve_body( $response );
            $result = json_decode( $responseBody );
            if(!empty($result->rows[0]->metricValues[0]->value)){
                if( $report_type == 'current_page_view' ) {
                    $page_view = [];
                    foreach ( $result->rows as $row ) {
                        $page_url           = !empty( $row->dimensionValues[0]->value ) ? $row->dimensionValues[0]->value : '';
                        $page_view_count    = !empty( $row->metricValues[0]->value ) ? $row->metricValues[0]->value : '';
                        
                        $page_view[] = [
                            'page_url'          => $page_url,
                            'page_view_count'   => $page_view_count,
                        ];
                    }
                    return array(
                        $report_type => $page_view,
                    );
                }else{
                    return array(
                        $report_type => $result->rows[0]->metricValues[0]->value
                    );
                }
            }
            else{
                Helper::write_log('No properties found.' . print_r($result ? $result : $response, true));
                if(!empty($result->error->message)){
                    Google_Analytics::$error_message = $result->error->message;
                    Helper::write_log($result->error->message);
                }
            }
        } catch (\Exception $e) {
            Google_Analytics::$error_message = $e->getMessage();
            Helper::write_log('Error:' . $e->getMessage());
        }

        Helper::write_log('Get views end at - ' . current_time('Y-m-d h:ia') . print_r($response, true));
        return false;
    }

    /**
     * @return array
     */
    private static function get_token()
    {
        if(!empty(self::$token)){
            return self::$token;
        }
        $options = Settings::get_instance()->get("settings.nx_pa_settings");
        if(!empty($options) && !empty($options['token_info'])){
            self::$token = $options['token_info'];
        }
        return self::$token;
    }
}