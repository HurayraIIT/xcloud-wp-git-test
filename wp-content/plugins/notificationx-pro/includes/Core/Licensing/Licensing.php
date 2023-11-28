<?php
namespace NotificationXPro\Core\Licensing;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles license input and validation
 */
class Licensing {
    protected static $instance = null;
	/**
	 * Product Slug
	 * @var string
	 */
	private $product_slug;
	/**
	 * Product Text Domain
	 * @var string
	 */
	private $text_domain;
	/**
	 * Product Name
	 * @var string
	 */
	private $product_name;
	/**
	 * Product ID in Store
	 * @var integer
	 */
	private $item_id;
	/**
	 * Settings Page Slug
	 * @var string
	 */
	private $page_slug;
	/**
	 * Store URL
	 * @var string
	 */
	private $storeURL = 'https://wpdeveloper.com';
	/**
	 * DEV MODE
	 * @var bool
	 */
	private $dev_mode = false;
	/**
	 * Contact Support URL
	 * @var string
	 */
	private $support_url = 'https://wpdeveloper.com/support/new-ticket';
	/**
	 * Initializes the license manager client.
	 */
    protected $args = [
        'slug' => '',
        'page_slug' => '',
        'name' => '',
        'text_domain' => '',
        'item_id' => '',
        'store' => '',
        'dev_mode' => false,
    ];
	public function __construct( $args = [] ) {
		if( empty( $args ) ) {
            throw new \ErrorException('$args can\'t be empty.');
        }
        if( is_array( $args ) ) {
            foreach( $args as $key => $value ) {
                $this->{ $key } = $value;
            }
        }
		/**
		 * Initialize all actions.
		 */
		$this->init();
	}
	/**
	 * Adds actions required for class functionality
	 */
	public function init() {
		if ( is_admin() ) {
			/**
			 * Handles Updates
			 */
			add_action( 'admin_init', [ $this, 'handle_updates'] );
			/**
			 * Register license settings field.
			 */
			add_action( 'admin_init', array( $this, 'register_license_settings' ) );
			// /**
			//  * Activate License
			//  * this will happens when license already activated before and its checking for its status.
			//  */
			// add_action( 'admin_init', array( $this, 'activate_license_when_check' ) );
			/**
			 * Admin Notices
			 */
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}
	/**
	 * Let EDD checks the Update of this plugin.
	 * @return void
	 */
	public function handle_updates(){
		// Disable SSL verification
		add_filter( 'edd_sl_api_request_verify_ssl', '__return_false' );
		// Setup the updater
		new ProPluginUpdater( $this->store, NOTIFICATIONX_PRO_FILE, array(
			'version'      => NOTIFICATIONX_PRO_VERSION,
			'license'      => $this->get_license_key(),
			'item_id'      => $this->item_id,
			'author'       => 'WPDeveloper',
		)
	);
	}
	public function admin_notices(){
		$license_data = $this->get_license_data();
		$status = $this->get_license_status();
		if( $license_data !== false ) {
			if( isset( $license_data->license ) ) {
				$status = $license_data->license;
			}
			if( \is_object( $license_data ) && isset( $license_data->error ) ) {
				$message = $this->get_formatted_message( $status, $license_data );
			}
		}
		$status = $status === 'invalid' ? false : $status;
		if( $status === 'http_error' ) {
			return;
		}

		extract($this->get_formatted_message( $status ));

		switch( $status ) {
			case 'expired':
				$message = sprintf(
					// translators: %1$s: opening tag for WP Developer account link, %2$s: closing tag, %3$s: plugin name (NotificationX Pro)
					__( 'Your license has been expired. Please %1$s renew your license %2$s key to enable updates for %3$s.', 'notificationx-pro' ),
					'<a href="https://store.wpdeveloper.com">', '</a>',
					'<strong>' . esc_html($this->name) . '</strong>'
				);
				break;
			case false:
				$message = sprintf(
					// translators: %1$s: link of license page, %2$s: closing tag, %3$s: plugin name (NotificationX Pro)
					__( 'Please %1$sactivate your license%2$s key to enable updates for %3$s.', 'notificationx-pro' ),
					'<a href="' . admin_url( 'admin.php?page=' . $this->page_slug ) . '">', '</a>',
					'<strong>' . esc_html($this->name) . '</strong>'
				);
				break;
			default:
				if ( ! empty( $message ) ) {
					$message = '<strong>' . esc_html($this->name) . '</strong>: ' . $message;
				}
				break;
		}

		if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['response_message'] ) ) {
			switch( $_GET['sl_activation'] ) {
				case 'false':
					$message = urldecode( $_GET['response_message'] );
					break;
				case 'true':
				default:
					// Try custom message if you want.
					break;
			}
		}

		if( empty( $message ) ) {
			return;
		}

