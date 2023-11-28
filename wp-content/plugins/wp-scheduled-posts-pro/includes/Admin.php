<?php

namespace WPSP_PRO;

use WPSP\Helper;

class Admin
{
    private $free_enabled = false;
    public function __construct()
    {
        $this->load_plugin_pages();
        $this->plugin_licensing();
        $this->admin_notice_loaded();
        $this->load_settings();
        add_filter('plugin_action_links_' . WPSP_PRO_PLUGIN_BASENAME, array($this, 'insert_pro_plugin_links'));
        add_action( 'wpsp_el_action_before', [ $this, 'wpsp_el_action_before' ] );
        add_action( 'wpsp_el_modal_pro_fields', [ $this, 'wpsp_el_modal_pro_fields' ] );
    }

    public function load_plugin_pages()
    {
        new Admin\Menu();
    }

    public function load_settings()
    {
        new Admin\Settings(WPSP_SETTINGS_SLUG, WPSP_SETTINGS_NAME);
    }

    /**
     * add license module
     */
    public function plugin_licensing()
    {
        // Setup the settings page and validation
        $licensing = new Admin\WPDev\Licensing(
            WPSP_PRO_SL_ITEM_SLUG,
            'SchedulePress',
            'wp-scheduled-posts-pro'
        );
    }



    /**
     * Extending plugin links
     *
     * @since 2.3.1
     */
    public function insert_pro_plugin_links($links)
    {
        // settings
        $links[] = sprintf('<a href="admin.php?page=' . WPSP_PRO_SETTINGS_SLUG . '">' . __('Settings', 'wp-scheduled-posts-pro') . '</a>');

        // go pro
        if (!is_plugin_active('wp-scheduled-posts-pro/wp-scheduled-posts-pro.php')) {
            $links[] = sprintf('<a href="https://wpdeveloper.com/plugins/wp-scheduled-posts/" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Go Pro', 'wp-scheduled-posts-pro') . '</a>');
        }

        return $links;
    }

    public function admin_notice_loaded()
    {
        $this->free_enabled();
        // version compatibility notice
        if ($this->free_enabled && \version_compare(WPSP_PRO_VERSION, '2.5.3', '<')) {
            add_action('admin_notices', array($this, 'admin_version_compatible_notice'));
        }
    }

    /**
     * Check free is enabled
     */

    public function free_enabled()
    {
        if (function_exists('is_plugin_active')) {
            return $this->free_enabled = is_plugin_active('wp-scheduled-posts/wp-scheduled-posts.php');
        } else {
            if (class_exists('WPSP')) {
                return $this->free_enabled = true;
            }
        }
    }

    public function admin_version_compatible_notice()
    {
        $class = 'notice notice-error';
        $message = __('You are using an incompatible version of', 'wp-scheduled-posts-pro') . '<strong> ' . __('SchedulePress Pro.', 'wp-scheduled-posts-pro') . ' </strong>' . __('Please update to v2.5.3+', 'wp-scheduled-posts-pro');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
    }

	public function wpsp_el_action_before( $args ) {
        if ( ! empty( $args['republish_datetime'] ) ) {
            update_post_meta( $args['id'], '_wpscp_schedule_republish_date', sanitize_text_field( $args['republish_datetime'] ) );
        }

        if ( ! empty( $args['unpublish_datetime'] ) ) {
            update_post_meta( $args['id'], '_wpscp_schedule_draft_date', sanitize_text_field( $args['unpublish_datetime'] ) );
        }
	}

	public function wpsp_el_modal_pro_fields( $post_id ) {
		$republish_date = get_post_meta( $post_id, '_wpscp_schedule_republish_date', true );
		$unpublish_date = get_post_meta( $post_id, '_wpscp_schedule_draft_date', true );
        $status_post_republish_unpublish = Helper::get_settings('post_republish_unpublish');

        ?>
        <div class="wpsp-pro-fields wpsp-pro-activated <?php echo ($status_post_republish_unpublish !== true) ? 'disabled' : '';  ?>">
            <label>
                <span><?php esc_html_e( 'Republish On', 'wp-scheduled-posts' ); ?></span>
                <input id="wpsp-republish-datetime" type="text" name="republish_datetime" value="<?php echo esc_attr( $republish_date ) ?>" <?php echo ($status_post_republish_unpublish !== true) ? 'disabled' : '';  ?>  readonly autocomplete="off">
            </label>
            <label>
                <span><?php esc_html_e( 'Unpublish On', 'wp-scheduled-posts' ); ?></span>
                <input id="wpsp-unpublish-datetime" type="text" name="unpublish_datetime" value="<?php echo esc_attr( $unpublish_date ) ?>" <?php echo ($status_post_republish_unpublish !== true) ? 'disabled' : '';  ?> readonly autocomplete="off">
            </label>
        </div>
		<?php
	}
}
