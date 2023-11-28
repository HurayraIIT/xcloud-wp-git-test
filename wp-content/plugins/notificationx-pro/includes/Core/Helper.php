<?php

namespace NotificationXPro\Core;

use NotificationX\Core\Helper as HelperFree;

/**
 * This class will provide all kind of helper methods.
 */
class Helper extends HelperFree {

    // There is no constructor function.

    /**
     * Get File Modification Time or URL
     *
     * @param string $file  File relative path for Admin
     * @param boolean $url  true for URL return
     * @return void|string|integer
     */
    public static function pro_file( $file, $url = false ){
        $base = '';
        if(defined('NX_DEBUG') && NX_DEBUG){
            if( $url ) {
                $base = NOTIFICATIONX_PRO_DEV_ASSETS;
            }
            else{
                $base = NOTIFICATIONX_PRO_DEV_ASSETS_PATH;
            }
            if(!file_exists(path_join(NOTIFICATIONX_DEV_ASSETS_PATH, $file))){
                $base = '';
            }
        }
        if(empty($base)){
            if( $url ) {
                $base = NOTIFICATIONX_PRO_ASSETS;
            }
            else{
                $base = NOTIFICATIONX_PRO_ASSETS_PATH;
            }
        }
        return path_join($base, $file);
    }

    public static function get_post_titles_by_search($post_type, $inputValue = '', $numberposts = 10, $args = []) {
        if (method_exists(get_parent_class(), 'get_post_titles_by_search')) {
            // Call the parent method with the same arguments
            return parent::get_post_titles_by_search($post_type, $inputValue, $numberposts, $args);
        }
        return [];
    }

}
