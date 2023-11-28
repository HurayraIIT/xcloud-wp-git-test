<?php

namespace xCloud\MigrationAssistant;

use WP_Error;
use xCloudMigrationAssistant;

class FileSystemMigration
{
    static function getExcludeRegex()
    {
        static $excludeRegex = null;

        if ($excludeRegex) {
            return $excludeRegex;
        }

        $exclude = [
            '.git',
            '.sql',
            '.sql.gz',
            '.idea',
            '.wpress',
            'wp-content/cache',
            'wp-content/ai1wm-backups',
            'wp-content/uploads/wpvivid_backups',
            'wp-content/wpvivid-backups',
            'wp-content/backups',
            'wp-content/updraft'
        ];

        $exclude = array_map(function ($word) {
            return preg_quote($word, '~');
        }, $exclude);

        $excludeRegex = '~'.implode('|', $exclude).'~';

        return $excludeRegex;
    }

    function getStructure($dir)
    {
        if (!$this->isRootValid($dir)) {
            return new WP_Error('invalid_root', 'Invalid root specified');
        }

        $excludeRegex = self::getExcludeRegex();

        $scanResult = scandir($dir);

        sort($scanResult);

        $dirs = [];
        $files = [];

        foreach ($scanResult as $fileName) {

            if ($fileName === '.' || $fileName === '..') {
                continue;
            }

            $filePath = str_replace(DIRECTORY_SEPARATOR, '/', realpath($dir.'/'.$fileName));
            $isDir = is_dir($filePath);

            if (strlen($excludeRegex) > 0 && preg_match($excludeRegex, $filePath.($isDir ? '/' : ''))) {
                continue;
            }

            if (empty($filePath)) {
                continue;
            }

            if ($isDir) {
                $dirs[] = $filePath;
            } else {
                $files[$filePath] = [
                    'size' => filesize($filePath),
                    'hash' => hash_file('sha256', $filePath),
                ];
            }
        }

        $response = [
            'files' => $files,
            'dirs' => $dirs,
            'unreadable' => [],
            'abspath' => ABSPATH,
        ];

        while (count($response['files']) + count($response['dirs']) < 50 && count($response['dirs']) > 0) {
            $dir = array_shift($response['dirs']);

            $deepResponse = $this->getStructure($dir);

            if (is_wp_error($deepResponse)) {
                $response['unreadable'] = array_merge([$dir], $response['unreadable']);
                continue;
            }

            $response['files'] = array_merge($deepResponse['files'], $response['files']);
            $response['dirs'] = array_merge($deepResponse['dirs'], $response['dirs']);
            $response['unreadable'] = array_merge($deepResponse['unreadable'], $response['unreadable']);
        }

        return $response;
    }

    function getFile($file, $start, $bytePerRequest = null)
    {
        if (is_null($bytePerRequest)) {
            $bytePerRequest = xCloudMigrationAssistant::XCLOUD_BYTE_PER_REQUEST;
        }

        if (!$this->isRootValid($file)) {
            return new WP_Error('invalid_root', 'Invalid root specified');
        }

        if (is_dir($file)) {
            return new WP_Error('invalid_file', 'Invalid file specified');
        }

        $handle = @fopen($file, "r");

        if (!$handle) {
            return new WP_Error('file_not_found', 'File not found');
        }

        $size = filesize($file);

        fseek($handle, $start);

        if (feof($handle) || ($size <= $start)) {
            fclose($handle);
            return new WP_Error('end_of_file', 'End of file');
        }

        $content = fread($handle, $bytePerRequest);

        $response = [
            'content' => base64_encode($content),
            'size' => $size,
            'hash' => hash_file('sha256', $file),
        ];

        if (($size > $bytePerRequest) && !feof($handle)) {
            $response['next'] = $start + $bytePerRequest;
        }

        fclose($handle);

        return $response;
    }

    function getFiles($files)
    {
        $response = [];

        foreach ($files as $file => $start) {

            $bytePerRequest = xCloudMigrationAssistant::XCLOUD_BYTE_PER_REQUEST;

            // Send minimum numbers of bytes for next files
            // If we hit the limit
            // not sure if this is a good idea?
            if (strlen(json_encode($response)) > $bytePerRequest) {
                $bytePerRequest = 10;
            }

            $output = $this->getFile($file, $start, $bytePerRequest);

            if (is_wp_error($output)) {
                $output = [
                    "code" => $output->get_error_code(),
                    "message" => $output->get_error_message(),
                    "data" => [
                        "status" => 500,
                    ]
                ];
            }

            $response[$file] = $output;
        }

        return $response;
    }

    function getConfig()
    {
        $paths = [
            ABSPATH.'wp-config.php',
            dirname(ABSPATH).'/wp-config.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);

                return [
                    'content' => base64_encode($content),
                ];
            }
        }

        return new WP_Error('conifg_not_found', 'WP Conifg file not found');
    }

    /**
     * Root must be under abspath. (for security purposes)
     *
     * @param $dir
     * @return false|int
     */
    private function isRootValid($dir)
    {
        return preg_match('/^'.preg_quote(ABSPATH, '/').'/', $dir);
    }
}