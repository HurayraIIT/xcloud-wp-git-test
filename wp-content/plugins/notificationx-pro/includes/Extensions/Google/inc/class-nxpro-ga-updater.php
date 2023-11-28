<?php
namespace NotificationXPro\Extensions\Google_Analytics;

use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;

/**
 * Insert or update new analytics data
 */
class Google_Analytics_Updater{
    /**
     * meta key for saving google analytics data in the notificationx post
     * @var string
     */
    private $meta_key = 'google_analytics_data';

    /**
     * post that need to update
     * @var object
     */
    private $post;

    /**
     * notificationx post settings
     * @var object
     */
    private $notification_settings;

    /**
     * post that need to update
     * @var object
     */
    private $global_settings;

    /**
     * saved data
     * @var object
     */
    private $saved_data;

    /**
     * page url
     * @var object
     */
    private $page_url = '';
    
    /**
     * google api token
     * @var array
     */
    private $token = array();

    /**
     * NotificationXPro_Google_Analytics_Updater constructor.
     * @param $args
     */
    public function __construct($args)
    {
        $this->notification_settings = (object) $args['notification_settings'];
        $this->global_settings = $args['global_settings'];
        $this->saved_data = $args['saved_data'];
        $this->page_url   = $args['page_url'];
        $this->update();
    }
    
    /**
     * Perform Inset or update process
     * @return void
     */
    public function update()
    {
        $nx_id = $this->notification_settings->nx_id;
        Helper::write_log('Update google analytics started at - ' . current_time('Y-m-d h:ia'));
        if (!empty($this->notification_settings) && empty($nx_id)) {
            Helper::write_log('Update google analytics stopped, Notification id empty');
            return;
        }

        $site_url = get_bloginfo('url');
        $site_title = get_bloginfo('title');
        $saved_template_meta = $this->notification_settings->{'notification-template'};

        $report_type = $this->get_report_type($this->notification_settings);

        $_report = $this->get_data($this->notification_settings, $this->global_settings);
        $report = array(
            'id' => 'google_' . $report_type,
            'title' => $site_title,
            'link' => $site_url,
            'last_updated' => current_time('Y-m-d \a\t h:ia'),
            'count' => isset( $saved_template_meta['fifth_param'] ) ? $saved_template_meta['fifth_param'] : 7,
            'type' => $report_type
        );

        if(!empty($_report)){
            $report['views'] = $_report[$report_type];
        }else{
            $report['views'] = 1;
        }

        // Settings::get_instance()->set("settings.{$this->meta_key}", $this->saved_data);
        // PostType::get_instance()->update_meta($nx_id, $this->meta_key, $this->saved_data);

        // removing old notifications.
        Google_Analytics::get_instance()->delete_notification(null, $nx_id);
        if( $report_type == 'current_page_view'  ) {
            $pages = $report['views'];
            unset($report['views']);
            if ( is_array( $pages ) && ! empty( $pages ) ) {
                $entries = [];
                foreach ( $pages as $key => $page ) {
                    $data = array_merge($report, [
                        'link' => $page['page_url'],
                        'views' => $page['page_view_count'],
                    ]);
                    $entries[] = [
                        'nx_id'      => $nx_id,
                        'source'     => Google_Analytics::get_instance()->id,
                        'entry_key'  => $page['page_url'],
                        'data'       => $data,
                    ];
                }
                Google_Analytics::get_instance()->update_notifications( $entries );
            }
        }else{
            Google_Analytics::get_instance()->update_notification([
                'nx_id'      => $nx_id,
                'source'     => Google_Analytics::get_instance()->id,
                'entry_key'  => $report_type,
                'data'       => $report,
            ]);
        }
       
        Helper::write_log('Update google analytics end at - ' . current_time('Y-m-d h:ia'));
    }

