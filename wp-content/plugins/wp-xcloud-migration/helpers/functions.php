<?php

function findTaskByTaskId($arr = [], $taskIndex, $subArrayKey = 'tasks')
{
    $arr = json_decode(json_encode($arr), true);

    if (empty($arr)) return [];

    foreach ($arr as $i => $elements) {

        foreach ($elements as $key => $element) {

            if ($key == $subArrayKey) {

                foreach ($element as $index => $value) {

                    if ($index == $taskIndex) {

                        return array_merge($arr[$i], ['task_index_id' => $i]);
                    }
                }
            }
        }

    }

    return [
        'stage' => 'Not initialized',
        'tasks' => [
            [
                'Pending',
            ]
        ]
    ];
}

function findArrayValuesByKeys($array = [], $key = 'stage')
{
    $array = json_decode(json_encode($array), true);

    $value = [];

    if (empty($array)) return [];

    foreach ($array as $i => $elements) {
        $value[] = $elements[$key];
    }

    return $value;
}
