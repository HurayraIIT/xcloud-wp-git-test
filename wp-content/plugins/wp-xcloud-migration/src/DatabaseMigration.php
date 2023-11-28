<?php

namespace xCloud\MigrationAssistant;

use Exception;
use WP_Error;

class DatabaseMigration
{
    function getTables()
    {
        global $wpdb;

        $list_tables = $wpdb->get_results("SHOW TABLES");

        $tables = [];

        foreach ($list_tables as $mytable) {
            foreach ($mytable as $t) {

                // skip table if it doesn't start with wp table prefix
                if (strpos($t, $wpdb->prefix) !== 0) {
                    continue;
                }

                $tables[] = $t;
            }
        }

        return [
            'tables' => $tables,
        ];
    }

    function getTableStructure($table)
    {
        global $wpdb;

        if (!$this->tableExists($table)) {
            return new WP_Error('table_not_found', 'Table not found');
        }

        $createTable = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
        $describeTable = $wpdb->get_results("DESCRIBE TABLE $table");
        $rowCount = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        if (false === $createTable || !isset($createTable[0][1])) {
            return new WP_Error('error_show_create_table',
                sprintf(__('Error with SHOW CREATE TABLE for %s.', 'wp-xcloud-migration'), $table)
            );
        }

        if (!isset($createTable[0][1])) {
            return new WP_Error('error_describe_table',
                sprintf(__('Error with DESCRIBE TABLE for %s.', 'wp-xcloud-migration'), $table)
            );
        }

        return [
            'table' => $createTable[0][0],
            'create_table' => $createTable[0][1],
            'describe_table' => isset($describeTable[0]) ? $describeTable[0] : null,
            'row_count' => $rowCount,
        ];
    }

    function getTableData($table, $rowStart, $rowIncrement)
    {
        global $wpdb;

        if (!$this->tableExists($table)) {
            return new WP_Error('table_not_found', 'Table not found');
        }

        $where = '';

        $data = $wpdb->get_results("SELECT * FROM $table $where LIMIT {$rowStart}, {$rowIncrement}", ARRAY_A);

        return [
            'table' => $table,
            'data' => $data,
            'next' => $rowStart + $rowIncrement,
        ];
    }

    /**
     * @param $table
     * @return string|null
     */
    private function tableExists($table)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count(*) FROM information_schema.tables WHERE table_schema='%s' AND table_name='%s' LIMIT 1;",
                DB_NAME,
                $table
            )
        );
    }
}