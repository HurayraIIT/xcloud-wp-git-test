<?php

namespace WPDeveloper\BetterDocs\Core;

use WP_Error;
use WP_User;
use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Database;
use WPDeveloper\BetterDocs\Admin\Builder\Rules;
use WPDeveloper\BetterDocs\Admin\Builder\GlobalFields;

class Settings extends Base {
    protected $base_key = 'betterdocs_settings';

    /**
     * Database class
     * @var Database
     */
    protected $database;

    private $deprecated = [];

    private $cannot_be_empty = [
        'breadcrumb_doc_title',
        'docs_slug',
        'category_slug',
        'tag_slug',
        'permalink_structure',
        'docs_page'
    ];

    public function __construct( Database $database ) {
        $this->database   = $database;
        $this->deprecated = $this->deprecated_settings();

        add_action( 'admin_enqueue_scripts', [$this, 'enqueue'] );

        if ( isset( $_GET['page'] ) && $_GET['page'] === 'betterdocs-settings' && ! has_action( 'betterdocs_settings_header' ) ) {
            add_action( 'betterdocs_settings_header', [$this, 'header'] );
        }

        add_action( 'wp_ajax_betterdocs_dark_mode', [$this, 'dark_mode'] );
        add_filter( 'betterdocs_settings_tab_advance', [ $this, 'hide_roles_management'], 11, 1 );
        add_action( 'betterdocs::settings::saved', [$this, 'fallback_slugs'], 99, 3 );
    }

    public function fallback_slugs($_saved, $_settings, $_old_settings = []){
        $_default = $this->get_default();
        foreach( $this->cannot_be_empty as $key ) {
            if( $key === 'docs_page' && ! $_settings['builtin_doc_page'] && empty( $_settings[ $key ] ) ) {
                $this->save( 'builtin_doc_page', true );
                continue;
            }

            if( empty( $_settings[ $key ] ) ) {
                $this->save( $key, $_default[ $key ] );
            }
        }
    }

    /**
     * Get the settings URL for admin
     * @return string
     */
    public function url() {
        return esc_url( admin_url( 'admin.php?page=betterdocs-settings' ) );
    }

    /**
     * This method is responsible for enqueueing scripts in settings panel
     *
     * @since 2.5.0
     * @param string $hook
     *
     * @return void
     */
    public function enqueue( $hook ) {
        if ( $hook !== 'betterdocs_page_betterdocs-settings' ) {
            return;
        }

        betterdocs()->assets->enqueue( 'betterdocs-settings', 'admin/css/settings.css' );
        betterdocs()->assets->enqueue( 'betterdocs-settings', 'admin/js/settings.js' );
        betterdocs()->assets->localize(
            'betterdocs-settings',
            'betterdocsAdminSettings',
            GlobalFields::normalize( $this->settings_args() )
        );
    }

    /**
     * This method is responsible for printing header in dashboard settings page.
     *
     * @since 2.5.0
     * @param string $hook
     *
     * @return void
     */
    public function header( $hook ) {
        if ( $hook !== 'settings' ) {
            return;
        }

        betterdocs()->views->get( 'admin/template-parts/settings-header' );
    }

    /**
     * A list of deprecated settings keys.
     *
     * @since 2.5.0
     * @return array
     */
    public function deprecated_settings() {
        return [];
    }

    /**
     * Dynamic migration caller.
     *
     * @since 2.5.0
     * @return void
     */
    public function migration( $version ) {
        if( $version > 250 ) {
            for ( $v = 250; $v <= $version; $v++ ) {
                $_func = "v{$v}";
                if ( method_exists( $this, $_func ) ) {
                    call_user_func([$this, $_func]);
                }
            }
        }
    }

    public function v253(){
        $this->fallback_slugs(true, $this->get_all());
    }

    /**
     * Migration for version 2.5.0
     *
     * @since 2.5.0
     * @return void
     */
    public function v250() {
        if ( $this->get( 'alphabetically_order_term', false ) ) {
            $this->save( 'terms_orderby', 'name' );
        }

        if ( $orderby = $this->get( 'alphabetically_order_post', false ) ) {
            if ( $orderby === '1' ) {
                $this->save( 'alphabetically_order_post', 'title' );
            }
        }
    }

    /**
     * A list of default settings data.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_default() {
        $_default = [
            'multiple_kb'                => '',
            'builtin_doc_page'           => true,
            'breadcrumb_doc_title'       => __( 'Docs', 'betterdocs' ),
            'docs_slug'                  => 'docs',
            'docs_page'                  => 0,
            'category_slug'              => 'docs-category',
            'tag_slug'                   => 'docs-tag',
            'permalink_structure'        => 'docs/',
            'enable_faq_schema'          => false,
            'live_search'                => true,
            'advance_search'             => false,
            'popular_keyword_limit'      => 5,
            'search_letter_limit'        => 3,
            'search_placeholder'         => __( 'Search...', 'betterdocs' ),
            'search_not_found_text'      => __( 'Sorry, no docs were found.', 'betterdocs' ),
            'search_result_image'        => true,
            'masonry_layout'             => true,
            'terms_orderby'              => 'betterdocs_order',
            'alphabetically_order_term'  => false,
            'terms_order'                => 'ASC',
            'alphabetically_order_post'  => 'betterdocs_order',
            'docs_order'                 => 'ASC',
            'nested_subcategory'         => false,
            'column_number'              => 3,
            'posts_number'               => 10,
            'post_count'                 => true,
            'count_text'                 => __( 'docs', 'betterdocs' ),
            'count_text_singular'        => __( 'doc', 'betterdocs' ),
            'exploremore_btn'            => true,
            'exploremore_btn_txt'        => __( 'Explore More', 'betterdocs' ),
            'doc_single'                 => 1,
            'enable_toc'                 => true,
            'toc_title'                  => __( 'Table of Contents', 'betterdocs' ),
            'toc_hierarchy'              => true,
            'toc_list_number'            => true,
            'toc_dynamic_title'          => false,
            'enable_sticky_toc'          => true,
            'sticky_toc_offset'          => 100,
            'collapsible_toc_mobile'     => false,
            'supported_heading_tag'      => [1, 2, 3, 4, 5, 6],
            'enable_post_title'          => true,
            'title_link_ctc'             => true,
            'enable_breadcrumb'          => true,
            'breadcrumb_home_text'       => __( 'Home', 'betterdocs' ),
            'breadcrumb_home_url'        => get_home_url(),
            'enable_breadcrumb_category' => true,
            'enable_breadcrumb_title'    => true,
            'enable_sidebar_cat_list'    => true,
            'enable_print_icon'          => true,
            'enable_tags'                => true,
            'email_feedback'             => true,
            'feedback_link_text'         => __( 'Still stuck? How can we help?', 'betterdocs' ),
            'feedback_url'               => '',
            'feedback_form_title'        => __( 'How can we help?', 'betterdocs' ),
            'email_address'              => get_option( 'admin_email' ),
            'show_last_update_time'      => true,
            'enable_navigation'          => true,
            'enable_comment'             => true,
            'enable_credit'              => true,
            'enable_archive_sidebar'     => true,
            'archive_nested_subcategory' => true,
            'enable_content_restriction' => false,
            'enable_reporting'           => false,
            'reporting_day'              => 'monday',
            'reporting_email'            => get_option( 'admin_email' )
        ];

        $_default = apply_filters( 'betterdocs_default_settings', $_default );
        // $_default = apply_filters_deprecated(
        //     'betterdocs_option_default_settings', [$_default], '2.5.0',
        //     'betterdocs_default_settings', 'betterdocs_option_default_settings will be removed from v3.5.0.'
        // );

        return $_default;
    }

    /**
     * A list of default settings for pro plugins.
     *
     * @since 2.5.0
     * @return array
     */
    public function get_pro_defaults() {
        return [];
    }

