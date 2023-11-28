<?php

namespace BetterLinksPro\Traits;

trait UTM {
    private function get_template($templates, $template_name) {
        $template_name_arr = array_column($templates, 'template_name');
        return array_search( $template_name, $template_name_arr );
    }
    private function check_template_exists($templates, $template_name) {
        $template_index = $this->get_template($templates, $template_name);
		return $template_index !== false;
    }
    private function check_campaign_exists($templates, $campaign_name) {
        $template_name_arr = array_column($templates, 'utm_campaign');
		return array_search( $campaign_name, $template_name_arr ) !== false;
    }

    public function check_fields_exists($templates, $utm_campaign, $template_name) {
        // Check if t campaign empty
        if( '' == $utm_campaign ) {
			return [
                'success' => false,
                'message' => esc_html__('Campaign field is empty', 'betterlinks-pro'),
            ];
		}

        // 👇 check if the campaign is already exists
		$is_campaign_exists = $this->check_campaign_exists($templates, $utm_campaign);
        if( $is_campaign_exists ) {
            return [
                'message' => esc_html__('Campaign already exists', 'betterlinks-pro'),
                'field_name' => 'utm_campaign',
            ];
		}

		// 👇 check if the template is already exists
		$is_template_exists = $this->check_template_exists($templates, $template_name);
		if( $is_template_exists ) {
			return [
                'message' => esc_html__('Template already exists', 'betterlinks-pro'),
                'field_name' => 'template_name',
            ];
		}
        return false;
    }
}