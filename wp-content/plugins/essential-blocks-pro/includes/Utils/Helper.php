<?php
namespace EssentialBlocks\Pro\Utils;

use EssentialBlocks\Utils\Helper as FreeHelper;

class Helper extends FreeHelper {

    protected static function get_views_path( $name ) {
        $_free_views_path = parent::get_views_path( $name );

        if ( $_free_views_path === false ) {
            $_free_views_path = ESSENTIAL_BLOCKS_PRO_DIR_PATH . 'views/' . $name . '.php';
        }

        if ( file_exists( $_free_views_path ) ) {
            return $_free_views_path;
        }

        return false;
    }

    /**
     * Get views for front-end display
     *
     * @param string $name  it will be file name only from the view's folder.
     * @param array $data
     * @return array
     */
    // public static function views( $name, $data = [] ) {
    //     extract( $data );
    //     $helper = self::class;

    //     $file = ESSENTIAL_BLOCKS_PRO_DIR_PATH . 'views/' . $name . '.php';

    //     if ( is_readable( $file ) ) {
    //         include $file;
    //     }
    // }

    public static function modify_array_key( $array, $name ) {
        $newArray = [];

        // Loop through each key-value pair in the original array
        foreach ( $array as $key => $value ) {
            // Add "site/" to the beginning of each key
            $newKey = $name . '/' . $key;

            // Add the key-value pair with the modified key to the new array
            $newArray[$newKey] = $value;
        }
        return $newArray;
    }

    /**
     * Function for get recaptcha settings
     * @param string $property [optional]
     */
    public static function get_recaptcha_settings( $property = null ) {
        $eb_settings = get_option( 'eb_settings' );
        if ( isset( $eb_settings['reCaptcha'] ) ) {
            $recaptchSetting = json_decode( wp_unslash( $eb_settings['reCaptcha'] ) );
            if ( is_object( $recaptchSetting ) && isset( $recaptchSetting->$property ) ) {
                return $recaptchSetting->$property;
            }
            return $recaptchSetting;
        }
    }

    /**
     * Function for get recaptcha settings
     * @param string $property [optional]
     */
    public static function get_mailchimp_api() {
        $eb_settings = get_option( 'eb_settings' );
        if ( isset( $eb_settings['mailchimp'] ) ) {
            return $eb_settings['mailchimp'];
        }
    }

    /**
     * Get form columns
     */
    public static function get_form_title( $form_id ) {
        global $wpdb;
        $table_name = ESSENTIAL_BLOCKS_FORM_SETTINGS_TABLE;

        $query = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT title FROM $table_name WHERE block_id = %s",
                $form_id
            )
        );

        $title = isset( $query->title ) ? $query->title : 'Untitled';

        return $title;
    }

    /**
     * Get form columns
     */
    public static function get_form_columns( $form_id ) {
        global $wpdb;
        $table_name = ESSENTIAL_BLOCKS_FORM_SETTINGS_TABLE;

        $query = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT fields FROM $table_name WHERE block_id = %s",
                $form_id
            )
        );

        $form_fields = $query->fields;

        $columns = [];
        if ( is_serialized( $form_fields ) ) {
            // $columns['id'] = 'Id';
            $fields = unserialize( $form_fields );
            if ( count( $fields ) > 0 ) {
                foreach ( $fields as $index => $field ) {
                    $columns[$index] = isset( $field->label ) ? $field->label : $index;
                }
            }
            $columns['email_status'] = 'Email Status';
            $columns['submitted_at'] = 'Submitted Time';
        }

        return $columns;
    }

    /**
     * Get the form response table data
     *
     */
    public static function form_response_table_data( $form_id, $search = "" ) {
        $data = [];

        global $wpdb;
        $table_name = ESSENTIAL_BLOCKS_FORM_ENTRIES_TABLE;

        $responses = [];
        if ( ! empty( $search ) ) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE block_id=%s AND response like %s ORDER BY created_at DESC",
                $form_id,
                '%' . $wpdb->esc_like( $search ) . '%'
            );

            $responses = $wpdb->get_results( $query );
        } else {
            $responses = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table_name WHERE block_id = %s ORDER BY created_at DESC",
                $form_id )
            );
        }

        if ( $responses ) {
            foreach ( $responses as $index => $item ) {
                if ( isset( $item->response ) && is_serialized( $item->response ) ) {
                    $response_data = unserialize( $item->response );
                    // $response_data['id']           = isset( $item->id ) ? $item->id : '';
                    $response_data['email_status'] = isset( $item->email_status ) ? $item->email_status === '1' ? 'success' : 'failed' : '';
                    $response_data['submitted_at'] = isset( $item->created_at ) ? $item->created_at : '';
                    $data[]                        = $response_data;
                }
            }
        }
        return $data;
    }

    /**
     * Export as CSV function
     */
    public static function export_as_csv( $data, $columns = [], $filename = 'export-data.csv' ) {
        if ( count( $data ) == 0 ) {
            return false;
        }

        ob_start(); // Start output buffering

        //Modify Headers
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );

        // Output data
        if ( count( $columns ) > 0 ) {
            // Output CSV header
            fputcsv( $output, $columns );
            foreach ( $data as $row ) {
                $reorder = Helper::reorder_array( $row, $columns );
                fputcsv( $output, $reorder );
            }
        } else {
            fputcsv( $output, $data );
        }

        fclose( $output );
        // ob_end_flush();

        exit;
    }

    /**
     * Prepare CSV string data
     */
    public static function prepare_csv_data( $data, $columns = [] ) {
        if ( count( $data ) == 0 ) {
            return '';
        }

        $final_data = [];
        $csv        = '';

        // Output data
        if ( count( $columns ) > 0 ) {
            $csv .= implode( ',', $columns ) . "\n";
            foreach ( $data as $row ) {
                $final_data[] = Helper::reorder_array( $row, $columns );
            }
        } else {
            $final_data = $data;
        }

        $csv = '';

        foreach ( $final_data as $row ) {
            $rowValues = array_map( function ( $value ) {
                return '"' . str_replace( '"', '""', $value ) . '"';
            }, $row );

            $csv .= implode( ',', $rowValues ) . "\n";
        }

        return $csv;
    }

    /**
     * Function for reorder array
     */
    public static function reorder_array( $arrayToReorder, $referenceArray ) {
        $reorderedArray = [];

        foreach ( $referenceArray as $key => $value ) {
            if ( isset( $arrayToReorder[$key] ) ) {
                $reorderedArray[$key] = $arrayToReorder[$key];
            }
        }

        return $reorderedArray;
    }

    /**
     * Hightlight search keyword
     *
     * @param string $content
     * @param string $search
     *
     * @return string
     */
    public static function highlight_search_keyword( $content, $search ) {
        $search_keys = implode( '|', explode( ' ', $search ) );
        $content     = preg_replace( '/(' . $search_keys . ')/iu', "<strong>$1</strong>", $content );

        return $content;
    }
}