    private function get_duration($settings){
        $saved_template_meta = $settings->{'notification-template'};

        $duration = isset( $saved_template_meta['ga_fifth_param'] ) ? $saved_template_meta['ga_fifth_param'] : 7;
        $duration_param = isset( $saved_template_meta['sixth_param'] ) ? $saved_template_meta['sixth_param'] : 'tag_day';
        $template_adv = !empty($settings->template_adv);

        if($template_adv && ($settings->themes == 'page_analytics_pa-theme-two' || $settings->themes == 'page_analytics_pa-theme-one')){
            $advanced_template = $settings->advanced_template;
            $matches           = [];
            preg_match('/{{((day|month|year):(\d+))}}/', $advanced_template, $matches);
            if(isset($matches[2], $matches[3])){
                $duration = $matches[3];
                $duration_param = "tag_" . $matches[2];
            }

        }

        return [$duration, $duration_param];
    }

    /**
     * Get analytics data for specific pages
     * @param array $saved_template_meta
     * @param array $pages
     * @return array|bool|void
     */
    private function get_data($notification_settings, $global_settings)
    {
        $data = null;
        $client = $this->client();
        if (!$client) {
            return;
        }
        list($duration, $duration_param) = $this->get_duration($notification_settings);
        $report_type = $this->get_report_type($notification_settings);
        $report_args = array(
            'view_id' => (string) $global_settings['ga_profile'],
            'pages' => $this->page_url
        );
        if(strpos($report_args['view_id'], 'properties/') === 0){
            $report_args['date_range'] = $this->format_date_range($duration, $duration_param);
            $data = Google_Analytics_V4::get_view($report_type, $report_args);
        }
        else{
            if ($report_type == 'siteview') {
                $report_args['date_range'] = $this->format_date_range($duration, $duration_param);
                $data = $this->get_siteview_data($client, $report_args);
            }
            if ($report_type == 'realtime_siteview') {
                $data = $this->get_realtime_siteview_data($client, $report_args);

            }
        }
        return $data;
    }

    /**
     * Google client instance
     * @return NxPro_Google_Client|bool
     */
    private function client()
    {
        $nx_google_client =  Google_Client::getInstance();
        $client = $nx_google_client->getClient();
        $token = $this->get_token();
        if (null == $client->getAccessToken()) {
            if (empty($token)) {
                Helper::write_log('Token not found in DB. Account connected but refresh token is removed from db. In: ' . __FILE__ . ', at line ' . __LINE__);
                return false;
            }
            $nx_google_client->setAccessToken($token);
        }
        return $client;
    }

    /**
     * Set date range for get page view data
     * @param string|int $duration
     * @param string $duration_param
     * @return array
     */
    private function format_date_range($duration, $duration_param)
    {
        $duration = intval($duration);
        switch ($duration_param) {
            case 'tag_year':
                $duration_param_string = 'years';
                break;
            case 'tag_month':
                $duration_param_string = 'months';
                break;
            default:
                $duration_param_string = 'days';
                $duration = $duration - 1;
                break;
        }
        return array(
            'start' => date('Y-m-d', strtotime('-' . intval($duration) . ' ' . $duration_param_string)),
            'end' => date('Y-m-d')
        );
    }

