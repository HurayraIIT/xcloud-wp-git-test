<?php
/**
 * Give Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Give;

use NotificationX\Core\Rules;
use NotificationX\Extensions\Give\Give as GiveFree;
use NotificationX\Extensions\GlobalFields as GlobalFieldsFree;
use NotificationXPro\Core\Helper;
use NotificationXPro\Extensions\GlobalFields;

/**
 * Give Extension
 * @todo normalize data for frontend.
 */
class Give extends GiveFree {
    public $post_type       = 'give_forms';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }



    public function init_fields(){
        parent::init_fields();
        add_filter( 'nx_content_fields', array( $this, 'content_fields' ) );

    }


    public function content_fields($fields){
        $content_fields = &$fields['content']['fields'];
        $default_options = [
            [
                'label'    => "Type for more result...",
                'value'    => null,
                'disabled' => true,
            ],
        ];

        $content_fields['give_forms_control'] = array(
            'label'    => __('Show Notification Of', 'notificationx-pro'),
            'name'     => 'give_forms_control',
            'type'     => 'select',
            'priority' => 200,
            'default'  => 'none',
            'options'  => GlobalFieldsFree::get_instance()->normalize_fields(array(
                'none'      => __('All', 'notificationx-pro'),
                'give_form' => __('By Form', 'notificationx-pro'),
            )),
            'rules'       => Rules::is( 'source', $this->id ),
        );
        $content_fields['give_form_list'] = array(
            'label'    => __('Select Donation Form', 'notificationx-pro'),
            'name'     => 'give_form_list',
            'type'     => 'select-async',
            'multiple' => true,
            'priority' => 201,
            'options'  => GlobalFieldsFree::get_instance()->normalize_fields($this->donation_forms(), 'source', $this->id, $default_options),
            'rules'       => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::is( 'give_forms_control', 'give_form' ),
            ]),
            'ajax'   => [
                'api'  => "/notificationx/v1/get-data",
                'data' => [
                    'type'   => "@type",
                    'source' => "@source",
                    'field'  => "give_form_list",
                ],
            ],
        );

        return $fields;
    }

    /**
     * Get donation forms
     * @return array
     */
    protected function donation_forms(){
        $forms_list = Helper::get_post_titles_by_search($this->post_type);
        return $forms_list;
    }

    /**
     * Lists available tags in the selected form.
     *
     * @param array $args An array of arguments, including inputValue.
     * @return array An indexed array of form IDs and titles.
     */
    public function restResponse($args) {
        // Check if inputValue is provided
        if (empty($args['inputValue'])) {
            return [];
        }
        // Get the forms that match the inputValue
        $forms = Helper::get_post_titles_by_search($this->post_type, $args['inputValue']);
        // Normalize the fields and return as an indexed array
        return array_values(GlobalFields::get_instance()->normalize_fields($forms, 'source', $this->id));
    }



}