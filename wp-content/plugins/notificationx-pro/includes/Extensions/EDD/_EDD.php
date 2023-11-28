<?php
/**
 * EDD Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\EDD;

use NotificationX\Core\Rules;
use NotificationX\Extensions\EDD\EDD as EDDFree;
use NotificationX\Extensions\GlobalFields as GlobalFieldsFree;
use NotificationXPro\Core\Helper;
use NotificationXPro\Extensions\GlobalFields;

/**
 * EDD Extension
 * @todo normalize data for frontend.
 * @todo show_purchaseof && excludes_product
 */
trait _EDD {
    public $post_type = 'download';

    public function categories($options){

        $product_categories = get_terms(array(
            'taxonomy'   => 'download_category',
            'hide_empty' => false,
        ));

        $category_list = [];

        if( ! is_wp_error( $product_categories ) ) {
            foreach( $product_categories as $product ) {
                $category_list[ $product->slug ] = $product->name;
            }
        }

        $options = GlobalFields::get_instance()->normalize_fields( $category_list, 'source', $this->id, $options );
        return $options;
    }

    public function products($options){
        $product_list = Helper::get_post_titles_by_search($this->post_type);
        return $product_list;
        $options      = GlobalFields::get_instance()->normalize_fields($product_list, 'source', $this->id, $options);
        return $options;
    }

    /**
     * Lists available tags in the selected form.
     *
     * @param array $args An array of arguments, including inputValue.
     * @return array An indexed array of form IDs and titles.
     */
    public function restResponse($args) {
        // Check if inputValue is provided
        if ( empty( $args['search_empty']) && empty($args['inputValue'] ) ) {
            return [];
        }

        // Get the forms that match the inputValue
        $forms = Helper::get_post_titles_by_search($this->post_type, $args['inputValue']);
        // Normalize the fields and return as an indexed array
        return array_values(GlobalFields::get_instance()->normalize_fields($forms, 'source', $this->id));
    }
}
