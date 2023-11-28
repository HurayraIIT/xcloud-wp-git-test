<?php
/**
 * Envato Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Elementor;

use ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository;
use ElementorPro\Modules\Forms\Submissions\Database\Entities\Form_Snapshot;
use ElementorPro\Modules\Forms\Submissions\Database\Query;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Elementor\From as ElementorFormsFree;
use NotificationX\Extensions\GlobalFields;

/**
 * Envato Extension
 */
class From extends ElementorFormsFree {

    public $ignore_fields_type = ['recaptcha', 'recaptcha_v3', 'honeypot'];

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
        add_action( 'elementor_pro/forms/new_record', array( $this, 'new_record' ), 10, 2);
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
        add_action("elementor/editor/after_save", array($this, 'after_save'), 10, 3);
        add_action("delete_post", array($this, 'delete_post'), 10);
    }

    public function new_record( $record, $handler ) {
        $data       = [];
        $form_name  = $record->get_form_settings( 'form_name' );
        $form_id    = $record->get_form_settings( 'id' );
        $post_id    = $record->get_form_settings( 'form_post_id' );
        $meta       = $record->get_form_meta( ['date', 'time', 'remote_ip'] );
        $raw_fields = $record->get( 'fields' );

        foreach ( $raw_fields as $id => $field ) {
            if(!in_array($field['type'], $this->ignore_fields_type)){
                $data[ "el_$id" ] = $field['value'];
            }
        }

        $data['ip']        = $meta['remote_ip'];
        $data['form_id']   = $form_id;
        $data['post_id']   = $post_id;
        $data['title']     = $form_name;

        if (!empty($data)) {
            $this->save([
                'source'    => $this->id,
                'entry_key' => "{$post_id}_$form_id",
                'data'      => $data,
            ]);
            return true;
        }
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
            if($settings['form_list'] !== $entry['entry_key']){
                return false;
            }
        }
        return $return;
    }

    /**
     * Limit entry by selected form in 'Select a Form';
     *
     * @param [type] $return
     * @param [type] $entry
     * @param [type] $settings
     * @return boolean
     */
    public function after_save($post_id, $editor_data){
        $forms   = get_option("nx_elementor_form", []);
        $results = $this->find_element_recursive($editor_data);
        if(!empty($results)){
            $forms[$post_id] = $results;
            update_option("nx_elementor_form", $forms);
        }
        else if(isset($forms[$post_id])){
            unset($forms[$post_id]);
            update_option("nx_elementor_form", $forms);
        }
    }

    /**
     * Limit entry by selected form in 'Select a Form';
     *
     * @param [type] $return
     * @param [type] $entry
     * @param [type] $settings
     * @return boolean
     */
    public function delete_post($post_id){
        $forms = get_option("nx_elementor_form", []);
        if(isset($forms[$post_id])){
            unset($forms[$post_id]);
            update_option("nx_elementor_form", $forms);
        }
    }

    public function find_element_recursive( $elements, $results = [] ) {
        foreach ( $elements as $element ) {
            if ( isset($element['widgetType']) && 'form' === $element['widgetType']) {
                $results[] = [
                    'id'      => $element['id'],
                    'name'    => $element['settings']['form_name'],
                    'fields'  => $element['settings']['form_fields'],
                ];
            }
            if ( ! empty( $element['elements'] ) ) {
                $results = $this->find_element_recursive( $element['elements'], $results );
            }
        }
        return $results;
    }

    public function get_el_forms(){
        $el_forms  = Form_Snapshot_Repository::instance()->all();
        $all_forms = get_option("nx_elementor_form", []);
        foreach ($all_forms as $post_id => $forms) {
            foreach ($forms as $form) {
                $form['fields'] = array_map(function($field){
                    return [
                        'id'    => $field["custom_id"],
                        'type'  => isset($field["field_type"]) ? $field["field_type"] : 'text',
                        'label' => isset($field["field_label"]) ? $field["field_label"] : $field["custom_id"],
                    ];
                }, $form['fields']);
                $el_forms["{$post_id}_{$form['id']}"] = new Form_Snapshot( $post_id, $form );
            }
        }
        return $el_forms;
    }

    /**
     * Adds available forms to the Select a Form field in Content tab.
     *
     * @param array $forms
     * @return array
     */
    public function nx_form_list($forms) {
        // return $forms;
        $el_forms = $this->get_el_forms();
        $_forms  = $el_forms->unique(['post_id', 'id'])->map(function ( $form ) {
            return [
                'label' => $form->get_label(),
                'value' => $form->get_key(),
            ];
        })->values();
        $forms = GlobalFields::get_instance()->normalize_fields($_forms, 'source', $this->id, $forms);
        return $forms;
    }

    public function restResponse($args) {
        if (isset($args['form_id']) && $el_forms = $this->get_el_forms()) {
            if( is_array( $args['form_id'] ) ) {
                $args['form_id'] = intval($args['form_id']['value']);
            }else{
                $args['form_id'] = intval($args['form_id']);
            }
            // converts form collection to array.
            $el_forms = $el_forms->unique(['post_id', 'id'])->all();
            foreach ($el_forms as $form) {
                if($this->is_form($form, $args)){
                    $results = [];
                    foreach ($form->fields as $field) {
                        if(in_array($field['type'], $this->ignore_fields_type)) continue;
                        $results[] = [
                            'label' => $field['label'],
                            'value' => "tag_el_{$field['id']}",
                        ];
                    }
                    return $results;
                }
            }
        }

        return [];
    }

    public function is_form($form, $args){
        if(isset($args['form_id'])){
            if($form->get_key() == $args['form_id']){
                return true;
            }
            else{
                return false;
            }
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
        if (empty($data['nx_id']) || empty($data['display_last'])) return null;

        $submissions = Query::get_instance()->get_submissions( [
            'page' => 1,
            'per_page' => $data['display_last'],
            'filters' => [
                'form' => [
                    'value' => $data['form_list'],
                ],
                'after' => [
                    'value' => date('Y-m-d', strtotime('-' . intval($data['display_from']) . ' days')),
                ],
            ],
            'order' => [
                'order' => 'desc',
                'by' => 'created_at',
            ],
            'with_meta' => true,
            'with_form_fields' => true,
        ] );
        if(!empty($submissions['data']) && is_array($submissions['data'])){
            $entries = [];
            foreach ($submissions['data'] as $submission) {
                $entry = [];
                $form_fields = array_column($submission['form']['fields'], 'type', 'id');
                foreach ( $submission['values'] as $id => $field ) {
                    $type = $form_fields[$field['key']];
                    if(!in_array($type, $this->ignore_fields_type)){
                        $entry[ "el_{$field['key']}" ] = $field['value'];
                    }
                }
                $post_id         = $submission['post']['id'];
                $form_id         = $submission['form']['element_id'];
                $entry['form_id'] = $form_id;
                $entry['post_id'] = $post_id;
                $entry['ip']      = $submission['user_ip'];
                $entry['title']   = $submission['form']['name'];

                $entries[] = [
                    'source'     => $this->id,
                    'nx_id'      => $data['nx_id'],
                    'entry_key'  => "{$post_id}_$form_id",
                    'data'       => $entry,
                    'created_at' => $submission['created_at_gmt'],
                    'updated_at' => $submission['updated_at_gmt'],
                ];
            }
            $this->update_notifications($entries);
        }
    }
}
