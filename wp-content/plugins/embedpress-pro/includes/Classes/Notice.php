<?php

namespace Embedpress\Pro\Classes;

if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Notice {

    /**
     * This notice will appear if Elementor is not installed or activated or both
     */
    public function failed_to_load() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if(defined('EMBEDPRESS_IS_LOADED')){
            return;
        }

        $plugin = 'embedpress/embedpress.php';

        if (Helper::is_plugin_installed( $plugin ) ) {
            $activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
            $message = __( '<strong>EmbedPress - Pro</strong> requires <strong>EmbedPress</strong> plugin to be active. Please activate EmbedPress to continue.', 'embedpress-pro' );
            $button_text = __( 'Activate EmbedPress', 'embedpress-pro' );
        } else {
            $activation_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=embedpress' ), 'install-plugin_embedpress' );
            $message = sprintf( __( '<strong>EmbedPress - Pro</strong> requires <strong>EmbedPress</strong> plugin to be installed and activated. Please install EmbedPress to continue.', 'embedpress-pro' ), '<strong>', '</strong>' );
            $button_text = __( 'Install EmbedPress', 'embedpress-pro' );
        }

        $button = '<p><a href="' . $activation_url . '" class="button-primary">' . $button_text . '</a></p>';

        printf( '<div class="error"><p>%1$s</p>%2$s</div>', __( $message ), $button );
    }
}
