<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\Core\Rules;
use NotificationX\Types\Inline as InlineFree;
use NotificationXPro\Extensions\GlobalFields;
use NotificationXPro\Feature\Maps;

/**
 * Extension Abstract for all Extension.
 */
class Inline extends InlineFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {

        parent::__construct();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_metabox_tabs', [ $this, 'nx_tabs' ], 11 );

    }

    /**
     * Undocumented function
     *
     * @param [type] $tabs
     * @return void
     */
    public function nx_tabs( $tabs ) {
        $tabs['display_tab']   = Rules::is( 'type', $this->id, true, $tabs['display_tab'] );
        $tabs['customize_tab'] = Rules::is( 'type', $this->id, true, $tabs['customize_tab'] );

        $themes       = &$tabs['design_tab']['fields']['themes']['fields']['advance_edit'];
        $themes       = Rules::is( 'type', $this->id, true, $themes );
        $link_options = &$tabs['content_tab']['fields']['link_options'];
        $link_options = Rules::is( 'type', $this->id, true, $link_options );
        $utm_options  = &$tabs['content_tab']['fields']['utm_options'];
        $utm_options  = Rules::is( 'type', $this->id, true, $utm_options );
        $random_order = &$tabs['content_tab']['fields']['content']['fields']['random_order'];
        $random_order = Rules::is( 'type', $this->id, true, $random_order );

        $tabs['content_tab']['fields']['visibility-hooks'] = [
            'label'    => __( 'Visibility', 'notificationx-pro' ),
            'name'     => 'visibility-hooks',
            'type'     => 'section',
            'priority' => 105,
            'fields'   => [
                'inline_location' => [
                    'label'    => __( 'Locations', 'notificationx-pro' ),
                    'name'     => 'inline_location',
                    'type'     => 'select',
                    'default'  => '',
                    'multiple' => true,
                    'priority' => 7,
                    'options'  => apply_filters( 'nx_inline_hook_options', [] ),
                ],
            ],
            'rules'    => Rules::is( 'type', $this->id ),
        ];
        return $tabs;
    }

    /**
     *
     * @param  bool $exclude
     * @param  array $settings
     * @return bool
     */
    public function show_on_exclude( $exclude, $settings ) {
        return $exclude;
    }
}
