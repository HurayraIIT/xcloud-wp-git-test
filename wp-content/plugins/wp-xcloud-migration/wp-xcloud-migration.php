<?php

/**
 * Plugin Name:       xCloud Migration Assistant
 * Plugin URI:        https://x-cloud.dev
 * Description:       Move your WordPress site into xCloud with a few clicks! (Do not install this in production or any live site)
 * Version:           0.2
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Nasir Nobin
 * Author URI:        https://nobin.me
 */

use xCloud\MigrationAssistant\Encrypter;
use xCloud\MigrationAssistant\ReceiveProcessingStatuses;
use xCloud\MigrationAssistant\Rest;
use xCloud\MigrationAssistant\Settings;
use xCloud\MigrationAssistant\xCloudOption;

/**
 * TODO: This code is not secure, it is just for a prof of cncept. Do not install this in production or any live site.
 *
 * Need to prevent SQL injection.
 * Need to add proper Encryption
 * Need to add proper Authentication
 */
class xCloudMigrationAssistant
{
    const XCLOUD_BYTE_PER_REQUEST = 1025 * 500;

    const ENCRYPTION_CIPHER = 'AES-256-CBC';

    private static $instance = null;

    public $container = [];

    public function __construct()
    {

    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function includeFiles()
    {
        require_once __DIR__.'/src/Encrypter.php';
        require_once __DIR__.'/src/Settings.php';
        require_once __DIR__.'/src/Rest.php';
        require_once __DIR__.'/src/Reponse.php';
        require_once __DIR__.'/src/FileSystemMigration.php';
        require_once __DIR__.'/src/DatabaseMigration.php';
        require_once __DIR__ . '/src/xCloudOption.php';
        require_once __DIR__ . '/helpers/functions.php';
    }

    public function init()
    {
        $this->includeFiles();

        $this->container['settings'] = new Settings();
        $this->container['rest'] = new Rest();
    }

    static function getOption($key)
    {
        return xCloudOption::get('settings.'.$key);
    }

    static function get($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }
}

function xcloud_migration()
{
    return xCloudMigrationAssistant::getInstance();
}

xcloud_migration()->init();