<?php

/**
 * GravityForms Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\GRVF;

use NotificationX\Core\Helper;
use NotificationX\Extensions\GRVF\GravityForms as GravityFormsFree;
use NotificationX\Extensions\GlobalFields;

/**
 * GravityForms Extension
 */
class GravityForms extends GravityFormsFree {

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function init() {
        parent::init();
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_form_list', [$this, 'nx_form_list'], 9);
    }

    public function public_actions() {
        parent::public_actions();
        add_action( 'gform_after_submission', array( $this, 'save_new_records' ), 10, 2);
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();

        add_filter("nx_can_entry_{$this->id}", array($this, 'can_entry'), 10, 3);
    }

    /**
     * Adds available forms to the Select a Form field in Content tab.
     *
     * @param array $forms
     * @return array
     */
    public function nx_form_list($forms) {
        $_forms = GlobalFields::get_instance()->normalize_fields($this->get_forms(), 'source', $this->id);
        return array_merge($_forms, $forms);
    }

    public function get_forms() {
        $forms = [];
        if (!class_exists('GFForms')) {
            return [];
        }
        global $wpdb;
        $formresult = $wpdb->get_results('SELECT id, title FROM `' . $wpdb->prefix . 'gf_form` ORDER BY title');
        if (!empty($formresult)) {
            foreach ($formresult as $form) {
                $key = $this->key($form->id);
                $forms[$key] = $form->title;
            }
        }
        return $forms;
    }

    public function restResponse($args) {
        if (!class_exists('GFForms')) {
            return [];
        }

        if (isset($args['form_id'])) {
            global $wpdb;
            if( is_array( $args['form_id'] ) ) {
                $form_id = intval($args['form_id']['value']);
            }else{
                $form_id = intval($args['form_id']);
            }
            $queryresult = $wpdb->get_results('SELECT display_meta FROM `' . $wpdb->prefix . 'gf_form_meta` WHERE form_id = ' . $form_id . '');

            $formdata = $queryresult[0]->display_meta;
            $keys = $this->keys_generator($formdata);
            $returned_keys = array();

            if (is_array($keys) && !empty($keys)) {
                foreach ($keys as $key => $value) {
                    $returned_keys[] = array(
                        'label' => ucwords(str_replace('_', ' ', str_replace('-', ' ', $value))),
                        'value' => "tag_$key",
                    );
                }
                return $returned_keys;
            }
        }
        wp_send_json_error([]);
    }

    public function keys_generator($fieldsString) {
        $fields = array();
        $fieldsdata = json_decode($fieldsString, true);
        if (!empty($fieldsdata['fields'])) {
            foreach ($fieldsdata['fields'] as $field) {
                if (Helper::filter_contactform_key_names($field['label'])) {
                    $key = Helper::rename_contactform_key_names($field['id']);
                    $value = Helper::rename_contactform_key_names($field['label']);
                    $fields[$key . '_' . $value] = $value;

                    if (isset($field['inputs']) && is_array($field['inputs'])) {
                        foreach ($field['inputs'] as $input) {
                            if ((isset($input['label']) && !empty($input['label'])) && (!isset($input['isHidden']) || !$input['isHidden'])) {
                                if (Helper::filter_contactform_key_names($input['label'])) {
                                    $key = Helper::rename_contactform_key_names($input['id']);
                                    $value = Helper::rename_contactform_key_names($input['label']);
                                    $fields[$key . '_' . $value] = $field['label'] . " > " . $value;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $fields;
    }

    public function save_new_records($entry, $form, $regenerate = false) {
        if (isset($form['fields']) && is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if (isset($field['inputs']) && is_array($field['inputs'])) {
                    $field_combined_value = "";
                    foreach ($field['inputs'] as $input) {
                        if (array_key_exists($input['id'], $entry)) {
                            if (Helper::filter_contactform_key_names($input['label']) && !empty($entry[$input['id']])) {
                                $value = $input['id'] . "_" . Helper::rename_contactform_key_names($input['label']);
                                $data[$value] = $entry[$input['id']];
                                $field_combined_value = $field_combined_value . " " . $entry[$input['id']];
                            }
                        }
                    }
                    if (Helper::filter_contactform_key_names($field['label']) && !empty($field_combined_value)) {
                        $value = $field['id'] . "_" . Helper::rename_contactform_key_names($field['label']);
                        $data[$value] = $field_combined_value;
                    }
                } else {
                    if (Helper::filter_contactform_key_names($field['label']) && !empty($entry[$field['id']])) {
                        $value = $field['id'] . "_" . Helper::rename_contactform_key_names($field['label']);
                        $data[$value] = $entry[$field['id']];


                        if (strpos(strtolower($field['label']), 'email') !== false) {
                            $data['email'] = $entry[$field['id']];
                        }
                    }
                }
            }
        }
        $data['title'] = $form['title'];
        $data['timestamp'] = $regenerate ? strtotime($entry['date_created']) : time();

        if (!empty($data)) {
            $key = $this->key($form['id']);
            $this->save([
                'source'    => $this->id,
                'entry_key' => $key,
                'data'      => $data,
            ]);
            return true;
        }
        return false;
    }

    public function saved_post($post, $data, $nx_id) {
        $this->delete_notification(null, $nx_id);
        $this->get_notification_ready($data);
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data) {
        if (!class_exists('GFAPI') || empty($data['nx_id'])) return null;

        $from = isset($data['display_from']) ? intval($data['display_from']) : 0;
        $page_size = isset($data['display_last']) ? intval($data['display_last']) : 0;

        $form_id = str_replace("{$this->id}_", '', $data['form_list']);

        $form = \GFAPI::get_form($form_id);
        if (!empty($form['id'])) {
            $search_criteria = array();
            $search_criteria['start_date'] = date('Y-m-d', strtotime("-$from days"));
            $search_criteria['end_date'] = date('Y-m-d', time());
            $search_criteria['page_size'] = $page_size;
            $search_criteria['status'] = 'active';

            $entries = \GFAPI::get_entries($form_id, $search_criteria);

            foreach ($entries as $entry) {
                $this->save_new_records($entry, $form, true);
            }
        }
    }

    /**
     * entry_key
     *
     * @param string $key
     * @return string
     */
    public function key($key) {
        $key = $this->id . '_' . $key;
        return $key;
    }

    /**
     * Limit entry by selected form in 'Select a Form';
     *
     * @param [type] $return
     * @param [type] $entry
     * @param [type] $settings
     * @return boolean
     */
    public function can_entry($return, $entry, $settings){
        if(!empty($settings['form_list']) && !empty($entry['entry_key'])){
            $selected_form = $settings['form_list'];
            $form_id = $entry['entry_key'];
            if($selected_form != $form_id){
                return false;
            }

        }
        return $return;
    }
}
