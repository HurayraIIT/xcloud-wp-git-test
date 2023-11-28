<?php
namespace BetterLinksPro\Admin;

class Metabox
{
    use \BetterLinks\Traits\Terms;
    use \BetterLinks\Traits\Links;
    use \BetterLinks\Traits\ArgumentSchema;

    private $link_options;
    const AFFILIATE_DISCLOSURE_ENABLE = 'betterlinks_enable_affiliate_link_disclosure';
    const AFFILIATE_DISCLOSURE_FIELDS = 'betterlinks_enable_affiliate_link_disclosure_text';

    public static function init()
    {
        $self = new self();
        $self->link_options = defined('BETTERLINKS_LINKS_OPTION_NAME') ? json_decode(get_option(BETTERLINKS_LINKS_OPTION_NAME), true) : [];
        add_action('add_meta_boxes', [$self, 'add_auto_keyword_metabox'], 10, 2);
        add_action('add_meta_boxes', [$self, 'add_auto_shotlink_metabox'], 10, 2);
        add_action('save_post', [$self, 'save_auto_keyword_metabox'], 10, 3);
        add_action('save_post', [$self, 'save_auto_create_links_metabox'], 10, 3);
        add_action('add_meta_boxes', [$self, 'add_affiliate_disclosure_metabox'], 10, 2);
        add_action('save_post', [$self, 'save_affiliate_disclosure_metabox'], 10, 2);
    }

    public function add_auto_keyword_metabox($post_type, $post)
    {
        $autolink_disable_post_types = isset($this->link_options["autolink_disable_post_types"]) && is_array($this->link_options["autolink_disable_post_types"]) ? $this->link_options["autolink_disable_post_types"] : [];
        if (in_array($post_type, $autolink_disable_post_types)) {
            return false;
        }
        add_meta_box('betterlinks-auto-keyword', __('BetterLinks Auto-Link Keywords', 'betterlinks'), [$this, 'auto_keyword_callback'], $post_type, 'side', 'core');
    }

    public function auto_keyword_callback($post)
    {
        $disable_auto_keyword = get_post_meta($post->ID, 'betterlinks_is_disable_auto_keyword', true); ?>
        <p>
            <label>
                <input 
                    type="checkbox" 
                    name="betterlinks_is_disable_auto_keyword" 
                    <?php checked(filter_var($disable_auto_keyword, FILTER_VALIDATE_BOOLEAN), true) ?> 
                />
                <?php esc_html_e('Disable Auto-Link Keywords', 'betterlinks-pro'); ?>
            </label>
        </p>
        <?php
    }

    public function save_auto_keyword_metabox($post_id, $post, $update)
    {
        $disable_auto_keyword = (isset($_POST['betterlinks_is_disable_auto_keyword']) ? filter_var(sanitize_text_field($_POST['betterlinks_is_disable_auto_keyword']), FILTER_VALIDATE_BOOLEAN) : false);
        update_post_meta($post_id, 'betterlinks_is_disable_auto_keyword', $disable_auto_keyword);
    }

