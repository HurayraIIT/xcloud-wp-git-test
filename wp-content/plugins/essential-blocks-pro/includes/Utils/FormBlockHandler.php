<?php
namespace EssentialBlocks\Pro\Utils;

use EssentialBlocks\Pro\Utils\Helper;

class FormBlockHandler {
    /**
     * Holds the plugin instance.
     *
     * @since 1.2.0
     * @access private
     * @static
     *
     * @var static
     */
    protected static $instances = [];
    /**
     * Sets up a single instance of the plugin.
     *
     * @since 1.0.0
     * @access public
     * @var mixed $args
     *
     * @static
     *
     * @return static An instance of the class.
     */
    public static function get_instance( ...$args ) {
        if ( ! isset( self::$instances[static::class] ) ) {
            self::$instances[static::class] = ! empty( $args ) ? new static( ...$args ) : new static;
        }

        return self::$instances[static::class];
    }

    public function __construct() {
        add_action( 'eb_form_data_validation', [$this, 'validation_rules'], 10, 4 );
        add_filter( 'eb_form_confirmation_div_attr', [$this, 'confirmation_div_attr'], 10, 2 );
        add_action( 'eb_form_submit_after_email', [$this, 'form_submit_actions'], 10, 4 );
        add_action( 'eb_form_block_integrations', [$this, 'form_integrations'], 10, 3 );
    }

    public function validation_rules( $validation, $datarules, $data, $index ) {
        foreach ( $datarules as $rulesType => $rulesData ) {
            switch ( $rulesType ):
        case 'recaptcha':
            $message = "reCAPTCHA verification failed";
            if ( isset( $rulesData->message ) && is_string( $rulesData->message ) ) {
                    $message = $rulesData->message;
            }
            $isValid = $this->isValidRecaptcha( Helper::get_recaptcha_settings( 'secretKey' ), $data );
            if ( ! is_string( $data ) ) {
                $validation['success']      = false;
                $validation['data'][$index] = $message;
                break 2;
            }
            break;
        default:
            $validation['success'] = false;
            endswitch;
        }
        return $validation;
    }

    public function isValidRecaptcha( $secretkey, $response ) {
        try {
            $url  = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret'   => $secretkey,
                'response' => $response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query( $data )
                ]
            ];

            $context = stream_context_create( $options );
            $result  = file_get_contents( $url, false, $context );
            return json_decode( $result )->success;
        } catch ( Exception $e ) {
            return null;
        }
    }

    public function confirmation_div_attr( $attrList, $attributes ) {
        if ( isset( $attributes['confirmationType'] ) && $attributes['confirmationType'] === 'redirect' ) {
            return array_merge( $attrList, [
                'data-redirect-url' => esc_url( $attributes['redirectUrl'] ?? '' )
            ] );
        }
        return $attrList;
    }

    public function form_submit_actions( $id, $fields, $email_success, $notification ) {
        if ( empty( $id ) ) {
            return;
        }
        if ( $notification === 'email' ) {
            return;
        }

        $_response = [];
        if ( is_array( $fields ) && count( $fields ) > 0 ) {
            $_response = $fields;
        }

        global $wpdb;
        $table_name = ESSENTIAL_BLOCKS_FORM_ENTRIES_TABLE;

        $wpdb->insert(
            $table_name,
            [
                'block_id'     => $id,
                'response'     => serialize( $_response ),
                'email_status' => $email_success,
                'created_at'   => current_time( 'mysql' )
            ]
        );
    }

    /**
     * Form Integration Hook
     * @param string $id
     * @param array $fields
     * @param array $integrations
     * @return mixed
     */
    public function form_integrations( $id, $fields, $integrations ) {
        if ( empty( $id ) ) {
            return;
        }
        //Mailchimp
        if ( isset( $integrations['mailchimp'] ) && is_object( $integrations['mailchimp'] ) ) {
            $mailchimp = $integrations['mailchimp'];
            if ( isset( $mailchimp->listId ) && strlen( $mailchimp->listId ) > 0 ) {
                $list_id = $mailchimp->listId;

                if ( ! isset( $fields['email'] ) ) {
                    return;
                }

                $api_key = Helper::get_mailchimp_api();

                $email = $fields['email'];

                $other_fields = [];
                if ( isset( $fields['first-name'] ) ) {
                    $other_fields['FNAME'] = $fields['first-name'];
                }
                if ( isset( $fields['last-name'] ) ) {
                    $other_fields['LNAME'] = $fields['last-name'];
                }

                $body_params = [
                    'email_address' => $email,
                    'status'        => 'subscribed',
                    'merge_fields'  => $other_fields
                ];
                $this->add_mailchimp_user( $api_key, $list_id, $email, $body_params );
            } else {
                return;
            }
        }
    }

    /**
     * Get Mailchimp list
     * @param string $api_key
     * @param string $list_id
     * @param string $email
     * @param array $body_params
     * @return void
     */
    public static function add_mailchimp_user( $api_key, $list_id, $email, $body_params ) {
        if ( empty( $api_key ) || empty( $list_id ) ) {
            exit();
        }

        if ( preg_match( '/[^a-zA-Z0-9-]/', $api_key ) ) {
            exit();
        }

        $response = wp_remote_post(
            'https://' . substr( $api_key, strpos(
                $api_key,
                '-'
            ) + 1 ) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5( strtolower( $email ) ),
            [
                'method'  => 'PUT',
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key )
                ],
                'body'    => json_encode( $body_params )
            ]
        );

        if ( ! is_wp_error( $response ) ) {
            $response = json_decode( wp_remote_retrieve_body( $response ) );

            if ( ! empty( $response ) ) {
                if ( $response->status == 'subscribed' ) {
                    return;
                } else {
                    error_log( 'Form Id: ' . $list_id . ' Mailchimp subscription failed for email: ' . $email );
                }
            }
        }
    }

    /**
     * Get Mailchimp list
     * @param string $api_key
     * @return array
     */
    public static function get_mailchimp_lists( $api_key ) {
        $lists = [];

        if ( empty( $api_key ) ) {
            return $lists;
        }

        $response = wp_remote_get( 'https://' . substr( $api_key,
            strpos( $api_key, '-' ) + 1 ) . '.api.mailchimp.com/3.0/lists/?fields=lists.id,lists.name&count=1000', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key )
            ]
        ] );

        if ( ! is_wp_error( $response ) ) {
            $response = json_decode( wp_remote_retrieve_body( $response ) );

            if ( ! empty( $response ) && ! empty( $response->lists ) ) {
                $lists[''] = __( 'Select One', 'essential-blocks-pro' );

                for ( $i = 0; $i < count( $response->lists ); $i++ ) {
                    $lists[$response->lists[$i]->id] = $response->lists[$i]->name;
                }
            }
        }

        return $lists;
    }
}