    /**
     * Get customizer links for docs page.
     *
     * @since 1.0.0
     * @return string
     */
    public function customizer_link() {
        $query['autofocus[panel]'] = 'betterdocs_customize_options';
        $query['return']           = admin_url( 'edit.php?post_type=docs' );
        $builtin_doc_page          = betterdocs()->settings->get( 'builtin_doc_page' );
        $docs_slug                 = betterdocs()->settings->get( 'docs_slug' );
        $docs_page                 = betterdocs()->settings->get( 'builtin_doc_page' );

        if ( $builtin_doc_page == 1 && $docs_slug ) {
            $query['url'] = site_url( '/' . $docs_slug );
        } elseif ( $builtin_doc_page != 1 && $docs_page ) {
            $post_info    = get_post( $docs_page );
            $query['url'] = site_url( '/' . $post_info->post_name );
        }

        return add_query_arg( $query, admin_url( 'customize.php' ) );
    }

    /**
     * Get All Roles
     * dynamically
     *
     * @return array
     */
    public function get_roles() {
        $roles = wp_roles()->role_names;
        unset( $roles['subscriber'] );
        return $roles;
    }

    /**
     * Set dark mode
     *
     * @since 1.0.0
     * @return void
     */
    public function dark_mode() {
        if ( ! check_ajax_referer( 'doc_cat_order_nonce', 'nonce', false ) ) {
            wp_send_json_error();
        }

        if ( isset( $_POST['mode'] ) ) {
            if ( $this->save( 'dark_mode', rest_sanitize_boolean( $_POST['mode'] ) ) ) {
                wp_send_json_success();
            }
        }

        wp_send_json_error();
    }

    public function get_normalized_value( $key, $value, $default = null ) {
        $_origin_value = $_value = $value;

        if ( in_array( $_value, ['on', 'off', '1', 'false', 'true'], true ) ) {
            switch ( $_value ) {
                case 'on':
                case 'ON':
                case '1':
                case 'true':
                    $_value = true;
                    break;
                case 'off':
                case 'OFF':
                case '':
                case 'false':
                    $_value = false;
                    break;
            }
        }

        $this->type_validation( $_value, $default );
        return $_value;
    }

    public function get_normalized_values( $values, $default_values = [] ) {
        if ( empty( $values ) ) {
            return [];
        }

        $_settings = [];
        foreach ( $values as $key => $value ) {
            $_default_value = isset( $default_values[ $key ] ) ? $default_values[ $key ] : null;
            $_settings[$key] = $this->get_normalized_value( $key, $value, $_default_value );
        }

        return $_settings;
    }

    public function get_all( $raw = false ) {
        // $_settings = $this->get( '', null, true );
        // return $_settings;
        $_default_settings = $raw ? [] : array_merge( $this->get_default(), $this->get_pro_defaults() );
        $_settings = $this->database->get( $this->base_key, $_default_settings );
        return $this->get_normalized_values( $_settings, $_default_settings );
    }

    public function type_validation( &$value, $defaultValue = null ){
        if ( $defaultValue !== null ) {
            /**
             * Check if value is not in same type
             */
            $_default_type = gettype( $defaultValue );

            if( ! ( is_scalar( $defaultValue ) && is_scalar( $value ) ) && empty( $value ) ) {
                $value = $defaultValue;
            }

            settype( $value, $_default_type );
        }
    }

    /**
     * Get settings value by key
     *
     * @since 2.5.0
     *
     * @param string $key
     * @param mixed $default
     * @param bool   $get_all
     *
     * @return mixed
     */
    public function get( $key, $default = null ) {
        $_default_settings = array_merge( $this->get_default(), $this->get_pro_defaults() );
        $_settings         = $this->database->get( $this->base_key, $_default_settings );

        $_value = $default;
        switch ( true ) {
            // Check if it's a PRO Option
            case ! isset( $_default_settings[$key] ):
                $_value = $default;
                break;
            // Check if it's a FREE Option and not in DB.
            case ! isset( $_settings[$key] ) && isset( $_default_settings[$key] ):
                $_value = $default !== null ? $default : $_default_settings[$key];
                break;
            // Check if it's a FREE Option
            case isset( $_settings[$key] ) && isset( $_default_settings[$key] ):
                $_value = $_settings[$key];
                break;
        }

        $_value = $this->get_normalized_value( $key, $_value, isset( $_default_settings[$key] ) ? $_default_settings[$key] : null );


        if ( gettype( $_value ) === 'string' ) {
            if ( empty( $_value ) && $default != null ) {
                $_value = $default;
            } elseif ( empty( $_value ) && $default === null && isset( $_default_settings[$key] ) ) {
                $_value = $_default_settings[$key];
            }
        }

        return $_value;
    }

    public function get_raw_field( $key, $default = null ) {
        $_settings = $this->database->get( $this->base_key, [] );

        if( isset( $_settings[ $key ] ) ) {
            return $this->get_normalized_value( $key, $_settings[ $key ], $default );
        }

        return $default;
    }

    public function save( $key, $value ) {
        $_settings       = $this->database->get( $this->base_key, [] );
        $_settings[$key] = $value;

        return $this->database->save( $this->base_key, $_settings );
    }

    public function save_settings( $settings ) {
        if ( ! current_user_can( 'edit_docs_settings' ) ) {
            return new WP_Error( 'unauthorized_action', __( 'You don\'t have any rights for saving settings.', 'betterdocs' ) );
        }

        $_old_settings = $this->database->get( $this->base_key, $this->get_default() );
        unset($_old_settings['ia_code_snippet']);
        // @todo: sanitize the data before inject in DB.
        $_normalized_settings = $this->get_normalized_values( $settings );
        unset($_normalized_settings['ia_code_snippet']);
        $_settings            = wp_parse_args( $_normalized_settings, $_old_settings );
        $_saved               = $this->database->save( $this->base_key, $_settings );

        do_action_ref_array( 'betterdocs::settings::saved', [$_saved, $_settings, $_old_settings, &$this] );

        return $_saved;
    }

    public function views( $hook ) {
        return betterdocs()->views->get( 'admin/settings' );
    }

    public function save_default_settings() {
        $_settings = $this->get_all();
        return $this->database->save( $this->base_key, $_settings );
    }

