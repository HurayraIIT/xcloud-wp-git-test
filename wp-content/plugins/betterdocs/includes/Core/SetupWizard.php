<?php

namespace WPDeveloper\BetterDocs\Core;

use WPDeveloper\BetterDocs\Utils\Base;
use WPDeveloper\BetterDocs\Utils\Helper;
use WPDeveloper\BetterDocs\SetupWizard\SetupWizard as SetupWizardHelper;

class SetupWizard extends Base {
    private $settings;
    public function __construct( Settings $settings ) {
        $this->settings = $settings;

        add_action( 'admin_enqueue_scripts', [$this, 'enqueue'] );
        add_action( 'admin_init', [$this, 'load'] );
    }

    public function load() {
        SetupWizardHelper::load( $this->settings );
    }

    public function enqueue( $hook ) {
        if ( $hook !== 'betterdocs_page_betterdocs-setup' ) {
            return;
        }

        betterdocs()->assets->enqueue( 'betterdocs-sweetalert', 'vendor/js/sweetalert.min.js', [] );
        betterdocs()->assets->enqueue( 'betterdocs-setup-wizard', 'admin/css/setup-wizard.css' );
        betterdocs()->assets->enqueue( 'betterdocs-setup-wizard', 'admin/js/setup-wizard.js', ['jquery', 'betterdocs-sweetalert'] );

        // Localize the script with new data
        betterdocs()->assets->localize( 'betterdocs-setup-wizard', 'bdquicksetup', [
            'finish_txt'    => __( 'Finish', 'betterdocs' ),
            'next_txt'      => __( 'Next', 'betterdocs' ),
            'customizerurl' => $this->customizer_settings_url(),
            'docspageurl'   => $this->docs_page_url(),
            'currentslug'   => $this->settings->get( 'docs_slug' )
        ] );
    }

