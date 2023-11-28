<?php

namespace BetterLinksPro\Frontend;

class AutoLinks
{
    private $protected_tags_content_lists = [];
    private $unique_number;
    private $empty_placeholder;
    private $is_show_icon = false;
    public static function init()
    {
        $self = new self();
        global $betterlinks;
        $self->is_show_icon = isset($betterlinks["is_autolink_icon"]) ? $betterlinks["is_autolink_icon"] : false;
        if ($self->is_show_icon) {
            add_action('wp_head', [$self, 'autolink_css']);
        }
        add_filter('the_content', [$self, 'add_autolinks'], 100);
        add_filter('get_the_excerpt', [$self, 'add_autolinks'], 100);
    }

    public function add_autolinks($content)
    {
        if(!(strpos($content, 'class="btl_autolink_hyperlink" ') === false)){
            return $content;
        }
        if (is_attachment() || is_feed()) {
            return $content;
        }
        $ID = get_the_ID();
        $post_type = get_post_type($ID);
        global $betterlinks;
        $autolink_disable_post_types = isset($betterlinks["autolink_disable_post_types"]) && is_array($betterlinks["autolink_disable_post_types"]) 
                    ? $betterlinks["autolink_disable_post_types"] 
                    : [];
        if (in_array($post_type, $autolink_disable_post_types) || get_post_meta($ID, 'betterlinks_is_disable_auto_keyword', true)) {
            return $content;
        }
        $this->unique_number = wp_rand(0, 99999);
        // placeholder variables
        $btl_plc_space = '_spt_' . $this->unique_number . '_s___pt_' . $this->unique_number;
        $btl_plc_slash = '_slsh_' . $this->unique_number . '_sl___sh_' . $this->unique_number;
        $btl_plc_colon = '_slcln_' . $this->unique_number . '_slc___ln_' . $this->unique_number;
        $btl_plc_svg = '_svgln_' . $this->unique_number . '_svg___ln_' . $this->unique_number;
        $this->empty_placeholder = '_mptbtl_' . $this->unique_number . '_mpt___btl_';
        $current_permalink = get_the_permalink();
        $post_category = get_the_category($ID);
        $post_category = (!empty($post_category) ? wp_list_pluck($post_category, 'slug') : []);
        $post_tags = get_the_tags($ID);
        $post_tags = (!empty($post_tags) ? wp_list_pluck($post_tags, 'slug') : []);
        $keywords = $this->get_keywords();
        $content = $this->prevent_protected_tags_contents_from_getting_autolinked($content);
        $content = $this->prevent_all_tag_attributes_from_getting_autolinked($content);
        $should_use_json_for_link = defined('BETTERLINKS_EXISTS_LINKS_JSON') && BETTERLINKS_EXISTS_LINKS_JSON && count($keywords) > 0;
        $links_formatted_by_id = [];
        if($should_use_json_for_link){
            $links = isset($betterlinks['links']) ? $betterlinks['links'] : [];
            $ids = array_column($links, 'ID');
            $links_formatted_by_id = array_combine($ids, $links);
        }
        $uncloaked_categories = isset($betterlinks["uncloaked_categories"]) && is_array($betterlinks["uncloaked_categories"]) 
                                    ? array_map('intval', $betterlinks["uncloaked_categories"])
                                    : [];
        $uncloaked_cats_count = count($uncloaked_categories);
        $is_force_https = isset($betterlinks['force_https']) && $betterlinks['force_https'];
        foreach ($keywords as $item) {
            if (
                // check keyword and link id not empty
                (empty($item['keywords']) && empty($item['link_id']))
                // check post type
                || (!empty($item['post_type']) && !in_array($post_type, $item['post_type']))
                // check category
                || (!empty($item['category']) && count(array_intersect($post_category, $item['category'])) === 0)
                // check tags
                || (!empty($item['tags']) && count(array_intersect($post_tags, $item['tags'])) === 0)
            ) {
                continue;
            }
            if($should_use_json_for_link){
                $link_id = $item['link_id'];
                $link = isset($links_formatted_by_id[$link_id]) ? $links_formatted_by_id[$link_id] : [] ;
            }else{
                $link = $uncloaked_cats_count > 0 
                            ? current(\BetterLinks\Helper::get_link_data_with_cat_id_by_link_id($item['link_id']))
                            : current(\BetterLinks\Helper::get_link_by_ID($item['link_id']));
            }
            if (
                // check if shortlink exist
                !isset($link['short_url']) ||
                // check if target_url exist
                !isset($link['target_url']) ||
                // check if in the same page as the target url
                $this->make_url_string_comparable($link['target_url']) == $this->make_url_string_comparable($current_permalink)
            ) {
                continue;
            }
            $tags = $this->fix_for_apostophie($item['keywords']);
            $autolink_url = "";
            if((isset($link['uncloaked']) && $link['uncloaked']) || (isset($link['cat_id']) && in_array(intval($link['cat_id']), $uncloaked_categories))){
                if(parse_url($link['target_url'], PHP_URL_SCHEME) === null){
                    $autolink_url = $is_force_https 
                    ? 'https://' . $link['target_url'] 
                    : 'http://' . $link['target_url'];
                }else{
                    $autolink_url = $is_force_https 
                    ? preg_replace("/^https?:\/\//i", "https://", $link['target_url'])
                    : $link['target_url'];
                }
            }else{
                $autolink_url = \BetterLinks\Helper::generate_short_url($link['short_url']);
            }
            $autolink_url = str_replace(["/", ":"], [$btl_plc_slash, $btl_plc_colon], $autolink_url);
            $search_mode = 'iu';
            if ($item['case_sensitive'] == true) {
                $search_mode = 'u';
            }
            $attribute = $this->get_link_attributes($item);
            $keyword_before = (!empty($item['keyword_before']) ? $this->fix_for_apostophie($item['keyword_before']) : '');
            $keyword_after = (!empty($item['keyword_after']) ? $this->fix_for_apostophie($item['keyword_after']) : '');
            $left_boundary = (!empty($item['left_boundary']) ? $this->get_boundary($item['left_boundary']) : '');
            $right_boundary = (!empty($item['right_boundary']) ? $this->get_boundary($item['right_boundary']) : '');
            $limit = (int) (!empty($item['limit']) ? $item['limit'] : 100);
            // step 1: added placeholder
            $content = preg_replace_callback(
                '/\b(' . $keyword_before . ')(' . $left_boundary . ')(' . $tags . ')(' . $right_boundary . ')(' . $keyword_after . ')\b/' . $search_mode,
                function($match) use ($btl_plc_space, $autolink_url, $attribute, $btl_plc_svg) {
                    return $match[1] . $match[2] . 
                    '<a ' .
                    $btl_plc_space .
                    'class="' . $this->empty_placeholder . 'btl_autolink_hyperlink" ' .
                    $btl_plc_space .
                    'href="' . $autolink_url . '" ' .
                    $btl_plc_space .
                    $attribute . '>' .
                    $btl_plc_svg .
                    $match[3] . $this->empty_placeholder . 
                    '</a>' .
                    $match[4] . $match[5];
                },
                $content,
                $limit
            );
        }
        $content = $this->put_back_protected_tags_contents($content);
        $content = $this->put_back_all_tag_attributes($content);
        $hyperlink_icon_svg = $this->is_show_icon 
                                ? ' <svg class="btl_autolink_icon_svg" enable-background="new 0 0 64 64"  viewBox="0 0 64 64"  xmlns="http://www.w3.org/2000/svg"><g><g ><g><path d="m36.243 29.758c-.16 0-1.024-.195-1.414-.586-3.119-3.119-8.194-3.12-11.314 0-.78.781-2.048.781-2.828 0-.781-.781-.781-2.047 0-2.828 4.679-4.68 12.292-4.679 16.97 0 .781.781.781 2.047 0 2.828-.39.391-.903.586-1.414.586z"/></g></g><g ><g><path d="m34.829 41.167c-3.073 0-6.146-1.17-8.485-3.509-.781-.781-.781-2.047 0-2.828.78-.781 2.048-.781 2.828 0 3.119 3.119 8.194 3.12 11.314 0 .78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828-2.34 2.339-5.413 3.509-8.485 3.509z"/></g></g><g ><g><path d="m41.899 38.243c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828l11.172-11.172c.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828l-11.172 11.172c-.39.391-.902.586-1.414.586z"/></g></g><g ><g><path d="m25.071 55.071c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828l6.245-6.245c.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828l-6.245 6.245c-.39.391-.902.586-1.414.586z"/></g></g><g ><g><path d="m10.929 40.929c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828l11.172-11.171c.781-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828l-11.172 11.171c-.391.39-.903.586-1.414.586z"/></g></g><g ><g><path d="m32.684 19.175c-.16 0-1.023-.195-1.414-.585-.781-.781-.781-2.047 0-2.829l6.245-6.246c.781-.781 2.047-.781 2.829 0 .781.781.781 2.047 0 2.829l-6.245 6.246c-.391.389-.904.585-1.415.585z"/></g></g><g ><g><path d="m18 57.935c-3.093 0-6.186-1.15-8.485-3.45-4.6-4.6-4.6-12.371 0-16.971.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828-3.066 3.066-3.066 8.248 0 11.314s8.248 3.066 11.314 0c.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828-2.299 2.301-5.392 3.451-8.485 3.451z"/></g></g><g ><g><path d="m53.071 27.071c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828 3.066-3.066 3.066-8.248 0-11.314s-8.248-3.066-11.314 0c-.78.781-2.048.781-2.828 0-.781-.781-.781-2.047 0-2.828 4.6-4.6 12.371-4.6 16.971 0s4.6 12.371 0 16.971c-.391.39-.903.585-1.415.585z"/></g></g></g></svg>' 
                                : '';
        // step 3: remove unnecessary strings
        $content = str_replace([
            $this->empty_placeholder,
            $btl_plc_space,
            $btl_plc_colon,
            $btl_plc_slash,
            $btl_plc_svg,
        ], [
            '',
            ' ',
            ':',
            '/',
            $hyperlink_icon_svg
        ], $content);
        return $content;
    }

