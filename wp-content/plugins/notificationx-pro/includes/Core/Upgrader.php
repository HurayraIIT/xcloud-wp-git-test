<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Core;

use NotificationX\Admin\Cron;
use NotificationX\Core\Upgrader as UpgraderFree;
use NotificationX\GetInstance;
use NotificationXPro\Admin\Settings;
use NotificationXPro\Feature\SalesFeatures;

/**
 * Upgrader Class
 */
class Upgrader extends UpgraderFree {

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();

        $installed_ver = $this->database->get_option("nx_pro_version", false);
        if(!$installed_ver || version_compare($installed_ver, '2.0.0', '<')){
            $free_upgraded = $this->database->get_option("notificationx_2x_upgraded", false);
            if( $free_upgraded ) {
                try {
                    add_action('plugins_loaded', [$this, 'nx_2x_migration'], 20);
                    $this->database->update_option('notificationx_pro_2x_upgraded', true, 'no');
                } catch (\Exception $th) {
                    return;
                }
                $this->database->update_option( 'nx_pro_version', NOTIFICATIONX_PRO_VERSION, 'no' );
            }
        }
        if(!$installed_ver || version_compare($installed_ver, '2.0.2', '<')){
            add_action('plugins_loaded', [$this, 'migrate_roles'], 20);

            $this->database->update_option( 'nx_pro_version', NOTIFICATIONX_PRO_VERSION, 'no' );
        }

    }

    public function nx_2x_migration(){

        $posts = PostType::get_instance()->get_posts(
            [ 'source' => [ 'in', SalesFeatures::get_instance()->sales_count_sources ] ],
            'nx_id'
        );

        if(!empty($posts) && is_array($posts)){
            foreach ($posts as $post) {
                Cron::get_instance()->set_cron_single($post['nx_id']);
                Cron::get_instance()->set_cron($post['nx_id'], 'nx_sales_count_interval');
            }
        }
    }

    public function migrate_roles(){
        $settings     = Settings::get_instance()->get_selected_roles();
        $role_cap_map = Settings::get_instance()->get_role_map($settings);
        do_action('wpd_add_cap', $role_cap_map);
    }

}
