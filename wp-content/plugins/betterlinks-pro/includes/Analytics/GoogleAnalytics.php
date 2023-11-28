<?php
namespace BetterLinksPro\Analytics;

class GoogleAnalytics {
        //Parse the GA Cookie
        public function gaParseCookie() {
            if (isset($_COOKIE['_ga'])) {
                list($version, $domainDepth, $cid1, $cid2) = explode('.', $_COOKIE["_ga"], 4);
                $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1 . '.' . $cid2);
                $cid = $contents['cid'];
            } else {
                $cid = $this->gaGenerateUUID();
            }
            return $cid;
        }

        //Generate UUID
        //Special thanks to stumiller.me for this formula.
        public function gaGenerateUUID() {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }

        //Send Data to Google Analytics UA
        //https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide#event
        public function legacyGaSendData($data) {
            $getString = 'https://ssl.google-analytics.com/collect';
            $getString .= '?payload_data&';
            $getString .= http_build_query($data);
            $result = wp_remote_get($getString);
            return $result;
        }

        // Send Data to Google Analytics 4
        // https://firebase.google.com/codelabs/firebase_mp#4
        // https://ga-dev-tools.google/ga4/event-builder/
        public function ga4SendData($data) {
            $api_secret     = !empty( $data['ga4_api_secret'] ) ? $data['ga4_api_secret'] : false;
            $measurement_id = !empty( $data['tid'] ) ? $data['tid']  : '';
            
            if( ! $api_secret || empty( $measurement_id ) ) return;
            
            $cid        = !empty( $data['cid'] ) ? $data['cid'] : '';
            $hostname   = !empty( $data['dh'] ) ? $data['dh']  : '';
            $page       = !empty( $data['dp'] ) ? $data['dp']  : '';
            $title      = !empty( $data['dt'] ) ? $data['dt'] : '';
            
            $endpoint   = 'https://www.google-analytics.com/mp/collect?api_secret='. $api_secret .'&measurement_id='. $measurement_id;
            

            $data = array(
                'client_id' => $cid,
                "events"    => array(
                  [
                    "name"      => 'pageview',
                    "params"    => array(
                        'page_title'    => $title,
                        'page_location' => $hostname.$page,
                    )
                  ]
                )
            );
            
            $result = wp_remote_post( $endpoint, [
                'body'      => wp_json_encode($data),
                'headers'   => [
                    'Content-Type' => 'application/json',
                ], 
            ]);

            return $result;
        }
        
        public function gaSendData($data) {
            if( substr( $data['tid'], 0, 1 ) == 'U' ){
                $this->legacyGaSendData($data);
            } else if( substr( $data['tid'], 0, 1 ) == 'G' ){
                $this->ga4SendData($data);
            }
            return;
        }

        //Send Pageview Function for Server-Side Google Analytics
        public function ga_send_pageview($hostname=null, $page=null, $title=null, $tid, $ga4_api_secret) {
            if(empty($tid)) return;
            $cid = $this->gaParseCookie();
            $data = array(
                'v'     => 1,
                'tid'   => $tid, //@TODO: Change this to your Google Analytics Tracking/Measurement ID.
                'ga4_api_secret' => $ga4_api_secret,  //@TODO: Change this to your Google Analytics 4 API Secret.
                'cid'   => $cid,
                't'     => 'pageview',
                'dh'    => $hostname, //Document Hostname "gearside.com"
                'dp'    => $page, //Page "/something"
                'dt'    => $title //Title
            );

            $this->gaSendData($data);
        }
}