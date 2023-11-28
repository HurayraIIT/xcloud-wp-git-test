<?php

namespace xCloud\MigrationAssistant;

use Illuminate\Support\Arr;

class xCloudOption
{
    private static function ArrSet(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    private static function objectGet($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) === '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_object($object) || ! isset($object->{$segment})) {
                return $default;
            }

            $object = $object->{$segment};
        }

        return $object;
    }

    public static function get($key = '')
    {
        return self::objectGet(
            json_decode(
                json_encode(
                    get_option(
                        'xcloud_migration_assistant'
                    )
                )
            ), $key
        );
    }

    public static function set($key, $value)
    {
        if (str_contains($key, 'xcloud_migration_assistant.'))
            $key = str_replace('xcloud_migration_assistant.', '', $key);

        $instance = empty(get_option('xcloud_migration_assistant')) ? [] : get_option('xcloud_migration_assistant');

        $value = self::ArrSet($instance, $key, $value);

        $value = array_merge($instance, $value);

        return update_option('xcloud_migration_assistant', $value);
    }

    public static function tasks()
    {
        return findArrayValuesByKeys(xCloudOption::get('migration.list'), 'tasks');
    }

    public static function lists()
    {
        return findArrayValuesByKeys(xCloudOption::get('migration.list'));
    }

    public static function runningTask()
    {
        return findTaskByTaskId(xCloudOption::get('migration.list'), xCloudOption::get('migration.status'));
    }

    public static function requiredDataForProgress()
    {
        return [
            'request_url' => rest_url('xcloud-migration/v1/get_statuses'),
            'tasks' => xCloudOption::tasks(),
            'lists' => xCloudOption::lists(),
            'percentage' => xCloudOption::get('migration.percentage'),
            'task_index_id' => xCloudOption::get('migration.task_index_id'),
            'running_task' => xCloudOption::runningTask()
        ];
    }

    public static function checkLastUpdateProcessedInTwoHours()
    {
        $last_update = xCloudOption::get('migration.last_update');
        $current_time = time();
        $time_difference = $current_time - $last_update;
        $hours = $time_difference / 3600;
        return $hours > 2;
    }
}