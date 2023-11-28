<?php

namespace xCloud\MigrationAssistant;

namespace xCloud\MigrationAssistant;

use Exception;
use WP_Error;
use WP_REST_Request;

class Rest
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    function register_rest($patth, $callback)
    {
        register_rest_route('xcloud-migration/v1', $patth, [
            'methods' => 'GET',
            'callback' => function (WP_REST_Request $request) use ($callback) {
                return $callback($request);
            },
            'permission_callback' => function () {
                return true;
            }
        ]);
    }

    function register_rest_with_encryption($patth, $callback)
    {
        register_rest_route('xcloud-migration/v1', $patth, [
            'methods' => ['GET', 'POST'],
            'callback' => function (WP_REST_Request $request) use ($callback) {
                $params = @json_decode(base64_decode($request->get_param('payload')), true);

                if (!$params) {
                    $params = [];
                }

                $response = $callback($params);

                if (is_wp_error($response) || (defined('XCLOUD_NO_ENCRYPTION') && XCLOUD_NO_ENCRYPTION)) {
                    return $response;
                }

                return Reponse::withEncryption($response);
            },
            'permission_callback' => function (WP_REST_Request $request) {
                $auth_token = xcloud_migration()->getOption('auth_token');
                return $auth_token && ($request->get_param('auth_token') == $auth_token);
            },
        ]);
    }

    function register_routes()
    {
        $this->register_rest_with_encryption('/abspath', [$this, 'get_abspath']);
        $this->register_rest_with_encryption('/wp_config', [$this, 'get_wp_config']);
        $this->register_rest_with_encryption('/db/tables', [$this, 'get_db_tables']);
        $this->register_rest_with_encryption('/db/table_structure', [$this, 'get_db_table_structure']);
        $this->register_rest_with_encryption('/db/table_data', [$this, 'get_table_data']);
        $this->register_rest_with_encryption('/fs/structure', [$this, 'get_file_system_structure']);
        $this->register_rest_with_encryption('/fs/read', [$this, 'get_file_system_server']);
        $this->register_rest_with_encryption('/receive_processing_statuses', [$this, 'receive_migration_statuses']);
        $this->register_rest('/get_statuses', [$this, 'get_status']);
    }

    function get_abspath($params)
    {
        return [
            'abspath' => ABSPATH,
        ];
    }

    function get_wp_config($params)
    {
        return (new FileSystemMigration)->getConfig();
    }

    function get_db_tables($params)
    {
        return (new DatabaseMigration)->getTables();
    }

    function get_db_table_structure($params)
    {
        $table = isset($params['table']) ? $params['table'] : null;

        if (!$table) {
            return new WP_Error('no_table', 'No table specified');
        }

        return (new DatabaseMigration)->getTableStructure($table);
    }

    function get_table_data($params)
    {
        $table = isset($params['table']) ? $params['table'] : null;
        $row_start = (int) isset($params['row_start']) && $params['row_start'] ? $params['row_start'] : 0;
        $row_inc = (int) isset($params['row_inc']) && $params['row_inc'] ? $params['row_inc'] : 10;

        if (!$table) {
            return new WP_Error('no_table', 'No table specified');
        }

        return (new DatabaseMigration)->getTableData($table, $row_start, $row_inc);
    }

    function get_file_system_structure($params)
    {
        $dir = isset($params['root']) && $params['root'] ? $params['root'] : ABSPATH;

        $response = (new FileSystemMigration)->getStructure($dir);

        return $response;
    }

    function get_file_system_server($params)
    {
        $start = (int) isset($params['start']) ? $params['start'] : 0;
        $file = isset($params['file']) ? $params['file'] : '';
        $files = isset($params['files']) ? $params['files'] : [];

        if ($file) {
            return (new FileSystemMigration)->getFile($file, $start);
        }

        if ($files) {
            return (new FileSystemMigration)->getFiles($files);
        }

        return new WP_Error('no_file', 'No file specified');
    }

    function receive_migration_statuses($params)
    {
        return [
            'data' => $params,
            'saved' => xCloudOption::set('migration', array_merge(
                    $params['statuses'],
                    [
                        'migrating_from' => $params['migrating_from'],
                        'migrating_to' => $params['migrating_to'],
                        'state' => $params['state'],
                        'last_updated' => time()
                    ]
                )
            )
        ];
    }

    function get_status(WP_REST_Request $request)
    {
        return array_merge(
            ['data' => xCloudOption::get('migration')],
            xCloudOption::requiredDataForProgress()
        );
    }
}