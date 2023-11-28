<?php

namespace NotificationXPro;

/**
 * NotificationX Upsell Class
 */
class CoreInstallerPro {

    /**
     * Instantiate the class
     *
     * @param string $affiliate
     */
    function __construct() {
        add_action( 'init', array( $this, 'init_hooks' ) );
        add_action( 'shutdown', array( $this, 'shutdown' ) );
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks() {

        if ( class_exists('\NotificationX\NotificationX' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_action( 'admin_notices', array( $this, 'activation_notice' ) );

        add_action( 'wp_ajax_NotificationX_Install_Core_installer', array( $this, 'install_notificationx_core' ) );
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function shutdown() {

        if ( class_exists('\NotificationX\NotificationX' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        try {
            $this->install_plugin( 'notificationx', 'notificationx.php' );
        } catch (\Exception $th) {
        }
    }

    /**
     * Show the plugin installation notice
     *
     * @return void
     */
    public function activation_notice() {
        if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'delete_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $message = sprintf("<strong>%s</strong> %s <strong>%s</strong> %s", 'NotificationX Pro', __('requires', 'notificationx-pro'), 'NotificationX', __( 'core plugin to be installed. Please get the plugin now!', 'notificationx-pro' ));
        $has_installed = get_plugins();
        if( isset( $has_installed['notificationx/notificationx.php'] ) ) {
            $button_text = __( 'Activate Now!', 'notificationx-pro' );

            if( isset( $has_installed['notificationx/notificationx.php']['Version'] ) && \version_compare( $has_installed['notificationx/notificationx.php']['Version'], '2.0.0', '<' ) ) {
                $message = sprintf("<strong>%s</strong> %s <strong>%s</strong> %s", 'NotificationX Pro', __('requires', 'notificationx-pro'), 'NotificationX', __( 'version to be 2.0.0. Please get the updated plugin now!', 'notificationx-pro' ));

                $button_text =  __( 'Update Now!', 'notificationx-pro' );
            }

        } else {
            $button_text =  __( 'Install Now!', 'notificationx-pro' );
        }

        ?>
        <div class="error notice is-dismissible">
            <p><?php echo $message; ?> <button id="notificationx-install-core" class="button button-primary"><?php echo $button_text; ?></button></p>
        </div>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
                $('#notificationx-install-core').on('click', function (e) {
                    var self = $(this);
                    e.preventDefault();
                    self.addClass('install-now updating-message');
                    self.text('<?php echo esc_js( 'Installing...' ); ?>');

                    $.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        type: 'post',
                        data: {
                            action: 'NotificationX_Install_Core_installer',
                            _wpnonce: '<?php echo wp_create_nonce('NotificationX_Install_Core_installer'); ?>',
                        },
                        success: function(response) {
                            self.text('<?php echo esc_js( 'Installed' ); ?>');
                            window.location.href = '<?php echo admin_url('admin.php?page=nx-edit' ); ?>';
                        },
                        error: function(error) {
                            self.removeClass('install-now updating-message');
                            console.log( error );
                        },
                        complete: function() {
                            self.attr('disabled', 'disabled');
                            self.removeClass('install-now updating-message');
                        }
                    });
                });
            } );
        </script>
        <?php
    }


    /**
     * Fail if plugin installtion/activation fails
     *
     * @param  Object $thing
     *
     * @return void
     */
    public function fail_on_error( $thing ) {
        if ( is_wp_error( $thing ) ) {
            wp_send_json_error( $thing->get_error_message() );
        }
    }

    /**
     * Install NotificationX
     *
     * @return void
     */
    public function install_notificationx_core() {
        check_ajax_referer( 'NotificationX_Install_Core_installer' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You don\'t have permission to install the plugins' ) );
        }
        $is_installed = isset( $_POST['installed'] ) ? $_POST['installed'] : false;
        $nx_status = $this->install_plugin( 'notificationx', 'notificationx.php' );
        $this->fail_on_error( $nx_status );

        wp_send_json_success();
    }

    /**
     * Install and activate a plugin
     *
     * @param  string $slug
     * @param  string $file
     *
     * @return WP_Error|null
     */
    public function install_plugin( $slug, $file ) {
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $plugin_basename = $slug . '/' . $file;

        if ( nx_pro_is_plugin_active( 'notificationx/notificationx.php' ) ) {
            return;
        }
        // if exists and not activated
        if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_basename ) ) {
            return activate_plugin( $plugin_basename );
        }

        // seems like the plugin doesn't exists. Download and activate it
        $upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

        $api      = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
        $result   = $upgrader->install( $api->download_link );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return activate_plugin( $plugin_basename );
    }
}