<?php

namespace WPSP_PRO\Traits;

trait Core
{


    /**
     * Make lite version available in Pro
     *
     * @since 2.0.0
     */
    public function make_lite_available()
    {
        $basename = 'wp-scheduled-posts/wp-scheduled-posts.php';
        $plugin_data = $this->get_plugin_data('wp-scheduled-posts');

        if ($this->is_plugin_installed($basename)) {
            // upgrade plugin - attempt for once
            if (isset($plugin_data->version) && $this->get_plugin_version($basename) != $plugin_data->version) {
                $this->upgrade_plugin($basename);
            }

            // activate plugin
            if (is_plugin_active($basename)) {
                return delete_transient('wpscp_install_lite');
            } else {
                activate_plugin($this->safe_path(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $basename), '', false, true);
                return delete_transient('wpscp_install_lite');
            }
        } else {
            // install & activate plugin
            $download_link = isset($plugin_data->download_link) ? $plugin_data->download_link : WPSP_PRO_PLUGIN_URL . '/library/wp-scheduled-posts.zip';

            if ($this->install_plugin($download_link)) {
                return delete_transient('wpscp_install_lite');
            }
        }

        return false;
    }

    /**
     * Creates an action menu
     *
     * @since 2.0.0
     */
    public function insert_plugin_links($links)
    {
        // settings
        $links[] = sprintf('<a href="admin.php?page=wp-scheduled-posts">' . __('Settings', 'wp-scheduled-posts-pro') . '</a>');

        return $links;
    }

    /**
     * Plugin Licensing
     *
     * @since v1.0.0
     */
    public function wpscp_plugin_licensing()
    {
        if (is_admin()) {
            // Setup the settings page and validation
            $licensing = new WpScp_Licensing(
                WPSP_PRO_SL_ITEM_SLUG,
                WPSP_PRO_SL_ITEM_NAME,
                'wp-scheduled-posts-pro'
            );
        }
    }
}
