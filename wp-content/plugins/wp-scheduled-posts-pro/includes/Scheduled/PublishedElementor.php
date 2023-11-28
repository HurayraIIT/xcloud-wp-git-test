<?php

namespace WPSP_PRO\Scheduled;

use Elementor\Plugin;
use Elementor\Core\Base\Document;
use Elementor\Core\Settings\Manager as SettingsManager;

class PublishedElementor {
    public function __construct() {
        $delayed_schedule = \WPSP\Helper::get_settings('is_delayed_schedule_active');
        if(!($delayed_schedule !== null ? $delayed_schedule : true)){
            return;
        }
        // add_action('elementor/ajax/register_actions', [$this, 'schedule_published_content']);
        add_filter('elementor/document/save/data', [$this, 'save_data'], 10, 2);
        add_filter('elementor/document/config', [$this, 'document_config'], 10, 3);
        add_filter('elementor/frontend/builder_content_data', [$this, 'builder_content_data'], 10, 2);
        // $config = apply_filters('elementor/editor/localize_settings', $config);
        // $additional_config = apply_filters( 'elementor/document/config', $additional_config, $this->get_main_id() );
        add_action('wpscp_el_pending_schedule', [$this, 'wpscp_el_pending_schedule_fn'], 10);
        add_action('wpsp_el_action_before', [$this, 'wpsp_el_action_before'], 10);
        add_action('after_delete_post', [$this, 'delete_post'], 10);
        add_action('trashed_post', [$this, 'delete_post'], 10);
        add_filter('wpsp_admin_bar_menu_posts', [$this, 'admin_bar_menu_posts'], 10, 2);
        add_filter('wpsp_el_modal_post_date', [$this, 'el_modal_post_date'], 10, 2);
        add_action('wpscp_calender_the_post', [$this, 'calender_the_post'], 10);
        add_action('wpsp_el_modal_pro_fields', [$this, 'el_modal_pro_fields'], 10);
        add_action('wpsp_el_after_publish_button', [$this, 'after_publish_button'], 10);

        add_filter('display_post_states', function ($post_states, $post) {
            $scheduled = (array) get_post_meta($post->ID, 'wpscp_el_pending_schedule', true);
            if (!empty($scheduled['status'])) {
                if ('publish' === $post->post_status) {
                    $post_states['scheduled'] = _x('Advanced Scheduled', 'post status', 'wp-scheduled-posts-pro');
                }
            }
            return $post_states;
        }, 9, 2);
    }


    public function delete_post($pid) {
        wp_clear_scheduled_hook('wpscp_el_pending_schedule', array((int) $pid));
    }

    /**
     * Undocumented function
     *
     * @param \Elementor\Core\Common\Modules\Ajax\Module $module
     * @return void
     */
    public function schedule_published_content($module) {
        if (!empty($_REQUEST['editor_post_id'])) {
            $editor_post_id = absint($_REQUEST['editor_post_id']);

            Plugin::$instance->db->switch_to_post($editor_post_id);
        }
    }

