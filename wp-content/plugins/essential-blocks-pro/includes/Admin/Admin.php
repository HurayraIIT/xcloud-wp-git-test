<?php

namespace EssentialBlocks\Pro\Admin;

use EssentialBlocks\Traits\HasSingletone;
use EssentialBlocks\Pro\Admin\FormResponseTable;
use EssentialBlocks\Pro\Deps\WPDeveloper\Licensing\LicenseManager;

class Admin {
    use HasSingletone;

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue'], 9 );
        add_action( 'admin_menu', [$this, 'admin_menu'] );
        add_filter( 'set-screen-option', [$this, 'form_table_set_option'], 10, 3 );
        $this->license();
    }

    public function enqueue( $hook ) {
        if ( "toplevel_page_essential-blocks" === $hook ) {
            wpdev_essential_blocks_pro()->assets->enqueue(
                'admin',
                'admin/js/index.js',
                ['essential-blocks-admin']
            );
            wpdev_essential_blocks_pro()->assets->localize( 'admin', 'ebProAdminConfig', [
                'logo' => wpdev_essential_blocks()->assets->icon( 'eb-logo.svg' )
            ] );
            wpdev_essential_blocks_pro()->assets->enqueue( 'admin', 'admin/js/style.css' );
        }

        if ( "essential-blocks_page_eb-form-responses" === $hook ) {
            wpdev_essential_blocks_pro()->assets->enqueue(
                'form-block-response',
                'js/form-response.js',
                []
            );
        }
    }

    public function admin_menu() {
        global $form_response_hook;

        //Form Response Page
        $form_response_hook = add_submenu_page(
            'essential-blocks',
            'Form Responses',
            'Form Responses',
            'activate_plugins',
            'eb-form-responses',
            [$this, 'form_responses']
        );

        // add_action( "load-$form_response_hook", [$this, 'eb_form_response_add_options'] );
    }

    // public function eb_form_response_add_options() {

    //     global $form_response_hook;
    //     global $table;

    //     $screen = get_current_screen();

    //     // get out of here if we are not on our settings page
    //     if ( ! is_object( $screen ) || $screen->id != $form_response_hook ) {
    //         return;
    //     }

    //     $args = [
    //         'label'   => __( 'Elements per page', 'supporthost-admin-table' ),
    //         'default' => 2,
    //         'option'  => 'elements_per_page'
    //     ];
    //     add_screen_option( 'per_page', $args );

    //     $table = new FormResponseTable();
    // }

    // function form_table_set_option( $status, $option, $value ) {
    //     return $value;
    // }

    public function form_responses() {

        //Get form Id
        $form_id = '';
        if ( isset( $_GET['form'] ) ) {
            $form_id = sanitize_key( $_GET['form'] );
        }

        global $wpdb;
        $table_name = ESSENTIAL_BLOCKS_FORM_SETTINGS_TABLE;

        $form_lists = $wpdb->get_results( "SELECT block_id, title, fields FROM $table_name" );

        echo '<div class="wrap">';
        echo '<h2 style="margin-bottom: 20px;">Form Block Response Table</h2>';
        if ( count( $form_lists ) > 0 ) {
            if ( ! empty( $form_id ) ) {
                foreach ( $form_lists as $index => $form_item ) {
                    if ( isset( $form_lists[$index]->block_id ) && $form_id === $form_lists[$index]->block_id ) {
                        $table = new FormResponseTable(
                            $form_lists[$index]->block_id,
                            $form_lists[$index]->fields
                        );
                        break;
                    }
                }
            } else {
                $table = new FormResponseTable( $form_lists[0]->block_id, $form_lists[0]->fields );
            }

            echo '<form method="post">';

            echo '<select name="form_list" id="select-form-list">';
            foreach ( $form_lists as $form ) {
                echo sprintf(
                    '<option name="form_list" value="%1$s" %3$s>%2$s</option>',
                    $form->block_id,
                    $form->title,
                    $form->block_id === $form_id ? 'selected' : ''
                );
            }
            echo '</select>';
            echo '<button type="button" id="export" name="export" value="export" style="margin-left: 10px;" class="button button-primary eb-form-export">Export as CSV</button>';
            echo '</form>';

            if ( isset( $table ) ) {
                $table->prepare_items();

                echo '<form id="eb_form_response" method="post" style="margin-top: -40px;">';
                $table->search_box( 'search', 'eb-form-response' );
                $table->display();

                echo '</form>';
            } else {
                echo "<p>No Form Found based on your Selection</p>";
            }
        } else {
            echo "<p>No Form Found</p>";
        }

        echo '</div>';

        // Helper::views( 'form-response', [] );
    }

    public function license() {
        return LicenseManager::get_instance( [
            'plugin_file'    => ESSENTIAL_BLOCKS_PRO_FILE,
            'version'        => ESSENTIAL_BLOCKS_PRO_VERSION,
            'item_id'        => ESSENTIAL_BLOCKS_PRO_SL_ITEM_ID,
            'item_name'      => ESSENTIAL_BLOCKS_PRO_SL_ITEM_NAME,
            'item_slug'      => ESSENTIAL_BLOCKS_PRO_SL_ITEM_SLUG,
            'storeURL'       => ESSENTIAL_BLOCKS_PRO_STORE_URL,
            'textdomain'     => 'essential-blocks-pro',
            'db_prefix'      => ESSENTIAL_BLOCKS_PRO_SL_DB_PREFIX,
            'page_slug'      => 'essential-blocks',

            'scripts_handle' => 'essential-blocks-pro-admin',
            'screen_id'      => "toplevel_page_essential-blocks",

            'api'            => 'rest',
            'rest'           => [
                'namespace'  => 'essential-blocks-pro',
                'permission' => 'delete_users'
            ]
        ] );
    }
}