		$output = '<div id="nx-license-notice" class="notice notice-error">';
			$output .= '<p>'. wp_kses_post($message) .'</p>';
		$output .= '</div>';

		echo $output;
	}
	/**
	 * Creates the settings fields needed for the license settings menu.
	 */
	public function register_license_settings() {
		// creates our settings in the options table
		register_setting( $this->page_slug, $this->slug . '-license-key', 'sanitize_license' );
	}

	public function sanitize_license( $new ) {
		$old = get_option( $this->slug . '-license-key' );
		if ( $old && $old != $new ) {
			delete_option( $this->slug . '-license-status' ); // new license has been entered, so must reactivate
		}
		return $new;
	}
	/**
	 * Gets the currently set license key
	 * @return bool|string   The product license key, or false if not set
	 */
	public function get_license_key(){
		$license = get_option( $this->slug . '-license-key' );
		if ( ! $license ) {
			return '';
		}
		return trim( $license );
	}
	/**
	 * Gets the currently set license key in a hidden way
	 * @return string   The product license key
	 */
	public function get_hidden_license_key() {
		$input_string = $this->get_license_key();
		$length = mb_strlen( $input_string ) - 10; // 5 - 5;
		return substr_replace( $input_string, mb_substr( preg_replace( '/\S/', '*', $input_string ), 5, $length ), 5, $length );
	}
	/**
	 * Gets the currently set license data
	 * @param boolean $force_request
	 * @return object|bool
	 */
	public function get_license_data( $force_request = false ) {
		$license_data = get_transient( $this->slug . '-license_data' );
		if ( false === $license_data || $force_request ) {
			$license = $this->get_license_key();
			if( empty( $license ) ) {
				return false;
			}
			$body_args = [
				'edd_action' => 'check_license',
				'license' => $license,
			];
			$license_data = $this->remote_post( $body_args );
			if ( is_wp_error( $license_data ) ) {
				$license_data = new \stdClass();
				$license_data->license = 'valid';
				$license_data->payment_id = 0;
				$license_data->license_limit = 0;
				$license_data->site_count = 0;
				$license_data->activations_left = 0;
				$this->set_license_data( $license_data, 30 * MINUTE_IN_SECONDS );
				$this->set_license_status( $license_data->license );
			} else {
				$this->set_license_data( $license_data );
				$this->set_license_status( $license_data->license );
			}
		}
		return $license_data;
	}
	/**
	 * License Activation
	 * @return void
	 */
	public function activate_license( $request ){
        $empty_license = __( 'License field can not be empty!', 'notificationx-pro' );
        if( $request->has_param('license') ) {
            $license = trim( $request->get_param('license') );
            if( empty( $license ) ) {
                return new \WP_Error('no_license_key', $empty_license );
            }
            /**
             * Get Ready for License Activation Hit
             */
            $api_params = array(
                'edd_action' => 'activate_license',
                'license'    => $license,
            );
            $request = $this->remote_post( $api_params ); // Hit it.
            /**
             * If its an error
             */
            if( is_wp_error( $request ) ) {
                $message = $request->get_error_message();
                $response_code = $request->get_error_code();
                return new \WP_Error( $response_code, $message );
            }
            /**
             * Get Formatted Message
             * if anything goes wrong.
             */
            $formatted_message = $this->get_formatted_message( null, $request );
            if( !empty( $formatted_message['code'] ) && $formatted_message['code'] !== 'valid') {
                return new \WP_Error( $formatted_message['code'], $formatted_message['message'] );
            }

            $this->set_license_key( $license );
            $this->set_license_data( $request );
            $this->set_license_status( $request->license );
            return array_merge( (array) $request, [ 'license_key' => $license, 'hidden_license_key' => $this->get_hidden_license_key() ]);
        }
        return new \WP_Error('no_license_key', $empty_license );
	}
	public function deactivate_license( $request ){
        $empty_license = __( 'License field can not be empty!', 'notificationx-pro' );
        $license = $this->get_license_key();
        if( empty( $license ) ) {
            return new \WP_Error('no_license_key', $empty_license );
        }
        /**
         * Get Ready for License Activation Hit
         */
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $license,
        );
        $request = $this->remote_post( $api_params ); // Hit it.
        /**
         * If its an error
         */
        if( is_wp_error( $request ) ) {
            $message = $request->get_error_message();
            $response_code = $request->get_error_code();
            return new \WP_Error( $response_code, $message );
        }
        /**
         * Get Formatted Message
         * if anything goes wrong.
         */
        $formatted_message = $this->get_formatted_message( null, $request );
        if( !empty( $formatted_message['code'] ) && $formatted_message['code'] !== 'valid') {
            return new \WP_Error( $formatted_message['code'], $formatted_message['message'] );
        }
        if( $request->license == 'deactivated' ) {
			delete_option( $this->slug . '-license-status' );
			delete_option( $this->slug . '-license-key' );
			$transient = get_transient( $this->slug . '-license_data' );
			if( $transient !== false ) {
				$transient = delete_transient( $this->slug . '-license_data');
			}
		} else {
			return new \WP_HTTP_Response([
				'text'  => __("You're license deactivation is failed. Try again later.", 'notificationx-pro')
			], 500);
		}
        return $request;
	}
	/**
	 * Updates the license key option
	 *
	 * @return bool|string   The product license key, or false if not set
	 */
	public function set_license_key( $license_key ) {
		return update_option( $this->slug . '-license-key', $license_key, 'no' );
	}
	/**
	 * Updates the license status option
	 *
	 * @return bool|string   The product license key, or false if not set
	 */
	public function set_license_status( $license_status ) {
		return update_option( $this->slug . '-license-status', $license_status, 'no' );
	}
	/**
	 * Get the license status
	 * @return string|bool
	 */
	public function get_license_status() {
		return get_option( $this->slug . '-license-status' );
	}
    /**
     * Set License Data in a transient
     *
     * @param object $license_data
     * @param int $expiration
     * @return void
     */
	public function set_license_data( $license_data, $expiration = null ) {
		if ( null === $expiration ) {
			$expiration = 12 * HOUR_IN_SECONDS;
		}
		set_transient( $this->slug . '-license_data', $license_data, $expiration );
	}
	/**
	 * Its a helper function for HTTP remote post.
	 * @param array $body_args
	 * @return \stdClass|\WP_Error
	 */
	private function remote_post( &$body_args = [] ) {
		$api_params = wp_parse_args(
			$body_args,
			[
				'item_id' => urlencode( $this->item_id ),
				'url'     => home_url(),
			]
		);
		// HIT IT
		$response = wp_remote_post( $this->store, [
			'sslverify' => false,
			'timeout' => 40,
			'body' => $api_params,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $response_code ) {
			return new \WP_Error( $response_code, __( 'HTTP Error', 'notificationx-pro' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $data ) || ! is_object( $data ) ) {
			return new \WP_Error( 'no_json', __( 'An error occurred, please try again', 'notificationx-pro' ) );
		}

		return $data;
	}
	/**
	 * Its a helper function for getting error message formatted.
	 * @param object $response
	 * @return string[]
	 */
	private function get_formatted_message( $status = null, &$response = null ){
		if ( ( isset( $response->success ) && false === boolval( $response->success ) ) || ! is_null( $status ) ) {
            $status_code = is_null( $status  ) ? $response->error : $status;
			switch( $status_code ) {
				case 'expired' :
					if( ! is_null( $response ) ) {
						$message = sprintf(
							// translators: %s: expiry date of the license.
							__( 'Your license key expired on %s.', 'notificationx-pro' ),
							date_i18n( get_option( 'date_format' ), strtotime( $response->expires, current_time( 'timestamp' ) ) )
						);
					}
					break;
				case 'revoked' :
					$message = __( 'Your license key has been disabled.', 'notificationx-pro' );
					break;
				case 'missing' :
					$message = __( 'Invalid license.', 'notificationx-pro' );
					break;
				case 'invalid' :
				case 'site_inactive' :
				case 'inactive' :
					$message = __( 'Your license is not active for this website.', 'notificationx-pro' );
					break;
				case 'item_name_mismatch' :
					$message = sprintf( '%s %s.', __( 'This appears to be an invalid license key for', 'notificationx-pro' ), $this->product_name );
					break;
				case 'no_activations_left':
					$message = __( 'Your license key has reached its activation limit.', 'notificationx-pro' );
					break;
				case 'disabled':
					$message = sprintf(
						// translators: %1$s: plugin name (NotificationX), %2$s: opening of a tag, %3$s: closing of a tag.
						__( 'Your license key has been disabled, Please contact the %1$s %2$sSupport Team%3$s.', 'notificationx-pro' ),
						"<strong>$this->product_name</strong>",
						"<a href=". esc_url( $this->support_url ) .">", "</a>"
					);
					break;
				case 'valid':
					$message = '';
					break;
				default :
					$message = __( 'An error occurred, please try again.', 'notificationx-pro' );
					break;
			}
		}

		return [
			'code'    => isset( $status_code ) ? $status_code : '',
			'message' => isset( $message ) ? $message : '',
		];
	}

    /**
     * Get the instance of called class.
     *
     * @return Licensing
     */
    public static function get_instance($args = null){
        if ( is_null( static::$instance ) || ! static::$instance instanceof self ) {
            $class = __CLASS__;
            if(!empty($args)){
                static::$instance = new $class($args);
            }
            else{
                static::$instance = new $class;
            }
        }
        return static::$instance;
    }
}