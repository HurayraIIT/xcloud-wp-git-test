<?php

namespace WeDevs\WeDocsPro;

use WeDevs\WeDocsPro\Api\AiIntegrationApi;
use WeDevs\WeDocsPro\Api\MetaApi;
use WeDevs\WeDocsPro\Api\SendMailApi;
use WeDevs\WeDocsPro\Api\UserApi;

/**
 * Api Handler Class.
 */
class Api {

    /**
     * Bind actions.
     */
    public function __construct() {
        new MetaApi();
        new UserApi();
        new SendMailApi();
        new AiIntegrationApi();

        add_filter( 'rest_prepare_docs', array( $this, 'remove_docs_private_label_from_api' ), 10, 2 );
    }

    /**
     * Remove private label from documentation title.
     *
     * @param \WP_REST_Response $data The response object.
     * @param \WP_Post          $post Post object.
     *
     * @return mixed
     */
    public function remove_docs_private_label_from_api( $data, $post ) {
        if ( $post->post_status === 'private' && ! empty( $data->data['title']['rendered'] ) ) {
            $data->data['title']['rendered'] = str_replace( 'Private: ', '', $data->data['title']['rendered'] );
        }

        return $data;
    }
}
