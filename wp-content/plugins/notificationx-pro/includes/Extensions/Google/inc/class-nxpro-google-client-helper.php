<?php
namespace NotificationXPro\Extensions\Google_Analytics;

use NotificationX\Admin\Settings;
use NotificationXPro\Core\Helper;
use NxProGA\Google\Client as GoogleClient;
class Google_Client{

    public $client;
    private static $instance = null;
    // Developer Made
    // public $client_id = '1050489600494-ra373okj96upq19575o33alm8aqlj0i9.apps.googleusercontent.com';
    // public $client_secret = 'W5bNchxKQVoEMYxJ82myiGoU';
    // public $redirect_uri = 'https://dev.notificationx.com/api.php';
    // Official Made
    public $client_id = '928694219401-b9njpjh55ha3vgepku2269kas5kd9a5c.apps.googleusercontent.com';
    public $redirect_uri = 'https://api.notificationx.com/google-analytics/';
    public $client_secret = '';

    public function __construct()
    {
        $this->set_user_credentials();
        $this->client = $this->getClient();

    }

    public function set_user_credentials($reset_client = false)
    {
        $settings = Settings::get_instance()->get('settings');
        if(!empty($settings['ga_redirect_uri']) && !empty($settings['ga_client_id']) && !empty($settings['ga_client_secret'])){
            $this->client_id = $settings['ga_client_id'];
            $this->client_secret = $settings['ga_client_secret'];
            $this->redirect_uri = $settings['ga_redirect_uri'];
            if($reset_client){
                $this->client = false;
                $this->client = $this->getClient();
            }
        }
    }

    public static function getInstance()
    {
        if(self::$instance == null){
            self::$instance = new Google_Client();
        }
        return self::$instance;
    }

    /**
     * Get instance of google client
     * @return NxPro_Google_Client
     */
    public function getClient()
    {
        if(!empty($this->client)){
            return $this->client;
        }
        $googleClient = new GoogleClient();
        $googleClient->setApplicationName ( 'NotificationX' );
        $googleClient->setScopes ( array('https://www.googleapis.com/auth/analytics.readonly') );
        $googleClient->setClientId($this->client_id);
        $googleClient->setClientSecret($this->client_secret);
        $googleClient->setAccessType ( 'offline' );
        $googleClient->setRedirectUri($this->redirect_uri);
        $googleClient->setState(admin_url('admin.php'));
        $googleClient->setApprovalPrompt('force');
        return $googleClient;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getTokenWithAuthCode($code){
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        return $token;
    }
    /**
     * set access token for use
     * if token expired set a new one
     * @param array $token
     * @return mixed
     */
    public function setAccessToken($token){
        try{
            if($this->client->isAccessTokenExpired()){
                $token = $this->_updateToken();
            }
            if($token){
                $this->client->setAccessToken($token);
            }
            return true;
        }catch(\Exception $e){
            return 'Set access token failed. Details: '.$e->getMessage();
        }
    }
    public function revokeToken(){
        return $this->client->revokeToken();
    }
    /**
     * get access token
     * @return mixed
     */

    public function getToken(){
        return $this->client->getAccessToken();
    }

    public function getRedirectUri()
    {
        return $this->client->getRedirectUri();
    }

    /**
     * update token with refresh code
     * @return array|bool
     */
    private function _updateToken(){
        $pa_options = Settings::get_instance()->get("settings.nx_pa_settings");
        $settings = Settings::get_instance()->get('settings');
        if(!empty($pa_options['token_info']['refresh_token'])){
            /**
             * Using user provided client_id nad client_secret.
             */
            if(!empty($settings['ga_redirect_uri']) && !empty($settings['ga_client_id']) && !empty($settings['ga_client_secret'])){
                $new_token = $this->client->refreshToken($pa_options['token_info']['refresh_token']);
                if(!empty($new_token) && is_array($new_token)){
                    $pa_options['token_info'] = $new_token;
                    Settings::get_instance()->set("settings.nx_pa_settings", $pa_options);
                    return $new_token;
                }
            }
            else{
                try {
                    $response = wp_remote_post( $this->redirect_uri, array(
                        'body'    => array(
                            'refresh_token' => $pa_options['token_info']['refresh_token'],
                        ),
                    ));
                    $responseBody = wp_remote_retrieve_body( $response );
                    $new_token = (array) json_decode( $responseBody );
                    if(!empty($new_token) && is_array($new_token)){
                        $pa_options['token_info'] = $new_token;
                        Settings::get_instance()->set("settings.nx_pa_settings", $pa_options);
                        return $new_token;
                    }
                } catch (\Exception $e) {
                    Google_Analytics::$error_message = "Field to regenerate access token. " . $e->getMessage();
                    Helper::write_log(['error' => 'Field to regenerate access token. Details: ' . $e->getMessage()]);
                }
            }
        }
        return false;
    }

}