    public function get_keywords()
    {
        $keywords = \BetterLinks\Helper::get_keywords();
        $keywords = $this->prepare_keywords($keywords);
        return $keywords;
    }

    public function prepare_keywords($keywords)
    {
        if (is_array($keywords)) {
            foreach ($keywords as $key => &$value) {
                $temp = json_decode($value, true);
                $tags = $this->keywords_to_tags_generator($temp['keywords']);
                $temp['keywords'] = $tags;
                $value = $temp;
            }
        }
        return $keywords;
    }

    public function keywords_to_tags_generator($string)
    {
        $string = trim($string);
        $string = preg_replace('/\,\s+|,+/', '|', $string);
        return $string;
    }
    public function get_boundary($data)
    {
        $boundary = '';
        switch ($data) {
            case 'generic':
                $boundary = '\b';
                break;

            case 'whitespace':
                $boundary = '\b \b';
                break;

            case 'comma':
                $boundary = ',';
                break;

            case 'point':
                $boundary = '\.';
                break;

            case 'none':
                $boundary = '';
                break;
        }
        return $boundary;
    }
    public function get_link_attributes($item)
    {
        // $this->empty_placeholder added to make it unique string so that, strings like 'target','rel','nofollow' don't get autolinked/hyperlinked
        $attribute = ' ';
        if ($item['open_new_tab'] == true) {
            $attribute .= ' ' . $this->empty_placeholder . 'target="' . $this->empty_placeholder . '_blank"';
            
        }
        if ($item['use_no_follow'] == true) {
            $attribute .= ' ' . $this->empty_placeholder . 'rel="' . $this->empty_placeholder . 'nofollow"';
        }
        return $attribute;
    }
    public function prevent_protected_tags_contents_from_getting_autolinked($content)
    {
        global $betterlinks;
        $autolink_in_heading_regex_part = isset($betterlinks['is_autolink_headings']) && !$betterlinks['is_autolink_headings'] 
            ? '|<h[1-6][^>]*?>[\s\S]*?<\/h[1-6]>' 
            : '';
        $content = preg_replace_callback(
            '/<a[^>]*?>[\s\S]*?<\/a>'.
            '|<img[^>]*?>' . 
            '|<script[^>]*?>[\s\S]*?<\/script>' . 
            $autolink_in_heading_regex_part . 
            '/u',
            array($this, 'replace_protected_tags_and_contents_by_placeholder'),
            $content
        );
        return $content;
    }
    public function prevent_all_tag_attributes_from_getting_autolinked($content)
    {
        $content = preg_replace_callback(
            '/<[^>]*?>/u',
            array($this, 'replace_protected_tags_and_attribute_only_by_placeholder'),
            $content
        );
        return $content;
    }