    /**
     * Undocumented function
     *
     * @param array                         $data The document data.
     * @param Document $this The document instance.
     * @return array
     */
    public function save_data($data, $document) {
        $is_advanced = $document->get_meta('wpscp_el_pending_schedule');
        if (isset($is_advanced['date_gmt'], $data["settings"]["post_status"]) && 'publish' === $data["settings"]["post_status"]) {
            $document->update_meta('wpscp_el_pending_schedule_data', $data);

            $published_post                = get_post( $document->get_post()->ID );
            $published_post->post_date_gmt = $is_advanced['post_time'];
            $published_post->post_date     = $is_advanced['date_gmt'];
            do_action('wpsp_transition_post_status', 'delayed_future', 'publish', $published_post);

            $return_data = [
                "success" => true,
                "data" => [
                    "responses" => [
                        "save_builder" => [
                            "success" => true,
                            "code" => 200,
                            "data" => [
                                'status' => $document->get_post()->post_status,
                                'config' => [
                                    'document' => [
                                        'last_edited' => $document->get_last_edited(),
                                        'urls' => [
                                            'wp_preview' => $document->get_wp_preview_url(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            wp_send_json($return_data);
        }
        return $data;
    }
    public function document_config($additional_config, $id, $config = []) {
        $is_advanced   = get_post_meta($id, 'wpscp_el_pending_schedule', true);
        $schedule_data = get_post_meta($id, 'wpscp_el_pending_schedule_data', true);
        if (!empty($is_advanced['post_time']) && !empty($schedule_data)) {
            $additional_config["settings"] = [];
            $settings                      = &$additional_config["settings"];
            $settings["controls"]          = [];
            $controls                      = &$settings["controls"];

            $document = Plugin::$instance->documents->get($id);
            $additional_config['elements'] = $document->get_elements_raw_data($schedule_data['elements'], true);
            $settings['settings']          = $schedule_data['settings'];
            $__settings = SettingsManager::get_settings_managers_config();



            // Avoid save empty post title.
            if (!empty($schedule_data['settings']['post_title'])) {
                $controls["post_title"]["default"] = $schedule_data['settings']['post_title'];
            }
            // if ( ! empty( $schedule_data['settings']['hide_title'] ) ) {
            //     $controls["hide_title"]["default"] = $schedule_data['settings']['hide_title'];
            // }

            if (isset($schedule_data['settings']['post_excerpt'])) {
                $controls["post_excerpt"]["default"] = $schedule_data['settings']['post_excerpt'];
            }

            if (isset($schedule_data['settings']['post_status'])) {
                $controls["post_status"]["default"] = $schedule_data['settings']['post_status'];
            }

            if (isset($schedule_data['settings']['post_featured_image'])) {
                $controls["post_featured_image"]["default"] = $schedule_data['settings']['post_featured_image'];
            }


            if (isset($schedule_data['settings']['template'], $__settings['page']['controls']['template'])) {
                $controls["template"]["default"] = $schedule_data['settings']['template'];
            }


            // wp_send_json($is_advanced);
        }
        return $additional_config;
    }

    public function builder_content_data($data, $post_id) {
        if (is_preview() || Plugin::$instance->preview->is_preview_mode()) {
            $is_advanced   = get_post_meta($post_id, 'wpscp_el_pending_schedule', true);
            $schedule_data = get_post_meta($post_id, 'wpscp_el_pending_schedule_data', true);
            if (!empty($schedule_data)) {
                $data          = $schedule_data['elements'];

                add_filter('elementor/frontend/builder_content/before_print_css', function ($with_css) {
                    return true;
                });
                add_filter("get_post_metadata", function ($return, $object_id, $meta_key, $single, $meta_type) use ($post_id, $data) {
                    if ('_elementor_data' == $meta_key && $object_id == $post_id) {
                        return json_encode($data);
                    }
                    return $return;
                }, 10,  5);
            }
            // $data = $document->get_elements_raw_data( $schedule_data['elements'], true );
        }
        return $data;
    }

    public function wpscp_el_pending_schedule_fn($pid) {
        $is_advanced   = get_post_meta($pid, 'wpscp_el_pending_schedule', true);
        $schedule_data = get_post_meta($pid, 'wpscp_el_pending_schedule_data', true);
        if (!empty($is_advanced['user_id'])) {
            remove_filter('elementor/document/save/data', [$this, 'save_data']);
            // @todo maybe do something.
            $user = wp_get_current_user();
            if (empty($user->ID)) {
                $user = wp_set_current_user($is_advanced['user_id']);
            }

            $document = Plugin::$instance->documents->get($pid);
            $document->save($schedule_data);
            delete_post_meta($pid, 'wpscp_el_pending_schedule');
            delete_post_meta($pid, 'wpscp_el_pending_schedule_data');

            // do_action('wpsp_publish_future_post', $pid);
            do_action('wpsp_transition_post_status', 'delayed_publish', get_post_status($pid), $pid);
        }
    }

    public function wpsp_el_action_before($args) {
        $id = $args['id'];
        if ($args['advanced'] == 'true') {
            $args['post_status'] = get_post_status($id);

            if ($args['post_status'] === 'publish') {
                $date_gmt = get_gmt_from_date($args['date']);
                wp_clear_scheduled_hook('wpscp_el_pending_schedule', array((int) $id));
                wp_schedule_single_event(strtotime($date_gmt), 'wpscp_el_pending_schedule', array((int) $id));

                $user_id = get_current_user_id();
                update_post_meta($id, 'wpscp_el_pending_schedule', [
                    'id'        => $id,
                    'status'    => 'future',
                    'post_time' => $args['date'],
                    'date_gmt'  => $date_gmt,
                    'user_id'   => $user_id,
                ]);
                wp_send_json_success([
                    'id'        => $id,
                    'status'    => $args['post_status'],
                    'post_time' => $args['date'],
                    'date_gmt'  => $date_gmt,
                    'msg'       => "Success",
                    'advanced'  => true,
                ]);
            }
        } else {
            $this->wpscp_el_pending_schedule_fn($id);
            wp_clear_scheduled_hook('wpscp_el_pending_schedule', array((int) $id));
        }
    }

    public function admin_bar_menu_posts($posts, $post_types) {
        $published = get_posts(array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'order'          => 'ASC',
            'meta_key'       => 'wpscp_el_pending_schedule',
        ));
        foreach ($published as $key => $post) {
            $scheduled = (array) get_post_meta($post->ID, 'wpscp_el_pending_schedule', true);
            if (!empty($scheduled['date_gmt'])) {
                $post->post_date     = $scheduled['post_time'];
                $post->post_date_gmt = $scheduled['date_gmt'];
            } else {
                unset($published[$key]);
            }
        }
        $posts = array_merge($posts, $published);
        return $posts;
    }

    public function calender_the_post() {
        global $post;
        $scheduled = (array) get_post_meta(get_the_ID(), 'wpscp_el_pending_schedule', true);
        if (isset($scheduled['status'], $scheduled['post_time'], $scheduled['date_gmt'])) {
            $post->post_status       = 'future';
            $post->post_date         = $scheduled['post_time'];
            $post->post_date_gmt     = $scheduled['date_gmt'];
            $post->post_modified     = $scheduled['post_time'];
            $post->post_modified_gmt = $scheduled['date_gmt'];
        }
    }

    public function el_modal_post_date($post_date, $post) {
        $is_advanced = get_post_meta($post->ID, 'wpscp_el_pending_schedule', true);
        $post_date   = !empty($is_advanced['post_time']) ? $is_advanced['post_time'] : $post->post_date;

        return $post_date;
    }

    public function after_publish_button($post) {
        $status           = get_post_status( $post->ID );
        $is_advanced      = get_post_meta($post->ID, 'wpscp_el_pending_schedule', true);

        if(class_exists('WPSP_PRO')):?>
            <button
                class="elementor-button wpsp-advanced-schedule"
                data-status="<?php echo $status;?>"
                data-is-advanced="<?php echo (bool) $is_advanced;?>"
                data-label-schedule="<?php esc_html_e( 'Advanced Schedule', 'wp-scheduled-posts' ); ?>"
                data-label-update="<?php esc_html_e( 'Update', 'wp-scheduled-posts' ); ?>"
                style="<?php echo 'display: none;'; ?>">
                <span class="elementor-state-icon">
                    <i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
                </span>
                <span>
                    <?php esc_html_e( 'Advanced Schedule', 'wp-scheduled-posts' ); ?>
                </span>
            </button>

        <?php endif;

    }

    public function el_modal_pro_fields($post) {

        echo '<input type="hidden" name="advanced" id="advanced" value="">';

    }
}
