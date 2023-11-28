<?php

namespace NotificationXPro\Core;

use NotificationX\Core\PostType as PostTypeFree;

/**
 * This class will provide all kind of helper methods.
 */
class PostType extends PostTypeFree {


    public function can_enable($source){
        $return = true;
        $rest = func_num_args() == 2 ? func_get_arg(1) : null;
        return apply_filters('nx_can_enable', $return, $source, $rest);
    }

    public function update_enabled_source($post){

    }

}