    public function replace_protected_tags_and_contents_by_placeholder($match)
    {
        $position = count($this->protected_tags_content_lists);
        array_push($this->protected_tags_content_lists, $match[0]);
        return '[alkpt]' . $position . '[/alkpt]';
    }
    public function replace_protected_tags_and_attribute_only_by_placeholder($match)
    {
        $position = count($this->protected_tags_content_lists);
        array_push($this->protected_tags_content_lists, $match[0]);
        return '[alkpta]' . $position . '[/alkpta]';
    }
    public function put_back_protected_tags_contents($content)
    {
        $content = preg_replace_callback(
            '/\[alkpt\](\d+)\[\/alkpt\]/u',
            array($this, 'replace_placeholders_with_the_stored_tags_contents'),
            $content
        );
        return $content;
    }
    public function put_back_all_tag_attributes($content)
    {
        $content = preg_replace_callback(
            '/\[alkpta\](\d+)\[\/alkpta\]/u',
            array($this, 'replace_placeholders_with_the_stored_tag_attributes'),
            $content
        );
        return $content;
    }
    public function replace_placeholders_with_the_stored_tags_contents($match)
    {
        return $this->protected_tags_content_lists[$match[1]];
    }
    public function replace_placeholders_with_the_stored_tag_attributes($match)
    {
        return $this->protected_tags_content_lists[$match[1]];
    }
    public function autolink_css()
    {
?>
        <style>
            a.btl_autolink_hyperlink {
                position: relative !important;
                padding: 0 0 0 22px !important;
                display: inline-block;
            }

            svg.btl_autolink_icon_svg {
                width: 16px !important;
                height: 16px !important;
                left: 4px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                position: absolute !important;
            }
        </style>
<?php
    }
    public function make_url_string_comparable($url_string = "")
    {
        return rtrim(strtolower(preg_replace('/https?:\/\//i', '', $url_string)), "/");
    }
    public function fix_for_apostophie($tags = "")
    {
        return preg_replace("/\’|\‘|\'|\&\#8217\;|\&\#8219\;/", "(?:[\'\’\‘]|\&\#8217\;|\&\#8219\;)", $tags);
    }
}
