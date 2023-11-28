<?php

namespace xCloud\MigrationAssistant;

use Exception;
use WP_Error;

class Reponse
{
    /**
     * Return data with Encryption
     *
     * @param $data
     * @return WP_Error|array
     * @throws Exception
     */
    static function withEncryption($data)
    {
        $encryption_key = xCloudOption::get('settings.encryption_key');

        if (!$encryption_key) {
            return new WP_Error('no_encryption_key_found', 'No Encryption Key Found');
        }

        try {
            $encrypted_data = (new Encrypter($encryption_key))->encrypt($data);
            return ['data' => $encrypted_data];
        } catch (Exception $e) {
            return new WP_Error('encryption_failed', $e->getMessage());
        }
    }
}