<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Core;

use NotificationX\Core\Database as DatabaseFree;
use NotificationX\GetInstance;

/**
 * Database Class
 */
class Database extends DatabaseFree {
    protected $wpdb;
    public static $table_maps;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        self::$table_maps      = $this->wpdb->prefix . 'nx_maps';

    }


    public function Create_DB() {
        parent::Create_DB();
        $charset_collate  = $this->wpdb->get_charset_collate();
        $table_maps       = self::$table_maps;

        $sql = "CREATE TABLE {$table_maps} (
            map_id bigint(20) unsigned NOT NULL auto_increment,
            ip varchar(55) default NULL,
            lat varchar(55) default 0,
            lon varchar(55) default 0,
            data longtext,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (map_id),
            KEY ip (ip)
        ) $charset_collate ;";
        $stats_db = dbDelta($sql);
    }


    public function insert_map($map_data){
        $table_name = self::$table_maps;
        if(!empty($map_data['data'])){
            $map_data['data'] = maybe_serialize($map_data['data']);
        }
        return $this->wpdb->insert($table_name, $map_data);
    }

    public function get_map($where){
        $table_name = self::$table_maps;
        $sql = "SELECT data from $table_name ";
        $sql .= $this->get_where_query($where);
        $results = $this->wpdb->get_row($sql, ARRAY_A);
        if(!empty($results['data'])){
            return maybe_unserialize($results['data']);
        }
        return [];
    }
}