    /**
     * Get total siteview data
     * @param NxPro_Google_Client $client
     * @param array $report_args
     * @return array|bool
     */
    private function get_siteview_data($client, $report_args)
    {
        try {
            $results = $this->get_siteview_reports($client, (object)$report_args);
            if (!empty($results)) {
                if(!empty($results->reports[0])){
                    $rows = $results->reports[0]->getData()->getRows();
                    if(!empty($rows[0])){
                        $metrics = $rows[0]->getMetrics();
                        if(!empty($metrics[0])){
                            $values = $metrics[0]->getValues();
                            if(!empty($values[0])){
                                return array(
                                    'siteview' => $values[0]
                                );
                            }
                        }
                    }
                }
                return false;
            }else {
                Helper::write_log([
                    'error' => 'No data found for current profile or page',
                    'query_profile' => $report_args['view_id'],
                    'query_pages' => $report_args['pages']
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Helper::write_log('Error in get report. ' . $e->getMessage() . ', at line ' . $e->getLine());
            return false;
        }
    }

    /**
     * Get realtime active users in all site
     * @param NxPro_Google_Client $client
     * @param array $args
     * @return array|bool|void
     */
    private function get_realtime_siteview_data($client, $args)
    {
        try {
            $analytics = new \NxProGA\Google\Service\Analytics($client);
            $realtime_users = $analytics->data_realtime->get('ga:' . $args['view_id'], 'rt:activeUsers');
            $rows = $realtime_users->getRows();
            if (!empty($rows)) {
                $realtime_data = $rows[0];
                if(!empty($realtime_data)){
                    return array(
                        'realtime_siteview' => $realtime_data[0]
                    );
                }
            }
            return false;
        } catch (\Exception $e) {
            Helper::write_log('Get realtime siteview failed. Google error: ' . $e->getMessage() . ' , Trace: ' . $e->getTrace());
            return false;
        }
    }

    /**
     * @param NxPro_Google_Client $client
     * @param object $args
     * @return \NxProGA\Google\Service\AnalyticsReporting\GetReportsResponse
     * @throws Exception
     */
    private function get_siteview_reports($client, $args)
    {
        $metrics = array();
        $analytics = new \NxProGA\Google\Service\AnalyticsReporting($client);
        $view_id = (string)$args->view_id;
        if (empty($view_id)) {
            throw new \Exception('View id is empty, required view id for report');
        }
        if (empty($args->date_range)) {
            throw new \Exception('Date range is required for analytics report');
        }

        // Create the DateRange object.
        $dateRange = new \NxProGA\Google\Service\AnalyticsReporting\DateRange();
        $dateRange->setStartDate($args->date_range['start']);
        $dateRange->setEndDate($args->date_range['end']);
        // Create the metrics object.
        foreach ($this->get_reportable_metrics() as $each) {
            $metric = new \NxProGA\Google\Service\AnalyticsReporting\Metric();
            $metric->setExpression($each['expression']);
            $metric->setAlias($each['alias']);
            $metrics[] = $metric;
        }

        // Create the report object.
        $request = new \NxProGA\Google\Service\AnalyticsReporting\ReportRequest();
        $request->setViewId($view_id);
        $request->setDateRanges($dateRange);
        $request->setMetrics($metrics);

        // get reports.
        $body = new \NxProGA\Google\Service\AnalyticsReporting\GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }

    /**
     * Determine which metrics we want to show in AnalyticsReporting query
     * @return array
     */
    private function get_reportable_metrics() {
        return array(
            array(
                'expression' => 'ga:pageviews',
                'alias' => 'Pageview'
            )
        );
    }

    /**
     * Get report type
     * Remove 'tag_' from first param of saved template meta
     * @param array $template_meta
     * @return mixed
     */
    private function get_report_type($settings)
    {
        $template_adv = !empty($settings->template_adv);
        if($template_adv && ($settings->themes == 'page_analytics_pa-theme-two' || $settings->themes == 'page_analytics_pa-theme-one')){
            $advanced_template = $settings->advanced_template;
            $matches           = [];
            preg_match('/{{(siteview|realtime_siteview)}}/', $advanced_template, $matches);
            if(isset($matches[1])){
                return $matches[1];
            }
        }

        $template_meta = $settings->{'notification-template'};
        $first_param   = !empty( $template_meta['first_param'] ) ? $template_meta['first_param'] : 'tag_siteview';
        $first_param   = str_replace('tag_','', $first_param);
        return $first_param;
    }

    /**
     * @return array
     */
    private function get_token()
    {
        if(!empty($this->token)){
            return $this->token;
        }
        $options = Settings::get_instance()->get("settings.nx_pa_settings");
        if(!empty($options) && !empty($options['token_info'])){
            $this->token = $options['token_info'];
        }
        return $this->token;
    }

    private function unserialize($str)
    {
        return unserialize(stripslashes($str));
    }
}
