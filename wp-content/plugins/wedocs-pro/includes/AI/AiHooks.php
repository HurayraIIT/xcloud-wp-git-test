<?php

namespace WeDevs\WeDocsPro\AI;

class AiHooks {

    public function __construct() {
        $ai_integration = new AiIntegration();
        // Check and update AI settings on/off
        add_filter( 'wedocs_settings_data', [ $ai_integration, 'update_ai_settings' ] );
        add_filter( 'wedocs_settings_data_rest_response', [ $ai_integration, 'ai_settings_rest_response' ], 10, 2 );
        add_action( 'wedocs_settings_data_updated', [ $ai_integration, 'sync_data' ] );
        add_action( 'save_post_docs', [ $ai_integration, 'sync_post' ], 10, 2 );
    }
}
