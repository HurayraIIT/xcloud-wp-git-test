<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\NotificationX;
use NotificationX\Types\Conversions as ConversionsFree;
use NotificationXPro\Feature\Maps;

/**
 * Extension Abstract for all Extension.
 */
class Conversions extends ConversionsFree {
    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();

        add_filter('nx_map_fourth_param_dependency', [$this, 'map_dependency']);
        add_filter('nx_sound_options', [$this, 'sound_options']);

        $this->themes['conv-theme-six']['template'] = Maps::get_instance()->get_themes( [ 'map_fourth_param'    => __('purchased', 'notificationx-pro') ] );
        $this->themes['maps_theme']['template']     = Maps::get_instance()->get_themes( [ 'map_fourth_param'    => __('purchased', 'notificationx-pro') ] );

        $sales_count_template = [
            'first_param'         => 'tag_sales_count',
            'custom_first_param'  => __('Someone', 'notificationx-pro'),
            'second_param'        => __('people purchased', 'notificationx-pro'),
            'third_param'         => 'tag_product_title',
            'fourth_param'        => 'tag_7days',
            'custom_fourth_param' => __('in last {{day:7}}', 'notificationx-pro'),
        ];

        $this->themes['conv-theme-seven']['template'] = $sales_count_template;
        $this->themes['conv-theme-eight']['template'] = $sales_count_template;
        $this->themes['conv-theme-nine']['template']  = $sales_count_template;

        // @todo create new template for conv themes.
        $this->templates['woo_template_sales_count']['first_param']['tag_sales_count'] = __('Sales Count', 'notificationx-pro');
        $this->templates['woo_template_sales_count']['fourth_param']['tag_1day']       = __('In last 1 day', 'notificationx-pro');
        $this->templates['woo_template_sales_count']['fourth_param']['tag_7days']      = __('In last 7 days', 'notificationx-pro');
        $this->templates['woo_template_sales_count']['fourth_param']['tag_30days']     = __('In last 30 days', 'notificationx-pro');

        $map_templates   = Maps::get_instance()->get_templates();
        $this->templates = array_merge($map_templates, $this->templates);

        add_filter( 'nx_content_fields', [ $this, 'content_fields_pro' ] );
    }

	public function init_fields(){
		parent::init_fields();
		add_filter( 'nx_design_tab_fields', [$this, 'get_nx_design_tab_fields'] );
	}

	function get_nx_design_tab_fields($fields) {
		//add section field.
		$fields['advance_design_section']['fields']['link_button_design'] = [
			'name'     => 'link_button_design',
			'type'     => 'section',
			'label'    => __( 'Link Button', 'notificationx-pro' ),
			'priority' => 7,
			'fields'   => [
				"link_button_bg_color" => [
					'label'   => __("Background Color", 'notificationx'),
					'name'    => "link_button_bg_color",
					'type'    => "colorpicker",
					'default' => "#5612D6",
				],
				"link_button_text_color" => [
					'label'   => __("Text Color", 'notificationx'),
					'name'    => "link_button_text_color",
					'type'    => "colorpicker",
					'default' => "#fff",
				],
				"link_button_font_size" => [
					'label'   => __("Font Size", 'notificationx'),
					'name'    => "link_button_font_size",
					'type'    => "number",
					'default' => 14,
					'min'     => 1,
					'max'     => 100,
				],
			],
		];

		return $fields;
	}

    public function content_fields_pro( $fields ){
        $content_fields = &$fields['content']['fields'];
        unset( $content_fields['product_control']['disable'] );
        unset( $content_fields['product_exclude_by']['disable'] );
        return $fields;
    }

    public function map_dependency($dependency) {
        $dependency = array_merge($dependency, [
            "{$this->id}_conv-theme-six",
            "{$this->id}_maps_theme",
        ]);
        return $dependency;
    }

    public function fallback_data($data, $saved_data, $settings) {
        $data['anonymous_title'] = __('Anonymous Product', 'notificationx-pro');
        return $data;
    }


    public function sound_options($options) {
        $options = GlobalFields::get_instance()->normalize_fields(
            [
                'sales-one'    => __('Sound One', 'notificationx-pro'),
                'sales-two'    => __('Sound Two', 'notificationx-pro'),
            ],
            'type',
            $this->id,
            $options
        );
        return $options;
    }


    /**
     * @todo remove in the future.
     *
     * @param [type] $data
     * @param [type] $settings
     * @return void
     */
    public function nx_can_entry($return, $entry, $settings){
        if(!($this->_excludes_product($entry['data'], $settings) && $this->_show_purchaseof($entry['data'], $settings))){
            return false;
        }

        return $return;
    }

    /**
     * @todo remove in the future.
     *
     * @param [type] $data
     * @param [type] $settings
     * @return void
     */
    public function show_exclude_product( $data, $settings ){
        $new_data = [];

        if( ! empty( $data ) ) {
            foreach( $data as $key => $product ) {
                if( $this->_excludes_product($product, $settings) && $this->_show_purchaseof($product, $settings)  ) {
                    $new_data[ $key ] = $product;
                }
            }
        }

        return $new_data;

    }

    public function _excludes_product( $product, $settings ){
        if( empty( $settings['product_exclude_by'] ) || $settings['product_exclude_by'] === 'none' ) {
            return true;
        }

        $product_category_list = [];

        $product_id = $product['product_id'];
        if( $settings['product_exclude_by'] == 'product_category' ) {
            $term = 'product_cat';
            if($settings['source'] == 'edd' || $settings['source'] == 'edd_inline'){
                $term = 'download_category';
            }
            $product_categories = get_the_terms( $product_id, $term );
            if( ! is_wp_error( $product_categories ) ) {
                foreach( $product_categories as $category ) {
                    $product_category_list[] = $category->slug;
                }
            }

            $product_category_count = count( $product_category_list );
            $array_diff = array_diff( $product_category_list, $settings['exclude_categories'] );
            $array_diff_count = count( $array_diff );

            if( ! ( $array_diff_count < $product_category_count ) ) {
                return true;
            }
            $product_category_list = [];
        }
        if( $settings['product_exclude_by'] == 'manual_selection' ) {
            if( ! in_array( $product_id, $settings['exclude_products'] ) ) {
                return true;
            }
        }

        return false;

    }


    public function _show_purchaseof( $product, $settings ){
        if( empty( $settings['product_control'] ) || $settings['product_control'] === 'none' ) {
            return true;
        }

        $product_category_list = [];

        $product_id = $product['product_id'];
        if( $settings['product_control'] == 'product_category' ) {
            $term = 'product_cat';
            if($settings['source'] == 'edd' || $settings['source'] == 'edd_inline'){
                $term = 'download_category';
            }
            $product_categories = get_the_terms( $product_id, $term );
            if(is_array($product_categories) && ! is_wp_error( $product_categories ) ) {
                foreach( $product_categories as $category ) {
                    $product_category_list[] = $category->slug;
                }
            }

            $product_category_count = count( $product_category_list );
            $array_diff = array_diff( $product_category_list, $settings['category_list'] );
            $array_diff_count = count( $array_diff );

            if( $array_diff_count < $product_category_count ) {
                return true;
            }

        }
        if( $settings['product_control'] == 'manual_selection' ) {
            if( in_array( $product_id, $settings['product_list'] ) ) {
                return true;
            }
        }

        return false;
    }
}