    public function get_pages() {
        $_pages = betterdocs()->query->get_posts( [
            'post_type'      => 'page',
            'numberposts'    => -1,
            'post_status'    => 'publish',
            'posts_per_page' => -1
        ] );

        $__pages = [];

        if ( ! empty( $_pages ) ) {
            $__pages[0] = __( 'Select a Page', 'betterdocs' );
            foreach ( $_pages->posts as $page ) {
                $__pages[$page->ID] = esc_html( $page->post_title );
            }
        }

        return $__pages;
    }

    public function settings_args() {
        $wp_roles  = $this->normalize_options( $this->get_roles() );

        $settings = [
            'id'            => 'betterdocs_settings_metabox_wrapper',
            'title'         => __( 'betterdocs', 'betterdocs' ),
            'object_types'  => ['betterdocs'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_header'   => false,
            'tabnumber'     => true,
            'is_pro_active' => betterdocs()->is_pro_active(),
            'logoURL'       => betterdocs()->assets->icon( 'betterdocs-icon.svg', true ),
            'layout'        => 'horizontal',
            'config'        => [
                'active'  => 'tab-general',
                'sidebar' => false,
                'title'   => false
            ],
            'submit'        => [
                'show'  => true,
                'label' => __( 'Save Settings', 'betterdocs' ),
                'loadingLabel' => __( 'Saving...', 'betterdocs' ),
                'class' => 'save-settings',
                'rules' => Rules::logicalRule( [
                    Rules::is( 'config.active', 'tab-design', true ),
                    Rules::is( 'config.active', 'tab-shortcodes', true ),
                    Rules::is( 'config.active', 'tab-instant-answer', true )
                ], 'and' )
            ],
            'values'        => $this->get_all( true ),
            'tabs'          => apply_filters( 'betterdocs_settings_tabs', [
                'tab-general'          => apply_filters( 'betterdocs_settings_tab_general', [
                    'id'       => 'tab-general',
                    'label'    => __( 'General', 'betterdocs' ),
                    'classes'  => 'tab-general',
                    'priority' => 10,
                    'fields'   => [
                        'multiple_kb'           => apply_filters( 'betterdocs_multi_kb_settings', [
                            'name'     => 'multiple_kb',
                            'type'     => 'checkbox',
                            'label'    => __( 'Enable Multiple Knowledge Base', 'betterdocs' ),
                            'default'  => '',
                            'priority' => 1,
                            'is_pro'   => true
                        ] ),
                        'builtin_doc_page'      => [
                            'name'     => 'builtin_doc_page',
                            'type'     => 'checkbox',
                            'label'    => __( 'Enable Built-in Documentation Page', 'betterdocs' ),
                            'default'  => true,
                            'priority' => 2,
                            'help'     => __( '<strong>Note:</strong> if you disable built-in documentation page, you can use shortcode or page builder widgets to design your documentation page.', 'betterdocs' )
                        ],
                        'breadcrumb_doc_title'  => [
                            'name'     => 'breadcrumb_doc_title',
                            'type'     => 'text',
                            'label'    => __( 'Documentation Page Title', 'betterdocs' ),
                            'default'  => 'Docs',
                            'priority' => 3
                        ],
                        'docs_slug'             => [
                            'name'     => 'docs_slug',
                            'type'     => 'text',
                            'label'    => __( 'BetterDocs Root Slug', 'betterdocs' ),
                            'default'  => 'docs',
                            'priority' => 4,
                            'rules'    => Rules::is( 'builtin_doc_page', true )
                        ],
                        'docs_page'             => [
                            'name'     => 'docs_page',
                            'label'    => __( 'Docs Page', 'betterdocs' ),
                            'type'     => 'select',
                            'default'  => 0,
                            'priority' => 5,
                            'search'   => true,
                            'options'  => $this->normalize_options( $this->get_pages() ),
                            'help'     => __( 'Note: You will need to insert BetterDocs Shortcode inside the page. This page will be used as docs permalink.', 'betterdocs' ),
                            'rules'    => Rules::is( 'builtin_doc_page', false )
                        ],
                        'category_slug'         => [
                            'name'     => 'category_slug',
                            'type'     => 'text',
                            'label'    => __( 'Custom Category Slug', 'betterdocs' ),
                            'default'  => 'docs-category',
                            'priority' => 6
                        ],
                        'tag_slug'              => [
                            'name'     => 'tag_slug',
                            'type'     => 'text',
                            'label'    => __( 'Custom Tag Slug', 'betterdocs' ),
                            'default'  => 'docs-tag',
                            'priority' => 7
                        ],
                        'permalink_structure'   => [
                            'name'     => 'permalink_structure',
                            'type'     => 'permalink_structure',
                            'label'    => __( 'Single Docs Permalink', 'betterdocs' ),
                            'default'  => PostType::permalink_structure(),
                            'priority' => 9,
                            'tags'     => $this->normalize_options( [
                                '%doc_category%'   => '%doc_category%',
                                '%knowledge_base%' => '%knowledge_base%'
                            ] ),
                            'help'     => __( '<b>Note:</b> Make sure to keep <b>Docs Root Slug</b> in the <b>Single Docs Permalink</b>. You are not able to keep it blank. You can use the available tags from below.', 'betterdocs' )
                        ],
                        'enable_faq_schema'     => [
                            'name'     => 'enable_faq_schema',
                            'type'     => 'checkbox',
                            'label'    => __( 'Enable FAQ Schema', 'betterdocs' ),
                            'default'  => '',
                            'priority' => 10
                        ],
                        'analytics_from'        => [
                            'name'     => 'analytics_from',
                            'type'     => 'select',
                            'label'    => __( 'Analytics From', 'betterdocs' ),
                            'options'  => $this->normalize_options( [
                                'everyone'         => __( 'Everyone', 'betterdocs' ),
                                'guests'           => __( 'Guests Only', 'betterdocs' ),
                                'registered_users' => __( 'Registered Users Only', 'betterdocs' )
                            ] ),
                            'default'  => 'everyone',
                            'priority' => 11,
                            'is_pro'   => true
                        ],
                        'exclude_bot_analytics' => [
                            'name'     => 'exclude_bot_analytics',
                            'type'     => 'checkbox',
                            'label'    => __( 'Exclude Bot Analytics', 'betterdocs' ),
                            'default'  => true,
                            'priority' => 12,
                            'help'     => __( 'Select if you want to exclude bot analytics.', 'betterdocs' ),
                            'is_pro'   => true
                        ]
                    ]
                ] ),
                'tab-layout'           => apply_filters( 'betterdocs_settings_tab_layout', [
                    'id'       => 'tab-layout',
                    'label'    => __( 'Layout', 'betterdocs' ),
                    'classes'  => 'tab-layout',
                    'priority' => 20,
                    'fields'   => [
                        'tab-sidebar-layout' => apply_filters( 'betterdocs_settings_tab_sidebar_layout', [
                            'id'              => 'tab-sidebar-layout',
                            'name'            => 'tab_sidebar_layout',
                            'label'           => __( 'Layout', 'betterdocs' ),
                            'classes'         => 'tab-layout',
                            'type'            => "tab",
                            'active'          => "layout_documentation_page",
                            'completionTrack' => true,
                            'sidebar'         => true,
                            'config'          => [
                                'active'  => 'layout_documentation_page',
                                'sidebar' => false,
                                'title'   => false
                            ],
                            'submit'          => [
                                'show' => false
                            ],
                            'step'            => [
                                'show' => false
                            ],
                            'priority'        => 20,
                            'fields'          => [
                                'layout_documentation_page' => [
                                    'id'       => 'layout_documentation_page',
                                    'name'     => 'layout_documentation_page',
                                    'type'     => 'section',
                                    'label'    => __( 'Documentation Page', 'betterdocs' ),
                                    'priority' => 5,
                                    'fields'   => [
                                        // 'doc_page'                  => [
                                        //     'name'     => 'doc_page',
                                        //     'type'     => 'title',
                                        //     'label'    => __( 'Documentation Page', 'betterdocs' ),
                                        //     'priority' => 1
                                        // ],
                                        'live_search'               => [
                                            'name'     => 'live_search',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Live Search', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 2
                                        ],
                                        'advance_search'            => apply_filters( 'betterdocs_advance_search_settings', [
                                            'name'     => 'advance_search',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Advanced Search', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 3,
                                            'is_pro'   => true
                                        ] ),
                                        'child_category_exclude'    => apply_filters( 'child_category_exclude', [
                                            'name'     => 'child_category_exclude',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Exclude Child Terms In Category Search', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 4,
                                            'is_pro'   => true
                                        ] ),
                                        'popular_keyword_limit'     => apply_filters( 'betterdocs_popular_keyword_limit_settings', [
                                            'name'     => 'popular_keyword_limit',
                                            'type'     => 'number',
                                            'label'    => __( 'Minimum amount of Keywords Search', 'betterdocs' ),
                                            'default'  => 5,
                                            'priority' => 5,
                                            'is_pro'   => true
                                        ] ),
                                        'search_letter_limit'       => [
                                            'name'     => 'search_letter_limit',
                                            'type'     => 'number',
                                            'label'    => __( 'Minimum Character Limit For Search Result', 'betterdocs' ),
                                            'priority' => 6,
                                            'default'  => 3
                                        ],
                                        'search_placeholder'        => [
                                            'name'     => 'search_placeholder',
                                            'type'     => 'text',
                                            'label'    => __( 'Search Placeholder', 'betterdocs' ),
                                            'default'  => 'Search..',
                                            'priority' => 7
                                        ],
                                        'search_button_text'        => apply_filters( 'betterdocs_search_button_text', [
                                            'name'     => 'search_button_text',
                                            'type'     => 'text',
                                            'label'    => __( 'Search Button Text', 'betterdocs' ),
                                            'priority' => 8,
                                            'default'  => __( 'Search', 'betterdocs' ),
                                            'is_pro'   => true
                                        ] ),
                                        'search_not_found_text'     => [
                                            'name'     => 'search_not_found_text',
                                            'type'     => 'text',
                                            'label'    => __( 'Search Not Found Text', 'betterdocs' ),
                                            'default'  => 'Sorry, no docs were found.',
                                            'priority' => 9
                                        ],
                                        'search_result_image'       => [
                                            'name'     => 'search_result_image',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Search Result Image', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 10
                                        ],
                                        'kb_based_search'           => apply_filters( 'betterdocs_kb_based_search_settings', [
                                            'name'     => 'kb_based_search',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Search Result based on Knowledge Base', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 11,
                                            'is_pro'   => true,
                                            'rules'    => Rules::is( 'multiple_kb', true )
                                        ] ),
                                        'masonry_layout'            => [
                                            'name'     => 'masonry_layout',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Masonry', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 12
                                        ],
                                        'terms_orderby'             => [
                                            'name'     => 'terms_orderby',
                                            'type'     => 'select',
                                            'label'    => __( 'Terms Order By', 'betterdocs' ),
                                            'default'  => 'betterdocs_order',
                                            'options'  => $this->normalize_options(
                                                apply_filters( 'betterdocs_terms_orderby_options', [
                                                    'none'             => __( 'No order', 'betterdocs' ),
                                                    'name'             => __( 'Name', 'betterdocs' ),
                                                    'slug'             => __( 'Slug', 'betterdocs' ),
                                                    'term_group'       => __( 'Term Group', 'betterdocs' ),
                                                    'term_id'          => __( 'Term ID', 'betterdocs' ),
                                                    'id'               => __( 'ID', 'betterdocs' ),
                                                    'description'      => __( 'Description', 'betterdocs' ),
                                                    'parent'           => __( 'Parent', 'betterdocs' ),
                                                    'betterdocs_order' => __( 'BetterDocs Order', 'betterdocs' )
                                                ] )
                                            ),
                                            'priority' => 13
                                        ],
                                        'terms_order'               => [
                                            'name'     => 'terms_order',
                                            'type'     => 'select',
                                            'label'    => __( 'Terms Order', 'betterdocs' ),
                                            'default'  => 'ASC',
                                            'options'  => $this->normalize_options( [
                                                'ASC'  => 'Ascending',
                                                'DESC' => 'Descending'
                                            ] ),
                                            'priority' => 15
                                        ],
                                        'alphabetically_order_post' => [
                                            'name'     => 'alphabetically_order_post',
                                            'type'     => 'select',
                                            'label'    => __( 'Docs Order By', 'betterdocs' ),
                                            'default'  => 'betterdocs_order',
                                            'options'  => $this->normalize_options( [
                                                'none'             => __( 'No order', 'betterdocs' ),
                                                'ID'               => __( 'Docs ID', 'betterdocs' ),
                                                'author'           => __( 'Docs Author', 'betterdocs' ),
                                                'title'            => __( 'Title', 'betterdocs' ),
                                                'date'             => __( 'Date', 'betterdocs' ),
                                                'modified'         => __( 'Last Modified Date', 'betterdocs' ),
                                                'parent'           => __( 'Parent Id', 'betterdocs' ),
                                                'rand'             => __( 'Random', 'betterdocs' ),
                                                'comment_count'    => __( 'Comment Count', 'betterdocs' ),
                                                'menu_order'       => __( 'Menu Order', 'betterdocs' ),
                                                'betterdocs_order' => __( 'BetterDocs Order', 'betterdocs' )
                                            ] ),
                                            'priority' => 16
                                        ],
                                        'docs_order'                => [
                                            'name'     => 'docs_order',
                                            'type'     => 'select',
                                            'label'    => __( 'Docs Order', 'betterdocs' ),
                                            'default'  => 'ASC',
                                            'options'  => $this->normalize_options( [
                                                'ASC'  => 'Ascending',
                                                'DESC' => 'Descending'
                                            ] ),
                                            'priority' => 17
                                        ],
                                        'nested_subcategory'        => [
                                            'name'     => 'nested_subcategory',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Nested Subcategory', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 18
                                        ],
                                        'column_number'             => [
                                            'name'     => 'column_number',
                                            'type'     => 'number',
                                            'label'    => __( 'Number of Columns', 'betterdocs' ),
                                            'default'  => 3,
                                            'priority' => 19
                                        ],
                                        'posts_number'              => apply_filters( 'betterdocs_posts_number', [
                                            'name'     => 'posts_number',
                                            'type'     => 'number',
                                            'label'    => __( 'Number of Docs', 'betterdocs' ),
                                            'default'  => 10,
                                            'priority' => 20
                                        ] ),
                                        'post_count'                => [
                                            'name'     => 'post_count',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Doc Count', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 21
                                        ],
                                        'count_text'                => [
                                            'name'     => 'count_text',
                                            'type'     => 'text',
                                            'label'    => __( 'Count Text', 'betterdocs' ),
                                            'default'  => __( 'docs', 'betterdocs' ),
                                            'priority' => 22
                                        ],
                                        'count_text_singular'       => [
                                            'name'     => 'count_text_singular',
                                            'type'     => 'text',
                                            'label'    => __( 'Count Text Singular', 'betterdocs' ),
                                            'default'  => __( 'doc', 'betterdocs' ),
                                            'priority' => 23
                                        ],
                                        'exploremore_btn'           => [
                                            'name'     => 'exploremore_btn',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Explore More Button', 'betterdocs' ),
                                            'default'  => true,
                                            'priority' => 24
                                        ],
                                        'exploremore_btn_txt'       => [
                                            'name'     => 'exploremore_btn_txt',
                                            'type'     => 'text',
                                            'label'    => __( 'Button Text', 'betterdocs' ),
                                            'default'  => __( 'Explore More', 'betterdocs' ),
                                            'priority' => 25,
                                            'rules'    => Rules::is( 'exploremore_btn', true )
                                        ]
                                    ]
                                ],
                                'layout_single_doc'         => [
                                    'id'       => 'layout_single_doc',
                                    'name'     => 'layout_single_doc',
                                    'type'     => 'section',
                                    'label'    => __( 'Single Doc', 'betterdocs' ),
                                    'priority' => 6,
                                    'fields'   => [
                                        // 'doc_single'                 => [
                                        //     'name'     => 'doc_single',
                                        //     'type'     => 'title',
                                        //     'label'    => __( 'Single Doc', 'betterdocs' ),
                                        //     'priority' => 1
                                        // ],
                                        'enable_toc'                 => [
                                            'name'     => 'enable_toc',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Table of Contents (TOC)', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 2
                                        ],
                                        'toc_title'                  => [
                                            'name'     => 'toc_title',
                                            'type'     => 'text',
                                            'label'    => __( 'TOC Title', 'betterdocs' ),
                                            'default'  => __( 'Table of Contents', 'betterdocs' ),
                                            'priority' => 3,
                                            'rules'    => Rules::is( 'enable_toc', true )

                                        ],
                                        'toc_hierarchy'              => [
                                            'name'     => 'toc_hierarchy',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'TOC Hierarchy', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 4,
                                            'rules'    => Rules::is( 'enable_toc', true )
                                        ],
                                        'toc_list_number'            => [
                                            'name'     => 'toc_list_number',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'TOC List Number', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 5,
                                            'rules'    => Rules::is( 'enable_toc', true )
                                        ],
                                        'toc_dynamic_title'          => [
                                            'name'     => 'toc_dynamic_title',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Show TOC Title in Anchor Links', 'betterdocs' ),
                                            'default'  => 0,
                                            'priority' => 6,
                                            'rules'    => Rules::is( 'enable_toc', true )
                                        ],
                                        'enable_sticky_toc'          => [
                                            'name'     => 'enable_sticky_toc',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Sticky TOC', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 7,
                                            'rules'    => Rules::is( 'enable_toc', true )
                                        ],
                                        'sticky_toc_offset'          => [
                                            'name'        => 'sticky_toc_offset',
                                            'type'        => 'number',
                                            'label'       => __( 'Content Offset', 'betterdocs' ),
                                            'default'     => 100,
                                            'priority'    => 8,
                                            'description' => __( 'content offset from top on scroll.', 'betterdocs' ),
                                            'rules'       => Rules::is( 'enable_toc', true )
                                        ],
                                        'collapsible_toc_mobile'     => [
                                            'name'     => 'collapsible_toc_mobile',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Collapsible TOC on small devices', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 9,
                                            'rules'    => Rules::is( 'enable_toc', true )
                                        ],
                                        'supported_heading_tag'      => [
                                            'name'     => 'supported_heading_tag',
                                            'label'    => __( 'TOC Supported Heading Tag', 'betterdocs' ),
                                            'type'     => 'select',
                                            'multiple' => true,
                                            'priority' => 10,
                                            'default'  => ['1', '2', '3', '4', '5', '6'],
                                            'options'  => $this->normalize_options( [
                                                '1' => 'h1',
                                                '2' => 'h2',
                                                '3' => 'h3',
                                                '4' => 'h4',
                                                '5' => 'h5',
                                                '6' => 'h6'
                                            ] ),
                                            'rules'    => Rules::is( 'enable_toc', true )
                                        ],
                                        'enable_post_title'          => [
                                            'name'     => 'enable_post_title',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Post Title', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 11
                                        ],
                                        'title_link_ctc'             => [
                                            'name'     => 'title_link_ctc',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Title Link Copy To Clipboard', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 12
                                        ],
                                        'enable_breadcrumb'          => [
                                            'name'     => 'enable_breadcrumb',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Breadcrumb', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 13
                                        ],
                                        'breadcrumb_home_text'       => [
                                            'name'     => 'breadcrumb_home_text',
                                            'type'     => 'text',
                                            'label'    => __( 'Breadcrumb Home Text', 'betterdocs' ),
                                            'default'  => __( 'Home', 'betterdocs' ),
                                            'priority' => 14,
                                            'rules'    => Rules::is( 'enable_breadcrumb', true )

                                        ],
                                        'breadcrumb_home_url'        => [
                                            'name'     => 'breadcrumb_home_url',
                                            'type'     => 'text',
                                            'label'    => __( 'Breadcrumb Home URL', 'betterdocs' ),
                                            'priority' => 15,
                                            'default'  => get_home_url(),
                                            'rules'    => Rules::is( 'enable_breadcrumb', true )
                                        ],
                                        'enable_breadcrumb_category' => [
                                            'name'     => 'enable_breadcrumb_category',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Category on Breadcrumb', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 16,
                                            'rules'    => Rules::is( 'enable_breadcrumb', true )
                                        ],
                                        'enable_breadcrumb_title'    => [
                                            'name'     => 'enable_breadcrumb_title',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Title on Breadcrumb', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 17,
                                            'rules'    => Rules::is( 'enable_breadcrumb', true )
                                        ],
                                        'enable_sidebar_cat_list'    => [
                                            'name'     => 'enable_sidebar_cat_list',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Sidebar Category List', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 18
                                        ],
                                        'enable_print_icon'          => [
                                            'name'     => 'enable_print_icon',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Print Icon', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 19
                                        ],
                                        'enable_tags'                => [
                                            'name'     => 'enable_tags',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Tags', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 20
                                        ],
                                        'email_feedback'             => [
                                            'name'     => 'email_feedback',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Email Feedback', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 21
                                        ],
                                        'feedback_link_text'         => [
                                            'name'     => 'feedback_link_text',
                                            'type'     => 'text',
                                            'label'    => __( 'Feedback Link Text', 'betterdocs' ),
                                            'default'  => __( 'Still stuck? How can we help?', 'betterdocs' ),
                                            'priority' => 22,
                                            'rules'    => Rules::is( 'email_feedback', true )
                                        ],
                                        'feedback_url'               => [
                                            'name'     => 'feedback_url',
                                            'type'     => 'text',
                                            'label'    => __( 'Feedback URL', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 23,
                                            'rules'    => Rules::is( 'email_feedback', true )
                                        ],
                                        'feedback_form_title'        => [
                                            'name'     => 'feedback_form_title',
                                            'type'     => 'text',
                                            'label'    => __( 'Feedback Form Title', 'betterdocs' ),
                                            'default'  => __( 'How can we help?', 'betterdocs' ),
                                            'priority' => 24,
                                            'rules'    => Rules::is( 'email_feedback', true )
                                        ],
                                        'email_address'              => [
                                            'name'        => 'email_address',
                                            'type'        => 'text',
                                            'label'       => __( 'Email Address', 'betterdocs' ),
                                            'default'     => get_option( 'admin_email' ),
                                            'priority'    => 25,
                                            'description' => __( 'The email address where the Feedback from will be sent', 'betterdocs' ),
                                            'rules'       => Rules::is( 'email_feedback', true )
                                        ],
                                        'show_last_update_time'      => [
                                            'name'     => 'show_last_update_time',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Show Last Update Time', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 26
                                        ],
                                        'enable_navigation'          => [
                                            'name'     => 'enable_navigation',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Navigation', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 27
                                        ],
                                        'enable_comment'             => [
                                            'name'     => 'enable_comment',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Comment', 'betterdocs' ),
                                            'default'  => '',
                                            'priority' => 28
                                        ],
                                        'enable_credit'              => [
                                            'name'     => 'enable_credit',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Credit', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 29
                                        ]
                                    ]
                                ],
                                'layout_archive_page'       => [
                                    'id'       => 'layout_archive_page',
                                    'name'     => 'layout_archive_page',
                                    'type'     => 'section',
                                    'label'    => __( 'Archive Page', 'betterdocs' ),
                                    'priority' => 7,
                                    'fields'   => [
                                        'enable_archive_sidebar'     => [
                                            'name'     => 'enable_archive_sidebar',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Enable Sidebar Category List', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 31
                                        ],
                                        'archive_nested_subcategory' => [
                                            'name'     => 'archive_nested_subcategory',
                                            'type'     => 'checkbox',
                                            'label'    => __( 'Nested Subcategory', 'betterdocs' ),
                                            'default'  => 1,
                                            'priority' => 32
                                        ]
                                    ]
                                ]
                            ]
                        ] )
                    ]
                ] ),
                'tab-design'           => apply_filters( 'betterdocs_settings_tab_design', [
                    'id'       => 'tab-design',
                    'label'    => __( 'Design', 'betterdocs' ),
                    'priority' => 21,
                    'fields'   => [
                        'customizer_link' => [
                            'name'           => 'customizer_link',
                            'type'           => 'action',
                            'action'         => 'betterdocs_settings_customizer_link',
                            'label'          => __( 'Customize BetterDocs', 'betterdocs' ),
                            'url'            => $this->customizer_link(),
                            'customizer_img' => betterdocs()->assets->icon( 'customizer/betterdocs-customize.svg', true ),
                            'priority'       => 1
                        ]
                    ]
                ] ),
                'tab-shortcodes'       => apply_filters( 'betterdocs_settings_tab_shortcodes', [
                    'label'    => __( 'Shortcodes', 'betterdocs' ),
                    'id'       => 'tab-shortcodes',
                    'classes'  => 'tab-shortcodes',
                    'priority' => 30,
                    'fields'   => apply_filters( 'betterdocs_shortcode_fields', [
                        'search_form'        => [
                            'name'        => 'search_form',
                            'type'        => 'text',
                            'label'       => __( 'Search Form', 'betterdocs' ),
                            'default'     => '[betterdocs_search_form]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 1,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_search_form placeholder="Search..." heading="Heading" subheading="Subheading" category_search="true" search_button="true" popular_search="true"]', 'betterdocs' )
                        ],
                        'feedback_form'      => [
                            'name'        => 'feedback_form',
                            'type'        => 'text',
                            'label'       => __( 'Feedback Form', 'betterdocs' ),
                            'default'     => '[betterdocs_feedback_form]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 2,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_feedback_form button_text="Send"]', 'betterdocs' )
                        ],
                        'category_grid'      => [
                            'name'        => 'category_grid',
                            'type'        => 'text',
                            'label'       => __( 'Category Grid- Layout 1', 'betterdocs' ),
                            'default'     => '[betterdocs_category_grid]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 3,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_category_grid post_counter="true" show_icon="true" masonry="true" column="3" posts_per_page="5" nested_subcategory="true" terms="term_ID, term_ID" terms_orderby="" terms_order="" multiple_knowledge_base="" kb_slug="" title_tag="h2" orderby="" order="" ]', 'betterdocs' )
                        ],
                        'category_box'       => [
                            'name'        => 'category_box',
                            'type'        => 'text',
                            'label'       => __( 'Category Box- Layout 2', 'betterdocs' ),
                            'default'     => '[betterdocs_category_box]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 4,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_category_box orderby="" column="" nested_subcategory="" terms="" terms_orderby="" show_icon="" kb_slug="" title_tag="h2" multiple_knowledge_base="false" border_bottom="false"]', 'betterdocs' )
                        ],
                        'category_list'      => [
                            'name'        => 'category_list',
                            'type'        => 'text',
                            'label'       => __( 'Category List', 'betterdocs' ),
                            'default'     => '[betterdocs_category_list]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 5,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_category_list orderby="" order="" posts_per_page="" nested_subcategory="" terms="" terms_orderby="" terms_order="" kb_slug="" multiple_knowledge_base="false" title_tag="h2"]', 'betterdocs' )
                        ],
                        'faq_modern_layout'  => [
                            'name'        => 'faq_modern_layout',
                            'type'        => 'text',
                            'label'       => __( 'FAQ Layout - 1', 'betterdocs' ),
                            'default'     => '[betterdocs_faq_list_modern]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 15,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_faq_list_modern groups="group_id" class="" group_exclude="group_id" faq_heading="Frequently Asked Questions"]', 'betterdocs' )
                        ],
                        'faq_classic_layout' => [
                            'name'        => 'faq_classic_layout',
                            'type'        => 'text',
                            'label'       => __( 'FAQ Layout - 2', 'betterdocs' ),
                            'default'     => '[betterdocs_faq_list_classic]',
                            'readOnly'    => true,
                            'copyOnClick' => true,
                            'priority'    => 16,
                            'help'        => __( '<strong>You can use:</strong> [betterdocs_faq_list_classic groups="group_id" class="" group_exclude="group_id" faq_heading="Frequently Asked Questions"]', 'betterdocs' )
                        ]
                    ] )
                ] ),
                'tab-advance-settings' => apply_filters( 'betterdocs_settings_tab_advance', [
                    'id'       => 'tab-advance-settings',
                    'label'    => __( 'Advanced Settings', 'betterdocs' ),
                    'priority' => 40,
                    'fields'   => [
                        'role_management' => [
                            'name'     => 'role_management',
                            'type'     => 'section',
                            'label'    => __( 'Role Management', 'betterdocs' ),
                            'priority' => 5,
                            'fields'   => [
                                'article_roles'   => [
                                    'name'     => 'article_roles',
                                    'type'     => 'select',
                                    'label'    => __( 'Who Can Write Docs?', 'betterdocs' ),
                                    'priority' => 1,
                                    'multiple' => true,
                                    'search'   => true,
                                    'is_pro'   => true,
                                    'default'  => ['administrator'],
                                    'options'  => $wp_roles
                                ],
                                'settings_roles'  => [
                                    'name'     => 'settings_roles',
                                    'type'     => 'select',
                                    'label'    => __( 'Who Can Edit Settings?', 'betterdocs' ),
                                    'priority' => 2,
                                    'multiple' => true,
                                    'is_pro'   => true,
                                    'search'   => true,
                                    'default'  => ['administrator'],
                                    'options'  => $wp_roles
                                ],
                                'analytics_roles' => [
                                    'name'     => 'analytics_roles',
                                    'type'     => 'select',
                                    'label'    => __( 'Who Can Check Analytics?', 'betterdocs' ),
                                    'priority' => 3,
                                    'multiple' => true,
                                    'is_pro'   => true,
                                    'search'   => true,
                                    'default'  => ['administrator'],
                                    'options'  => $wp_roles
                                ]
                            ]
                        ],
                        'internal_kb'     => [
                            'name'     => 'internal_kb',
                            'type'     => 'section',
                            'label'    => __( 'Internal Knowledge Base', 'betterdocs' ),
                            'priority' => 6,
                            'fields'   => apply_filters( 'betterdocs_internal_kb_fields', [
                                'enable_content_restriction' => [
                                    'name'     => 'enable_content_restriction',
                                    'type'     => 'checkbox',
                                    'is_pro'   => true,
                                    'priority' => 1,
                                    'label'    => __( 'Enable/Disable', 'betterdocs' ),
                                    'default'  => ''
                                ],
                                'content_visibility'         => [
                                    'name'        => 'content_visibility',
                                    'type'        => 'select',
                                    'label'       => __( 'Restrict Access to', 'betterdocs' ),
                                    'help'        => __( '<strong>Note:</strong> Only selected User Roles will be able to view your Knowledge Base', 'betterdocs' ),
                                    'is_pro'      => true,
                                    'priority'    => 2,
                                    'multiple'    => true,
                                    'search'      => true,
                                    'default'     => 'all',
                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                    'options'     => $this->normalize_options( array_merge( [
                                        'all' => __( 'All logged in users', 'betterdocs' )
                                    ], wp_roles()->role_names ) ),
                                    'rules'       => Rules::is( 'enable_content_restriction', true ),
                                    'filterValue' => 'all'
                                ],
                                'restrict_template'          => [
                                    'name'        => 'restrict_template',
                                    'type'        => 'select',
                                    'label'       => __( 'Restriction on Docs', 'betterdocs' ),
                                    'help'        => __( '<strong>Note:</strong> Selected Docs pages will be restricted', 'betterdocs' ),
                                    'is_pro'      => true,
                                    'priority'    => 3,
                                    'multiple'    => true,
                                    'search'      => true,
                                    'default'     => 'all',
                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                    'options'     => $this->get_texanomy(),
                                    'rules'       => Rules::is( 'enable_content_restriction', true ),
                                    'filterValue' => 'all'
                                ],
                                'restrict_category'          => [
                                    'name'        => 'restrict_category',
                                    'type'        => 'select',
                                    'label'       => __( 'Restriction on Docs Categories', 'betterdocs' ),
                                    'help'        => __( '<strong>Note:</strong> Selected Docs categories will be restricted ', 'betterdocs' ),
                                    'is_pro'      => true,
                                    'priority'    => 5,
                                    'multiple'    => true,
                                    'search'      => true,
                                    'default'     => 'all',
                                    'placeholder' => __( 'Select any', 'betterdocs' ),
                                    'options'     => $this->get_terms( 'doc_category' ),
                                    'rules'       => Rules::is( 'enable_content_restriction', true ),
                                    'filterValue' => 'all'
                                ],
                                'restricted_redirect_url'    => [
                                    'name'        => 'restricted_redirect_url',
                                    'type'        => 'text',
                                    'label'       => __( 'Redirect URL', 'betterdocs' ),
                                    'help'        => __( '<strong>Note:</strong> Set a custom URL to redirect users without permissions when they try to access internal knowledge base. By default, restricted content will redirect to the "404 not found" page', 'betterdocs' ),
                                    'default'     => '',
                                    'placeholder' => 'https://',
                                    'is_pro'      => true,
                                    'priority'    => 6,
                                    'rules'       => Rules::is( 'enable_content_restriction', true )
                                ]
                            ] )
                        ]
                    ]
                ] ),
                'tab-email-reporting'  => apply_filters( 'betterdocs_settings_tab_email_reporting', [
                    'id'     => 'tab-email-reporting',
                    'label'  => __( 'Email Reporting', 'betterdocs' ),
                    'fields' => [
                        'enable_reporting'      => [
                            'name'     => 'enable_reporting',
                            'label'    => __( 'Enable Reporting', 'betterdocs' ),
                            'type'     => 'checkbox',
                            'priority' => 0,
                            'default'  => 0
                        ],
                        'reporting_frequency'   => apply_filters( 'betterdocs_reporting_frequency_settings', [
                            'name'     => 'reporting_frequency',
                            'type'     => 'select',
                            'label'    => __( 'Reporting Frequency', 'betterdocs' ),
                            'default'  => 'betterdocs_weekly',
                            'priority' => 1,
                            'is_pro'   => true,
                            'options'  => $this->normalize_options( [
                                'betterdocs_daily'   => __( 'Once Daily', 'betterdocs' ),
                                'betterdocs_weekly'  => __( 'Once Weekly', 'betterdocs' ),
                                'betterdocs_monthly' => __( 'Once Monthly', 'betterdocs' )
                            ] ),
                            'rules'    => Rules::is( 'enable_reporting', true )
                        ] ),
                        'reporting_day'         => [
                            'name'     => 'reporting_day',
                            'type'     => 'select',
                            'label'    => __( 'Select Reporting Day', 'betterdocs' ),
                            'default'  => 'monday',
                            'rules'    => Rules::logicalRule( [
                                Rules::is( 'enable_reporting', true ),
                                Rules::is( 'reporting_frequency', 'betterdocs_weekly' )
                            ], 'and' ),
                            'priority' => 2,
                            'options'  => $this->normalize_options( [
                                'sunday'    => __( 'Sunday', 'betterdocs' ),
                                'monday'    => __( 'Monday', 'betterdocs' ),
                                'tuesday'   => __( 'Tuesday', 'betterdocs' ),
                                'wednesday' => __( 'Wednesday', 'betterdocs' ),
                                'thursday'  => __( 'Thursday', 'betterdocs' ),
                                'friday'    => __( 'Friday', 'betterdocs' ),
                                'saturday'  => __( 'Saturday', 'betterdocs' )
                            ] ),
                            'help'     => __( '<strong>Note:</strong> This is only applicable for the <strong>“Weekly”</strong> report', 'betterdocs' )
                        ],
                        'reporting_email'       => [
                            'name'     => 'reporting_email',
                            'type'     => 'text',
                            'label'    => __( 'Reporting Email', 'betterdocs' ),
                            'default'  => get_option( 'admin_email' ),
                            'priority' => 3,
                            'rules'    => Rules::is( 'enable_reporting', true )
                        ],
                        'reporting_subject'     => apply_filters( 'betterdocs_reporting_subject_settings', [
                            'name'     => 'reporting_subject',
                            'type'     => 'text',
                            'label'    => __( 'Reporting Email Subject', 'betterdocs' ),
                            'default'  => wp_sprintf( __( 'Your Documentation Performance of %s Website', 'betterdocs' ), get_bloginfo( 'name' ) ),
                            'priority' => 4,
                            'is_pro'   => true,
                            'rules'    => Rules::is( 'enable_reporting', true )
                        ] ),
                        'select_reporting_data' => apply_filters( 'betterdocs_select_reporting_data_settings', [
                            'name'     => 'select_reporting_data',
                            'type'     => 'select',
                            'label'    => __( 'Select Reporting Data', 'betterdocs' ),
                            'priority' => 5,
                            'multiple' => true,
                            'options'  => $this->normalize_options( [
                                'overview'    => 'Overview',
                                'top-docs'    => 'Top Docs',
                                'most-search' => 'Most Searched Keywords'
                            ] ),
                            'default'  => ['overview', 'top-docs', 'most-search'],
                            'is_pro'   => true,
                            'rules'    => Rules::is( 'enable_reporting', true )
                        ] ),
                        'test_report'           => [
                            'name'     => 'test_report',
                            'label'    => __( 'Reporting Test', 'betterdocs' ),
                            'text'     => __( 'Test Report', 'betterdocs' ),
                            'type'     => 'button',
                            'priority' => 6,
                            'rules'    => Rules::is( 'enable_reporting', true ),
                            'ajax'     => [
                                'on'   => 'click',
                                'api'  => '/betterdocs/v1/reporting-test',
                                'data' => [
                                    'enable_reporting'      => '@enable_reporting',
                                    'select_reporting_data' => '@select_reporting_data',
                                    'reporting_subject'     => '@reporting_subject',
                                    'reporting_email'       => '@reporting_email',
                                    'reporting_day'         => '@reporting_day',
                                    'reporting_frequency'   => '@reporting_frequency'
                                ],
                                'swal' => [
                                    'text'      => __( 'Successfully Sent a Test Report in Your Email.', 'betterdocs' ),
                                    'icon'      => 'success',
                                    'autoClose' => 2000
                                ]
                            ]
                        ]
                    ]
                ] ),
                'tab-instant-answer'   => apply_filters( 'betterdocs_settings_tab_instant_answer', [
                    'id'       => 'tab-instant-answer',
                    'name'     => 'tab-instant-answer',
                    'type'     => 'section',
                    'label'    => __( 'Instant Answer', 'betterdocs' ),
                    'priority' => 6,
                    'fields'   => [
                        'enable_instant_answer' => [
                            'name'     => 'layout-documentation-page',
                            'type'     => 'section',
                            'label'    => __( 'Enable/Disable Instant Answer', 'betterdocs-pro' ),
                            'priority' => 0,
                            'fields'   => [
                                'ia_description'    => [
                                    'name'     => 'ia_description',
                                    'type'     => 'html',
                                    'priority' => 1,
                                    'html'     => wp_sprintf( '<p>%s</p>', __( 'Display a list of docs or categories in a chat-like widget to give your visitors a chance of self-learning about your website.' ) )
                                ],
                                'enable_disable'    => [
                                    'name'     => 'enable_disable',
                                    'type'     => 'checkbox',
                                    'priority' => 2,
                                    'label'    => __( 'Enable/Disable', 'betterdocs-pro' ),
                                    'default'  => true,
                                    'is_pro'   => true
                                ],
                                'ia_enable_preview' => [
                                    'name'     => 'ia_enable_preview',
                                    'type'     => 'checkbox',
                                    'label'    => __( 'Enable IA Live Preview', 'betterdocs-pro' ),
                                    'priority' => 3,
                                    'default'  => false,
                                    'is_pro'   => true,
                                    'rules'    => Rules::is( 'enable_disable', true )
                                ]
                            ]
                        ],
                        'ia_image'              => [
                            'name'     => 'ia_image',
                            'type'     => 'image',
                            'media'    => [
                                'url' => betterdocs()->assets->icon( 'ia-preview.gif', true ),
                                'alt' => __( 'Instant Answer', 'betterdocs' )
                            ],
                            'priority' => 3,
                            'rules'    => Rules::logicalRule( [
                                Rules::is( 'is_pro_active', false ),
                                Rules::is( 'enable_disable', true, true )
                            ], 'or' )
                        ]
                    ]
                ] )
            ] )
        ];

        return apply_filters( 'betterdocs_settings_args', $settings );
    }

    public function normalize_options( $options ) {
        return GlobalFields::normalize_fields( $options );
    }

    public function get_texanomy() {
        $docs_tax = $this->database->get_cache( 'betterdocs::settings::taxonomies' );

        if ( $docs_tax ) {
            return $docs_tax;
        }

        $taxonomies = get_taxonomies( [
            'object_type' => ['docs']
        ], 'objects' );

        $docs_tax = [
            'all'  => 'All Docs Archive',
            'docs' => 'Docs Page'
        ];
        foreach ( $taxonomies as $key => $value ) {
            $docs_tax[$key] = $value->label;
        }
        unset( $docs_tax['doc_tag'] );

        $docs_tax = $this->normalize_options( $docs_tax );
        if ( count( $docs_tax ) > 2 ) {
            $this->database->set_cache( 'betterdocs::settings::taxonomies', $docs_tax );
        }

        return $docs_tax;
    }

    public function get_terms( $taxonomy ) {
        $_cache_key = 'betterdocs::settings::terms::' . trim( $taxonomy );
        $docs_tax   = $this->database->get_cache( $_cache_key );

        if ( $docs_tax ) {
            return $docs_tax;
        }

        $get_terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false
        ] );

        $terms = [
            'all' => 'All'
        ];

        if ( ! empty( $get_terms ) && ! is_wp_error( $get_terms ) ) {
            foreach ( $get_terms as $value ) {
                if ( isset( $value->slug ) && isset( $value->name ) ) {
                    $terms[$value->slug] = $value->name;
                }
            }
        }

        $terms = $this->normalize_options( $terms );
        if ( count( $terms ) > 1 ) {
            $this->database->set_cache( $_cache_key, $terms );
        }

        return $terms;
    }

    public function hide_roles_management( $tabData = [] ){
        global $current_user;

        if( $current_user instanceof WP_User && ! in_array( 'administrator', $current_user->roles ) ) {
            unset( $tabData['fields']['role_management'] );
        }

        return $tabData;
    }
}
