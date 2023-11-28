<?php 

namespace NotificationXPro\Extensions\Tutor;

use NotificationX\Extensions\GlobalFields;
use NotificationXPro\Core\Helper;

/**
 * 
 */
trait _Tutor
{
    public $post_type = 'courses';
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
