<?php

namespace WeDevs\WeDocsPro\AI;

class AiIntegration {

    protected $base_url = 'https://sass.wedocs.co';
    protected $api_base = '';
    private $password;
    private $domain;

    public function __construct() {
        $this->api_base = $this->base_url . '/api/v1';
        // Check if debug is enabled, then use local api.
        if ( defined( 'WP_AI_DEBUG' ) && WP_AI_DEBUG ) {
            $this->base_url = 'https://dev.sass.wedocs.co';
            $this->api_base = $this->base_url . '/api/v1';
        }

        $settings     = get_option( 'wedocs_settings' );
        $this->domain = str_replace( [ 'http://', 'https://', 'www.', '/' ], '', get_site_url() );
        if ( ! empty( $settings[ 'integrate_ai' ][ 'ai_password' ] ) ) {
            $this->password = $settings[ 'integrate_ai' ][ 'ai_password' ];
        }
    }

    /**
     * Get API base.
     *
     * @since 1.0.0
     *
     * @return mixed|string
     */
    public function get_api_base_url() {
        return $this->base_url;
    }

    /**
     * Update AI settings.
     *
     * @since 1.0.0
     *
     * @param $settings
     *
     * @return array
     */
    public function update_ai_settings( $settings ) {
        if ( $settings[ 'assistant' ][ 'integrate_ai' ] && $settings[ 'assistant' ][ 'integrate_ai' ][ 'ai_enabled' ] === 'on' ) {
            if ( ! $this->is_ai_enabled() ) {
                foreach ( $settings[ 'integrate_ai' ] as $key => $value ) {
                    $settings[ 'integrate_ai' ][ $key ] = 'off';
                }
            }
        }

        return $settings;
    }

    /**
     * AI settings rest response.
     *
     * @since 1.0.0
     *
     * @param $new_data
     * @param $old_data
     *
     * @return array
     */
    public function ai_settings_rest_response( $new_data, $old_data ) {
        if ( $old_data[ 'assistant' ][ 'integrate_ai' ] && 'on' === $old_data[ 'assistant' ][ 'integrate_ai' ][ 'ai_enabled' ] ) {
            $response = $this->check_in_remote();
            if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                $body                                                       = json_decode( wp_remote_retrieve_body( $response ) );
                $new_data[ 'assistant' ][ 'integrate_ai' ][ 'ai_response' ] = $body;
            }
        }

        return $new_data;
    }

    /**
     * Sync data with AI.
     *
     * @since 1.0.0
     *
     * @param $data
     *
     * @return void
     */
    public function sync_data( $data ) {
        if ( $data[ 'assistant' ][ 'integrate_ai' ] && 'on' === $data[ 'assistant' ][ 'integrate_ai' ][ 'ai_enabled' ] ) {
            // Get all posts with the post-type of docs
            $posts = get_posts( [
                'post_type'      => 'docs',
                'posts_per_page' => - 1,
            ] );

            if ( empty( $posts ) ) {
                return;
            }

            $post_data = [];
            foreach ( $posts as $post ) {
                if ( empty( trim( wp_strip_all_tags( $post->post_content ) ) ) ) {
                    continue;
                }
                $post_data[] = [
                    'title'   => $post->post_title,
                    'content' => trim( wp_strip_all_tags( $post->post_content ) ),
                    'url'     => get_permalink( $post->ID ),
                ];
            }
            $response = wp_remote_post( $this->api_base . '/sync-data', [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'body'    => json_encode( [
                    'domain'   => str_replace( [ 'http://', 'https://', 'www.', '/' ], '', get_site_url() ),
                    'password' => $this->password,
                    'data'     => $post_data,
                ] ),
            ] );

            if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
                update_option( 'wedocs_ai', [
                    'assistant' => [
                        'integrate_ai'=> [
                            'initial_data_synced' => true,
                        ],
                    ]
                ] );
            }
        }
    }

    /**
     * Sync post with AI.
     *
     * @since 1.0.0
     *
     * @param $post_id
     * @param $post
     *
     * @return void
     */
    public function sync_post( $post_id, $post ) {
        $data_synced = get_option( 'wedocs_settings' );
        if ( $data_synced[ 'assistant' ][ 'integrate_ai' ] && 'on' === $data_synced[ 'assistant' ][ 'integrate_ai' ][ 'ai_enabled' ] ) {
            update_option( 'wedocs_ai', [
                'data_synced' => [ $post_id ],
            ] );
            if ( empty( trim( wp_strip_all_tags( $post->post_content ) ) ) || '' === trim( wp_strip_all_tags( $post->post_content ) ) ) {
                return;
            }
            $post_data[] = [
                'title'   => $post->post_title,
                'content' => trim( wp_strip_all_tags( $post->post_content ) ),
                'url'     => get_permalink( $post->ID ),
            ];

            $response = wp_remote_post( $this->api_base . '/sync-data', [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'body'    => json_encode( [
                    'domain'   => $this->domain,
                    'password' => $this->password,
                    'data'     => $post_data,
                ] ),
            ] );

            if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
                update_option( 'wedocs_ai', [
                    'data_synced' => true,
                ] );
            }
        }
    }

    /**
     * Check if AI is enabled.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_ai_enabled() {
        $response = $this->check_in_remote();
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        return true;
    }

    /**
     * Check in remote.
     *
     * @since 1.0.0
     *
     * @return array|\WP_Error
     */
    public function check_in_remote() {
        return wp_remote_post( $this->api_base . '/validate-user', [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body'    => wp_json_encode( [
                'password' => $this->password,
                'domain'   => $this->domain,
            ] ),
        ] );
    }

    public function get_current_ai_password() {
        return $this->password;
    }

    /**
     * Get chat.
     *
     * @since 1.0.0
     *
     * @param $prompt
     *
     * @return array|\WP_Error
     */
    public function get_chat( $prompt ) {
        return wp_remote_post( $this->api_base . '/chat', [
            'timeout' => 45,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body'    => wp_json_encode( [
                'domain'   => $this->domain,
                'password' => $this->password,
                'prompt'   => $prompt,
            ] ),
        ] );
    }

    public function reset_ai(){
        update_option( 'wedocs_settings', [
            'integrate_ai' => [
                'ai_enabled' => 'off',
                'ai_password' => '',
            ],
        ] );

        return true;
    }

}