    public function init() {
        SetupWizardHelper::setSection( [
            'id'     => 'betterdocs_getting_started_settings',
            'title'  => __( 'Getting Started', 'betterdocs' ),
            'fields' => [
                [
                    'id'        => 'getting_started',
                    'title'     => __( 'Getting Started', 'betterdocs' ),
                    'sub_title' => __( 'Easily get started with this easy setup wizard and complete setting up your Knowledge Base.', 'betterdocs' ),
                    'type'      => 'welcome',
                    'video_url' => 'https://www.youtube.com/embed/57BioKfROlo'
                ]
            ]
        ] );

        $existing_plugins = $this->knowledge_base_plugins();
        if ( $existing_plugins ) {
            SetupWizardHelper::setSection( [
                'id'     => 'betterdocs_migration_settings',
                'title'  => __( 'Migration', 'betterdocs' ),
                'fields' => [
                    [
                        'id'        => 'migration_step',
                        'sub_title' => __( 'We detected another Knowledge Base Plugin installed in this site. For BetterDocs to work efficiently, we will migrate the data from the plugin listed below, and deactivate the plgugin, to avoid conflict.', 'betterdocs' ),
                        'type'      => 'migration',
                        'options'   => [
                            [
                                'id'      => $existing_plugins[0][0],
                                'title'   => 'Migrate ' . $existing_plugins[0][1],
                                'type'    => 'checkbox',
                                'default' => 1
                            ]
                        ]
                    ]
                ]
            ] );
        }

        // Setup Pages
        SetupWizardHelper::setSection( [
            'id'     => 'betterdocs_setup_page_settings',
            'title'  => __( 'Setup Pages', 'betterdocs' ),
            'fields' => [
                [
                    'id'    => 'builtin_doc_page',
                    'title' => __( 'Enable Built-in Documentation Page', 'betterdocs' ),
                    'type'  => 'checkbox'
                ],
                [
                    'id'          => 'docs_slug',
                    'title'       => __( 'Page Slug', 'betterdocs' ),
                    'type'        => 'text',
                    'placeholder' => 'Page Slug',
                    'default'     => 'docs'
                ],
                [
                    'id'    => 'enable_disable',
                    'title' => __( 'Enable Instant Answer', 'betterdocs' ),
                    'type'  => 'checkbox_pro_feature'
                ]
            ]
        ] );

        // Create Content
        SetupWizardHelper::setSection( [
            'id'     => 'betterdocs_create_content_settings',
            'title'  => __( 'Create Content', 'betterdocs' ),
            'fields' => [
                [
                    'id'        => 'content_step',
                    'title'     => __( 'Create Documentation Content', 'betterdocs' ),
                    'sub_title' => __( 'Let\'s create some categories and articles. And then assign the articles to proper categories.', 'betterdocs' ),
                    'type'      => 'link',
                    'image_url' => betterdocs()->assets->icon( 'setup-wizard/setup-articles.png', true ),
                    'options'   => [
                        [
                            'title'           => __( 'Create Categories', 'betterdocs' ),
                            'url'             => admin_url( 'edit-tags.php?taxonomy=doc_category&post_type=docs' ),
                            'feature_title'   => __( 'Create Categories', 'betterdocs' ),
                            'feature_content' => sprintf( '%s %s &gt; <strong>%s</strong>', __( 'You can create Categories from', 'betterdocs' ), '<strong>BetterDocs</strong>', __( 'Categories', 'betterdocs' ) )
                        ],
                        [
                            'title'           => __( 'Create Docs', 'betterdocs' ),
                            'url'             => admin_url( 'post-new.php?post_type=docs' ),
                            'feature_title'   => __( 'Create Docs', 'betterdocs' ),
                            'feature_content' => sprintf( '%1$s %2$s %3$s', __( 'You can create Docs from ', 'betterdocs' ), '<strong>BetterDocs &gt;</strong>', __( 'Add New', 'betterdocs' ) )
                        ]
                    ]
                ]
            ]
        ] );

        // Customize
        SetupWizardHelper::setSection( [
            'id'     => 'betterdocs_customize_settings',
            'title'  => __( 'Customize', 'betterdocs' ),
            'fields' => [
                [
                    'id'        => 'customize_step',
                    'title'     => __( 'Customize Everything', 'betterdocs' ),
                    'sub_title' => __( 'Take control of your settings and customize your documentation page, articles and archive pages live, with the power of Customizer', 'betterdocs' ),
                    'type'      => 'link',
                    'image_url' => betterdocs()->assets->icon( 'customizer/setup-customizer.png', true ),
                    'options'   => [
                        [
                            'id'              => 'bdgotocustomize',
                            'title'           => __( 'Go To Customizer', 'betterdocs' ),
                            'url'             => $this->customizer_settings_url(),
                            'feature_title'   => __( 'Easy To Customize', 'betterdocs' ),
                            'feature_content' => __( 'Customize Docs page, Docs, Archive page Live', 'betterdocs' )
                        ],
                        [
                            'title'           => __( 'Go To Settings', 'betterdocs' ),
                            'url'             => $this->settings->url(),
                            'feature_title'   => __( 'Extensive Options Panel', 'betterdocs' ),
                            'feature_content' => __( 'Take control of your pages with extensive settings options', 'betterdocs' )
                        ]
                    ]
                ]
            ]
        ] );

        // Finalize
        SetupWizardHelper::setSection( [
            'id'     => 'betterdocs_finalize_settings',
            'title'  => __( 'Finalize', 'betterdocs' ),
            'fields' => [
                [
                    'id'        => 'finnilize_step',
                    'title'     => __( 'Great Job!', 'betterdocs' ),
                    'sub_title' => __( 'Your documentation page is ready! Make sure to add more articles and assign them to proper categories and you are good to go.', 'betterdocs' ),
                    'type'      => 'final_step',
                    'image_url' => betterdocs()->assets->icon( 'setup-wizard/setup-finalize.svg', true ),
                    'options'   => [
                        [
                            'id'    => 'bdgotodocspage',
                            'title' => __( 'Visit Your Documentation Page', 'betterdocs' ),
                            'url'   => $this->docs_page_url()
                        ]
                    ]
                ]
            ]
        ] );
    }

    public function views() {
        betterdocs()->views->get( 'admin/setup-wizard' );
    }

    public function customizer_settings_url() {
        $query = [
            'autofocus[panel]' => 'betterdocs_customize_options',
            'return'           => admin_url( 'edit.php?post_type=docs' )
        ];

        $docs_slug = $this->settings->get( 'docs_slug', 'docs' );
        if ( $docs_slug ) {
            $query['url'] = site_url( '/' . $docs_slug );
        }
        $customizer_link = add_query_arg( $query, admin_url( 'customize.php' ) );

        return esc_url( $customizer_link );
    }

    public function docs_page_url() {
        return esc_url( site_url( '/' . $this->settings->get( 'docs_slug', 'docs' ) ) );
    }

    public function knowledge_base_plugins() {
        $plugins = [];

        if ( Helper::is_plugin_active( 'wedocs/wedocs.php' ) ) {
            $plugins[] = ['wedocs', 'weDocs'];
        }
        if ( Helper::is_plugin_active( 'bsf-docs/bsf-docs.php' ) ) {
            $plugins[] = ['bsf-docs', 'BSF docs'];
        }
        if ( Helper::is_plugin_active( 'documentor-lite/documentor-lite.php' ) ) {
            $plugins[] = ['documentor-lite', 'Documentor'];
        }
        if ( Helper::is_plugin_active( 'echo-knowledge-base/echo-knowledge-base.php' ) ) {
            $plugins[] = ['echo-knowledge-base', 'Echo Knowledge Base'];
        }
        if ( Helper::is_plugin_active( 'pressapps-knowledge-base/pressapps-knowledge-base.php' ) ) {
            $plugins[] = ['pressapps-knowledge-base', 'PressApps Knowledge Base'];
        }

        return $plugins;
    }
}
