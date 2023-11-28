<?php

namespace WPSP_PRO\Admin;

class Menu
{
    public function __construct()
    {
        add_action('wpsp_settings_tab_menu', array($this, 'load_tab_menu_items_markup'));
        add_action('wpsp_settings_tab_body', array($this, 'load_scheduled_template_markup'));
    }
    public function load_tab_menu_items_markup()
    {
?>
        <li data-tab="wpsp_man_sch"><a href="#wpsp_man_sch"><?php _e('Manage Schedule', 'wp-scheduled-posts-pro'); ?></a></li>
        <li data-tab="wpsp_lic"><a href="#wpsp_lic"><?php _e('License', 'wp-scheduled-posts-pro'); ?></a></li>
<?php
    }


    /**
     * WPSP scheduled template
     *
     * @function load_scheduled_template_markup
     */
    public function load_scheduled_template_markup()
    {
        //include integration page
        include WPSP_PRO_VIEW_DIR_PATH . 'scheduled-settings.php';
    }
}