    function generateRandomSlug($length = 3) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        $charactersLength = strlen($characters);
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength -1)];
        }
        $random_num = rand(0, 10) . rand(0, 10) . rand(0, 10);
        return $randomString . $random_num;
    }

    public function is_using_gutenberg_block() {
        $current_screen = get_current_screen();
        $is_using_block_editor = $current_screen->is_block_editor || (function_exists( 'is_gutenberg_page' ) && is_gutenberg_page());
        return $is_using_block_editor;
    }
    public function add_auto_shotlink_metabox($post_type, $post) {
        $auto_link_create_settings = json_decode(get_option('betterlinkspro_auto_link_create'), true);
        $allowed_post_type = array('post','page');
        if( 
            !$this->is_using_gutenberg_block() && 
            !empty($auto_link_create_settings['enable_auto_link']) && 
            ($post_type != 'product' && in_array($post_type, $allowed_post_type) && 
            $auto_link_create_settings[$post_type.'_shortlinks'] || 
            $post_type === 'product')
        ){
            add_meta_box('betterlinks-auto-shortlink', __('BetterLinks Auto-Create Links', 'betterlinks'), [$this, 'auto_create_link'], $post_type, 'side', 'core');
        }
    }

    public function get_betterlinks_prefix() {
        $betterlinks_links = get_option('betterlinks_links', []);
        if( is_string($betterlinks_links ) ) {
            $betterlinks_links = json_decode($betterlinks_links, true);
        }
        $prefix = !empty( $betterlinks_links['prefix'] ) ? $betterlinks_links['prefix'] . '/' : '';
        return $prefix;
    }
    public function auto_create_link($post) {
        $permalink = get_permalink($post->ID);
        $links = \BetterLinks\Helper::get_link_by_permalink($permalink);

        $is_disabled_current_post = false;

        $disable_ids = get_post_meta($post->ID, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS);
        if( is_array($disable_ids) && count($disable_ids) > 0 && in_array('1', $disable_ids)) {
            $is_disabled_current_post = true;
        }

        $auto_link_settings = get_option('betterlinkspro_auto_link_create', []);
        if( is_string($auto_link_settings) ) {
            $auto_link_settings = json_decode($auto_link_settings, true);
        }
        $default_category = isset($auto_link_settings[$post->post_type . '_default_cat'])  ? $auto_link_settings[$post->post_type . '_default_cat'] : '1';

        $random_slug = '';
        $saved_category = [];
        $saved_link = [];
        if(array_key_exists('0', $links) && !empty($links[0]['ID']) ) {
            $saved_link = $links[0];
            $saved_category = \BetterLinks\Helper::get_terms_by_link_ID_and_term_type($saved_link['ID'], 'category');

            if( array_key_exists('0', $saved_category) ) {
                $saved_category = $saved_category[0];
            }
            $random_slug = $saved_link['short_url'];
        }else {
            $prefix = $this->get_betterlinks_prefix();
            $random_slug = $prefix . $this->generateRandomSlug();
        }
        
        $args = [];
        $betterlinks_categories = $this->get_all_terms_data($args);
        $redirect_type = [
            [
                "value" => '307',
                "label" => __('307 (Temporary)', 'betterlinks-pro'),
            ],
            [
                "value" => '302',
                "label" => __('302 (Temporary)', 'betterlinks-pro'),
            ],
            [
                "value" => '301',
                "label" => __('301 (Permanent)', 'betterlinks-pro'),
            ],
            [
                "value" => 'cloak',
                "label" => __('Cloaked', 'betterlinks-pro'),
            ],
        ];
        ?>
        <div>
            <p>A BetterLink for this post will be generated on publish</p>
            <div class="betterlinks_auto_create_link_form" style="display: <?php echo $is_disabled_current_post ? 'none' : 'block'; ?>">
                <div class="betterlinks-form-group">
                    <?php echo site_url() . '/' ?>
                    <div style="display: flex; align-items: center;justify-content: space-between;">
                        <input 
                            type="text" 
                            name="betterlinks_auto_create_shortlinks" 
                            id="betterlinks_auto_create_shortlinks" 
                            value="<?php echo $random_slug; ?>"
                            data-short-url="<?php echo site_url() . '/' . $random_slug; ?>"
                            data-link-id="<?php echo array_key_exists('ID', $saved_link) ? $saved_link['ID'] : ''; ?>"
                        />
                        <span class="betterlinks betterlinks-copy dashicons dashicons-admin-page"></span>
                    </div>
                    <p style="color: red;display: none;" class="betterlinks-exists">Link already exists, try another ... </p>
                </div>
                <div class="betterlinks-form-group betterlinks-form-flex">
                    <label>BetterLinks Category</label>
                    <select name="betterlinks_auto_link_category">
                    <?php 
                        foreach ($betterlinks_categories as $key => $value) {
                            if( $value['term_type'] == 'category' ) {
                                
                        ?>
                            <option value="<?php echo $value['ID'] ?>" <?php echo (isset($saved_category['term_id']) && $saved_category['term_id'] == $value['ID']) || ($value['ID'] == $default_category) ? 'selected' : '' ?>><?php echo $value['term_name']; ?></option>
                        <?php
                            }
                        }
                    ?>
                    </select>
                </div>
                <div class="betterlinks-form-group betterlinks-form-flex">
                    <label>Redirect Type</label>
                    <select name="betterlinks_auto_link_redirect_type">
                    <?php 
                        foreach ($redirect_type as $key => $value) {
                        ?>
                            <option value="<?php echo $value['value'] ?>" <?php echo (isset($saved_link['redirect_type']) && $saved_link['redirect_type'] == $value['value']) ? 'selected' : '' ?>><?php echo $value['label']; ?></option>
                        <?php
                        }
                    ?>
                    </select>
                </div>
            </div>
            <label>
                <input 
                    type="checkbox" 
                    name="betterlinks_auto_create_link_disabled"
                    id="betterlinks_auto_create_link_disabled" 
                    <?php checked(filter_var($is_disabled_current_post, FILTER_VALIDATE_BOOLEAN), true) ?> 
                />
                <input 
                    type="hidden" 
                    name="betterlinks_al_disable_hidden"
                    id="betterlinks_al_disable_hidden"
                    value="loaded"
                />
                <?php esc_html_e('Disable Auto-Create Links on this post', 'betterlinks-pro'); ?>
            </label>
        </div>

        <script>
            const disableAutoLink = document.getElementById("betterlinks_auto_create_link_disabled");
            const autoLinkForm = document.querySelector(".betterlinks_auto_create_link_form");
            const copyButton = document.querySelector(".betterlinks-copy")
            const linksExists = document.querySelector(".betterlinks-exists")
            const autoLinkInput = document.getElementById("betterlinks_auto_create_shortlinks");
            const linkId = autoLinkInput?.dataset?.linkId;
            
            const copyToClipboard = (copyText) => {
                var tempInput = document.createElement('input');
                tempInput.value = copyText;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                return;
            };
            disableAutoLink.addEventListener("click", function(e) {
                const isChecked = e.target.checked;
                autoLinkForm.style.display = isChecked ? "none" : "block";
            })

            copyButton.addEventListener("click", function(e) {
                const shortUrl = autoLinkInput?.dataset?.shortUrl;
                copyToClipboard(shortUrl);
                const classLists = e.target.classList;
                classLists.remove('dashicons-admin-page');
                classLists.remove('betterlinks-copy');
                classLists.add('dashicons-yes');
                setTimeout(() => {
                    classLists.remove('dashicons-yes');
                    classLists.add('dashicons-admin-page');
                    classLists.add('betterlinks-copy');
                }, 1000);
            })
            
            async function shortURLUniqueCheckGutenberg(slug, ID) {
                const betterlinks_nonce = `<?php echo wp_create_nonce('betterlinks_admin_nonce'); ?>`;
                let form_data = new FormData();
                form_data.append('action', 'betterlinks/admin/short_url_unique_checker');
                form_data.append('security', betterlinks_nonce );
                form_data.append('ID', ID);
                form_data.append('slug', slug);
                try {
                    const response = await fetch(ajaxurl, {
                        method: 'POST',
                        body: form_data,
                    });

                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.log('--error is: ', error);
                }
            };
            autoLinkInput.addEventListener("keyup", function(e) {
                shortURLUniqueCheckGutenberg(e.target.value, linkId).then(data => {
                    linksExists.style.display = data.data ? "block" : "none";
                });
            });

        </script>
        <style>
            .betterlinks.dashicons {
                border: 1px solid gray;
                border-radius: 10%;
                padding: 4px;
                margin-left: 5px;
                cursor: pointer;
            }
            .betterlinks-form-group {
                margin-bottom:0.5rem;
            }
            .betterlinks-form-flex {
                display: flex;
                flex-direction: column;
            }
            #betterlinks_auto_create_shortlinks{
                flex-grow: 1;
            }
        </style>

        <?php
    }

    public function save_auto_create_links_metabox($post_id, $post) {
        $hidden = (isset($_POST['betterlinks_al_disable_hidden']) ? sanitize_text_field($_POST['betterlinks_al_disable_hidden']) : '');
        if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || $hidden == '' ) {
            return;
        }
        
        $defaults = [
            "ID" => "",
            "cat_id" => "",
            "short_url" => "",
            "redirect_type" => "307",
            "nofollow" => true,
            "param_forwarding" => false,
            "sponsored" => false,
            "track_me" => true,
            "link_status" => "publish",
            "dynamic_redirect" => [
                "type" => "",
                "value" => [],
                "extra" => [
                    "rotation_mode" => "weighted",
                    "split_test" => false,
                    "goal_link" => ""
                ]
            ],
            "expire" => [
                "status" => false,
                "type" => "date",
                "clicks" => "",
                "date" => "",
                "redirect_status" => false,
                "redirect_url" => ""
            ],
            "wildcards" => 0,
        ]; 

        $is_checked = (isset($_POST['betterlinks_auto_create_link_disabled']) ? filter_var(sanitize_text_field($_POST['betterlinks_auto_create_link_disabled']), FILTER_VALIDATE_BOOLEAN) : false);
        $is_checked = (isset($_POST['betterlinks_auto_create_link_disabled']) ? filter_var(sanitize_text_field($_POST['betterlinks_auto_create_link_disabled']), FILTER_VALIDATE_BOOLEAN) : false);
        $short_slug = (isset($_POST['betterlinks_auto_create_shortlinks']) ? sanitize_text_field($_POST['betterlinks_auto_create_shortlinks']) : '');
        $category = (isset($_POST['betterlinks_auto_link_category']) ? sanitize_text_field($_POST['betterlinks_auto_link_category']) : '');
        $redirect_type = (isset($_POST['betterlinks_auto_link_redirect_type']) ? sanitize_text_field($_POST['betterlinks_auto_link_redirect_type']) : '307');
        $prefix = $this->get_betterlinks_prefix();


        // fetching the saved links
        $permalink = get_permalink($post_id);
        $links = \BetterLinks\Helper::get_link_by_permalink($permalink);
        
        $ID = array_key_exists('0', $links) ? $links[0]['ID'] : '';
        if( !empty($ID) ) {
            $is_link_exists = \BetterLinks\Helper::get_link_by_short_url($short_slug, $ID);
            $exists_link = [];
            if( count($is_link_exists) > 0) {
                $exists_link = $is_link_exists[0];
                if( $exists_link['ID'] != $ID) {
                    return false;
                }
            }
        }

        if( !$is_checked && !$this->is_using_gutenberg_block() ){
            $args = wp_parse_args([
                "ID" => $ID,
                "short_url" => $short_slug,
                "cat_id" => $category,
                "redirect_type" => $redirect_type,
                "target_url" => $permalink,
                "link_title" => $post->post_title,
                "link_slug" => $post->post_name,
                "link_modified" => $post->post_modified,
                "link_modified_gmt" => $post->post_modified_gmt,
            ], $defaults);
            
            delete_transient( BETTERLINKS_CACHE_LINKS_NAME );

            $args = $this->sanitize_links_data($args);
            if( empty($args['ID'] )) {
                $this->insert_link($args);
            }else {
                // echo '<pre>';
                // var_dump('inserting');
                // die();
                $this->update_link($args);
            }
        }

        $disable_id = get_post_meta($post_id, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS);

        if( is_array($disable_id) && count($disable_id) > 0) {
            update_post_meta($post_id, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS, $is_checked);
        }else {
            add_post_meta($post_id, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS, $is_checked);
        }
    }

    public function add_affiliate_disclosure_metabox($post_type, $post) {
        if( 
            !$this->is_using_gutenberg_block() && 
            in_array($post_type, ['post', 'page']) && 
            !empty($this->link_options['affiliate_link_disclosure']) 
        ) {
            add_meta_box('betterlinks-affiliate-disclosure', __('BetterLinks Affiliate Disclosure', 'betterlinks-pro'), [$this, 'affiliate_disclosure'], $post_type, 'side');
        }
    }
    public function affiliate_disclosure($post) {
        $affiliate_disclosure_enabled = $this->affiliate_disclosure_enabled($post->ID);
        $is_affiliate_disclosure_enabled = in_array('true', $affiliate_disclosure_enabled);
        $affiliate_disclosure_data = $this->get_affiliate_disclosure_data($post->ID);
        $affiliate_text =  str_replace(' rn ','', $affiliate_disclosure_data['affiliate_disclosure_text']);
        ?>
        <div>
            <p><?php esc_html_e('This will allow you to add an Affiliate Link Disclosure in this '. $post->post_type, 'betterlinks-pro'); ?></p>

            <label>
                <input 
                    type="checkbox" 
                    name="betterlinks_affiliate_disclosure_enable"
                    id="betterlinks_affiliate_disclosure_enable"
                    value="<?php echo $is_affiliate_disclosure_enabled ? 'loaded' : 'disable'; ?>"
                    <?php checked(filter_var($is_affiliate_disclosure_enabled, FILTER_VALIDATE_BOOLEAN), true) ?> 
                />
                <input 
                    type="hidden" 
                    name="betterlinks_affiliate_hidden_field"
                    id="betterlinks_affiliate_hidden_field"
                    value="loaded"
                />
                <?php esc_html_e('Enable Affiliate Disclosure on this post', 'betterlinks-pro'); ?>
            </label>


                <div class="betterlinks-affiliate-link-disclosure" style="display: <?php echo $is_affiliate_disclosure_enabled ? 'block' : 'none'; ?>">
                
                    <div class="betterlinks-form-group betterlinks-form-flex">
                        <label><?php esc_html_e('Choose Affiliate Disclosure Position', 'betterlinks-pro') ?></label>
                        <select name="betterlinks_affiliate_disclosure_position" style="width: 100%;">
                            <option value="top" <?php echo sanitize_text_field( $affiliate_disclosure_data['affiliate_link_position'] ) === 'top' ? 'selected' : '' ?>>
                                <?php esc_html_e('Top', 'betterlinks-pro') ?>
                            </option>
                            <option value="bottom" <?php echo sanitize_text_field( $affiliate_disclosure_data['affiliate_link_position'] ) === 'bottom' ? 'selected' : '' ?>>
                                <?php esc_html_e('Bottom', 'betterlinks-pro') ?>
                            </option>
                            <option value="top-bottom" <?php echo sanitize_text_field( $affiliate_disclosure_data['affiliate_link_position'] ) === 'top-bottom' ? 'selected' : '' ?>>
                                <?php esc_html_e('Top & Bottom', 'betterlinks-pro') ?>
                            </option>
                        </select>
                    </div>
                    <div class="betterlinks-form-group betterlinks-form-flex">
                        <label><?php esc_html_e('Affiliate Disclosure Text', 'betterlinks-pro') ?></label>
                        <div class="affiliate_disclosure_text_box" style="border: 1px solid gray;border-radius: 5px; padding: 0px 5px;">
                            <?php echo wp_kses_post(  $affiliate_text ); ?>
                        </div>
                        <textarea class="affiliate_disclosure_text_area" name="affiliate_disclosure_text" style="width: 100%;display: none;height: 150px;"> 
                            <?php echo wp_kses_post(  $affiliate_text ); ?>
                        </textarea>
                        
                        <div class="affiliate_disclosure_btn">
                            <a href="#" id="affiliate_disclosure_edit_btn">Edit</a> |
                            <a href="#" id="affiliate_disclosure_save_btn">Save</a>
                        </div>
                    </div>
                </div>
            <div>
            
            </div>
        </div>
        <style>
            .betterlinks-affiliate-link-disclosure div {
                margin-bottom: 5px;
            }
            .affiliate_disclosure_btn {
                align-self: flex-start;
            }
        </style>
        <script>
            const affiliateDisclosureEnable = document.getElementById('betterlinks_affiliate_disclosure_enable');
            const affiliateForm = document.querySelector('.betterlinks-affiliate-link-disclosure');
            const affiliateTextEditBtn = document.getElementById('affiliate_disclosure_edit_btn');
            const affiliateTextSaveBtn = document.getElementById('affiliate_disclosure_save_btn');
            const affiliateTextBox = document.querySelector('.affiliate_disclosure_text_box');
            const affiliateTextArea = document.querySelector('.affiliate_disclosure_text_area');

            affiliateTextEditBtn.addEventListener('click', function(e) {
                e.preventDefault();
                affiliateTextBox.style.display = "none";
                affiliateTextArea.style.display = "block";
            })
            affiliateTextSaveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                affiliateTextBox.style.display = "block";
                affiliateTextArea.style.display = "none";
                affiliateTextBox.innerHTML = affiliateTextArea.value;
                // affiliateTextBox.appendChild(document.createNode(affiliateTextArea.value));
            })


            affiliateDisclosureEnable.addEventListener("click", function(e) {
                const isChecked = e.target.checked;
                e.target.value = isChecked ? 'checked' : 'disabled';
                affiliateForm.style.display = isChecked ? "block" : "none";
            })
        </script>
        <?php
    }
    public function save_affiliate_disclosure_metabox($post_id, $post) {
        $is_checked = (isset( $_POST['betterlinks_affiliate_disclosure_enable'] ) ? sanitize_text_field( $_POST['betterlinks_affiliate_disclosure_enable'] ) : '');
        $hidden = (isset( $_POST['betterlinks_affiliate_hidden_field'] ) ? sanitize_text_field( $_POST['betterlinks_affiliate_hidden_field'] ) : '');


        if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || $hidden === '' ) {
            return;
        }
        
        if( !in_array($is_checked , ['loaded', 'checked']) ){
            update_post_meta($post_id, self::AFFILIATE_DISCLOSURE_ENABLE , 'falsee');
            return;
        }

        $affiliate_disclosure_enabled = $this->affiliate_disclosure_enabled($post->ID);
        update_post_meta( $post_id, self::AFFILIATE_DISCLOSURE_ENABLE , in_array($is_checked, ['loaded', 'checked']) ? 'true' : 'falsee' );
        
        $position = (isset( $_POST['betterlinks_affiliate_disclosure_position'] ) ? sanitize_text_field( $_POST['betterlinks_affiliate_disclosure_position'] ) : '');
        $affiliate_disclosure_text = (isset( $_POST['affiliate_disclosure_text'] ) ? ($_POST['affiliate_disclosure_text']) : '');

        // error_log( $affiliate_disclosure_text );

        // affiliate disclosure data
        $get_affiliate_disclosure_data = get_post_meta( $post_id, self::AFFILIATE_DISCLOSURE_FIELDS );
        if( !$this->is_using_gutenberg_block() ) {
            $update_disclosure_fields = wp_json_encode([
                'affiliate_disclosure_text' => $affiliate_disclosure_text,
                'affiliate_link_position' => esc_html( $position )
            ]);
            
            if( !empty( $get_affiliate_disclosure_data ) ) {
                update_post_meta( $post_id, self::AFFILIATE_DISCLOSURE_FIELDS,  $update_disclosure_fields);
            }else {
                add_post_meta( $post_id, self::AFFILIATE_DISCLOSURE_FIELDS, $update_disclosure_fields );
            }
       }
    }
    private function affiliate_disclosure_enabled($post_id) {
        return get_post_meta( $post_id, self::AFFILIATE_DISCLOSURE_ENABLE );
    }

    private function get_affiliate_disclosure_data($post_id) {
        $affiliate_link_position = !empty( $this->link_options['affiliate_link_position'] ) ? sanitize_text_field( $this->link_options['affiliate_link_position'] ) : '';

        $affiliate_disclosure_text = (isset( $this->link_options['affiliate_disclosure_text'] ) ? $this->link_options['affiliate_disclosure_text'] : '');

        $get_affiliate_disclosure_saved_data = get_post_meta( $post_id, self::AFFILIATE_DISCLOSURE_FIELDS );


        $data = [];
        if( count( $get_affiliate_disclosure_saved_data) > 0 ) {
            $data = json_decode( html_entity_decode($get_affiliate_disclosure_saved_data[0]), true );
        }

        return [
            'affiliate_link_position' => empty( $data['affiliate_link_position'] ) ? $affiliate_link_position : sanitize_text_field( $data['affiliate_link_position'] ),
            'affiliate_disclosure_text' => empty( $data['affiliate_disclosure_text'] ) ? $affiliate_disclosure_text : $data['affiliate_disclosure_text']
        ];
    }
 }
