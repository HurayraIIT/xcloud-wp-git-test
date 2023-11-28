<?php

namespace WPDeveloper\BetterDocs\Utils;

/**
 * Utility methods used for serving block templates from BetterDocs Blocks.
 * {@internal This class and its methods should only be used within the class-block-template-controller.php and is not intended for public use.}
 */
class BlockTemplateUtils {
    const ELIGIBLE_FOR_DOC_ARCHIVE_FALLBACK = [ 'taxonomy-knowledge_base', 'taxonomy-doc_category', 'taxonomy-doc_tag' ];

    /**
     * BetterDocs plugin slug
     *
     * This is used to save templates to the DB which are stored against this value in the wp_terms table.
     *
     * @var string
     */
    const PLUGIN_SLUG = 'betterdocs/betterdocs';

    /**
     * Returns an array containing the references of
     * the passed blocks and their inner blocks.
     *
     * @param array $blocks array of blocks.
     *
     * @return array block references to the passed blocks and their inner blocks.
     */
    public static function flatten_blocks( &$blocks ) {
        $all_blocks = [];
        $queue      = [];
        foreach ( $blocks as &$block ) {
            $queue[] = &$block;
        }
        $queue_count = count( $queue );

        while ( $queue_count > 0 ) {
            $block = &$queue[0];
            array_shift( $queue );
            $all_blocks[] = &$block;

            if ( ! empty( $block['innerBlocks'] ) ) {
                foreach ( $block['innerBlocks'] as &$inner_block ) {
                    $queue[] = &$inner_block;
                }
            }

            $queue_count = count( $queue );
        }

        return $all_blocks;
    }

    /**
     * Parses wp_template content and injects the current theme's
     * stylesheet as a theme attribute into each wp_template_part
     *
     * @param string $template_content serialized wp_template content.
     *
     * @return string Updated wp_template content.
     */
    public static function inject_theme_attribute_in_content( $template_content ) {
        $has_updated_content = false;
        $new_content         = '';
        $template_blocks     = parse_blocks( $template_content );

        $blocks = self::flatten_blocks( $template_blocks );
        foreach ( $blocks as &$block ) {
            if (
                'core/template-part' === $block['blockName'] &&
                ! isset( $block['attrs']['theme'] )
            ) {
                $block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
                $has_updated_content     = true;
            }
        }

        if ( $has_updated_content ) {
            foreach ( $template_blocks as &$block ) {
                $new_content .= serialize_block( $block );
            }

            return $new_content;
        }

        return $template_content;
    }

    /**
     * Build a unified template object based a post Object.
     * Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
     *
     * @param \WP_Post $post Template post.
     *
     * @return \WP_Block_Template|\WP_Error Template.
     */
    public static function build_template_result_from_post( $post ) {
        $terms = get_the_terms( $post, 'wp_theme' );

        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        if ( ! $terms ) {
            return new \WP_Error( 'template_missing_theme', __( 'No theme is defined for this template.', 'betterdocs' ) );
        }

        $theme          = $terms[0]->name;
        $has_theme_file = true;

        $template                 = new \WP_Block_Template();
        $template->wp_id          = $post->ID;
        $template->id             = $theme . '//' . $post->post_name;
        $template->theme          = $theme;
        $template->content        = $post->post_content;
        $template->slug           = $post->post_name;
        $template->source         = 'custom';
        $template->type           = $post->post_type;
        $template->description    = $post->post_excerpt;
        $template->title          = $post->post_title;
        $template->status         = $post->post_status;
        $template->has_theme_file = $has_theme_file;
        $template->is_custom      = false;
        $template->post_types     = []; // Don't appear in any Edit Post template selector dropdown.

        if ( 'wp_template_part' === $post->post_type ) {
            $type_terms = get_the_terms( $post, 'wp_template_part_area' );
            if ( ! is_wp_error( $type_terms ) && false !== $type_terms ) {
                $template->area = $type_terms[0]->name;
            }
        }

        return $template;
    }

    /**
     * Build a unified template object based on a theme file.
     * Important: This method is an almost identical duplicate from wp-includes/block-template-utils.php as it was not intended for public use. It has been modified to build templates from plugins rather than themes.
     *
     * @param array|object $template_file Theme file.
     * @param string       $template_type wp_template or wp_template_part.
     *
     * @return \WP_Block_Template Template.
     */
    public static function build_template_result_from_file( $template_file, $template_type ) {
        $template_file = (object) $template_file;

        // If the theme has an archive-docs.html template but does not have docs taxonomy templates
        // then we will load in the archive-docs.html template from the theme to use for docs taxonomies on the frontend.
        $template_is_from_theme = 'theme' === $template_file->source;
        $theme_name             = wp_get_theme()->get( 'TextDomain' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $template_content  = file_get_contents( $template_file->path );
        $template          = new \WP_Block_Template();
        $template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : self::PLUGIN_SLUG . '//' . $template_file->slug;
        $template->theme   = $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG;
        $template->content = self::inject_theme_attribute_in_content( $template_content );
        // Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
        $template->source         = $template_file->source ? $template_file->source : 'plugin';
        $template->slug           = $template_file->slug;
        $template->type           = $template_type;
        $template->title          = ! empty( $template_file->title ) ? $template_file->title : self::convert_slug_to_title( $template_file->slug );
        $template->status         = 'publish';
        $template->has_theme_file = true;
        $template->origin         = $template_file->source;
        $template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
        $template->post_types     = []; // Don't appear in any Edit Post template selector dropdown.
        $template->area           = 'uncategorized';
        return $template;
    }

    /**
     * Build a new template object so that we can make BetterDocs Blocks default templates available in the current theme should they not have any.
     *
     * @param string $template_file Block template file path.
     * @param string $template_type wp_template or wp_template_part.
     * @param string $template_slug Block template slug e.g. single-docs.
     * @param bool   $template_is_from_theme If the block template file is being loaded from the current theme instead of BetterDocs Blocks.
     *
     * @return object Block template object.
     */
    public static function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
        $theme_name = wp_get_theme()->get( 'TextDomain' );

        $new_template_item = [
            'slug'        => $template_slug,
            'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : self::PLUGIN_SLUG . '//' . $template_slug,
            'path'        => $template_file,
            'type'        => $template_type,
            'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
            // Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
            'source'      => $template_is_from_theme ? 'theme' : 'plugin',
            'title'       => self::convert_slug_to_title( $template_slug ),
            'description' => '',
            'post_types'  => [] // Don't appear in any Edit Post template selector dropdown.
        ];

        return (object) $new_template_item;
    }

    /**
     * Finds all nested template part file paths in a theme's directory.
     *
     * @param string $base_directory The theme's file path.
     * @return array $path_list A list of paths to all template part files.
     */
    public static function get_template_paths( $base_directory ) {
        $path_list = [];
        if ( file_exists( $base_directory ) ) {
            $nested_files      = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base_directory ) );
            $nested_html_files = new \RegexIterator( $nested_files, '/^.+\.html$/i', \RecursiveRegexIterator::GET_MATCH );
            foreach ( $nested_html_files as $path => $file ) {
                $path_list[] = $path;
            }
        }
        return $path_list;
    }

    /**
     * Returns template titles.
     *
     * @param string $template_slug The templates slug (e.g. single-docs).
     * @return string Human friendly title.
     */
    public static function get_block_template_title( $template_slug ) {
        $plugin_template_types = self::get_plugin_block_template_types();
        if ( isset( $plugin_template_types[$template_slug] ) ) {
            return $plugin_template_types[$template_slug]['title'];
        } else {
            // Human friendly title converted from the slug.
            return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
        }
    }

    /**
     * Returns template descriptions.
     *
     * @param string $template_slug The templates slug (e.g. single-docs).
     * @return string Template description.
     */
    public static function get_block_template_description( $template_slug ) {
        $plugin_template_types = self::get_plugin_block_template_types();
        if ( isset( $plugin_template_types[$template_slug] ) ) {
            return $plugin_template_types[$template_slug]['description'];
        }
        return '';
    }

    /**
     * Returns a filtered list of plugin template types, containing their
     * localized titles and descriptions.
     *
     * @return array The plugin template types.
     */
    public static function get_plugin_block_template_types() {
        $plugin_template_types = [
            'archive-docs'            => [
                'title'       => _x( 'Docs Page', 'Template name', 'betterdocs' ),
                'description' => __( 'Template used to display docs page.', 'betterdocs' )
            ],
            'taxonomy-knowledge_base' => [
                'title'       => _x( 'Knowledge Base', 'Template name', 'betterdocs' ),
                'description' => __( 'Template used to display Knowledge Bases.', 'betterdocs' )
            ],
            'taxonomy-doc_category'   => [
                'title'       => _x( 'Doc Category', 'Template name', 'betterdocs' ),
                'description' => __( 'Template used to display docs by category.', 'betterdocs' )
            ],
            'taxonomy-doc_tag'        => [
                'title'       => _x( 'Doc Tag', 'Template name', 'betterdocs' ),
                'description' => __( 'Template used to display docs by tag.', 'betterdocs' )
            ],
            'single-docs'             => [
                'title'       => _x( 'Single Docs', 'Template name', 'betterdocs' ),
                'description' => __( 'Template used to display the single docs.', 'betterdocs' )
            ]
        ];

        return $plugin_template_types;
    }

    /**
     * Converts template slugs into readable titles.
     *
     * @param string $template_slug The templates slug (e.g. single-docs).
     * @return string Human friendly title converted from the slug.
     */
    public static function convert_slug_to_title( $template_slug ) {
        switch ( $template_slug ) {
            case 'single-docs':
                return __( 'Single Docs', 'betterdocs' );
            case 'archive-docs':
                return __( 'Docs Page', 'betterdocs' );
            case 'taxonomy-knowledge_base':
                return __( 'Knowledge Base', 'betterdocs' );
            case 'taxonomy-doc_category':
                return __( 'Docs Category', 'betterdocs' );
            case 'taxonomy-doc_tag':
                return __( 'Docs Tag', 'betterdocs' );
            default:
                // Replace all hyphens and underscores with spaces.
                return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
        }
    }

    /**
     * Converts template paths into a slug
     *
     * @param string $path The template's path.
     * @return string slug
     */
    public static function generate_template_slug_from_path( $path ) {
        $template_extension = '.html';

        return basename( $path, $template_extension );
    }

    /**
     * Checks to see if they are using a compatible version of WP, or if not they have a compatible version of the Gutenberg plugin installed.
     *
     * @return boolean
     */
    public static function supports_block_templates() {
        if (
            ! betterdocs()->helper->current_theme_is_fse_theme() &&
            ( ! function_exists( 'gutenberg_supports_block_templates' ) || ! gutenberg_supports_block_templates() )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves a single unified template object using its id.
     *
     * @param string $id            Template unique identifier (example: theme_slug//template_slug).
     * @param string $template_type Optional. Template type: `'wp_template'` or '`wp_template_part'`.
     *                             Default `'wp_template'`.
     *
     * @return \WP_Block_Template|null Template.
     */
    public static function get_block_template( $id, $template_type ) {
        if ( function_exists( 'get_block_template' ) ) {
            return get_block_template( $id, $template_type );
        }

        if ( function_exists( 'gutenberg_get_block_template' ) ) {
            return gutenberg_get_block_template( $id, $template_type );
        }

        return null;
    }

    /**
     * Checks if we can fallback to the `archive-docs` template for a given slug
     *
     * `taxonomy-doc_category`, `taxonomy-knowledge_base` and `taxonomy-doc_tag` templates can generally use the
     * `archive-docs` as a fallback if there are no specific overrides.
     *
     * @param string $template_slug Slug to check for fallbacks.
     * @return boolean
     */
    public static function template_is_eligible_for_docs_archive_fallback( $template_slug ) {
        return in_array( $template_slug, self::ELIGIBLE_FOR_DOC_ARCHIVE_FALLBACK, true );
    }

    /**
     * Sets the `has_theme_file` to `true` for templates with fallbacks
     *
     * There are cases (such as tags and categories) in which fallback templates
     * can be used; so, while *technically* the theme doesn't have a specific file
     * for them, it is important that we tell Gutenberg that we do, in fact,
     * have a theme file (i.e. the fallback one).
     *
     * **Note:** this function changes the array that has been passed.
     *
     * It returns `true` if anything was changed, `false` otherwise.
     *
     * @param array  $query_result Array of template objects.
     * @param object $template A specific template object which could have a fallback.
     *
     * @return boolean
     */
    public static function set_has_theme_file_if_fallback_is_available( $query_result, $template ) {
        foreach ( $query_result as &$query_result_template ) {
            if (
                $query_result_template->slug === $template->slug
                && $query_result_template->theme === $template->theme
            ) {
                if ( self::template_is_eligible_for_docs_archive_fallback( $template->slug ) ) {
                    $query_result_template->has_theme_file = true;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Removes templates that were added to a theme's block-templates directory, but already had a customised version saved in the database.
     *
     * @param \WP_Block_Template[]|\stdClass[] $templates List of templates to run the filter on.
     *
     * @return array List of templates with duplicates removed. The customised alternative is preferred over the theme default.
     */
    public static function remove_theme_templates_with_custom_alternative( $templates ) {

        // Get the slugs of all templates that have been customised and saved in the database.
        $customised_template_slugs = array_map(
            function ( $template ) {
                return $template->slug;
            },
            array_values(
                array_filter(
                    $templates,
                    function ( $template ) {
                        // This template has been customised and saved as a post.
                        return 'custom' === $template->source;
                    }
                )
            )
        );

        // Remove theme (i.e. filesystem) templates that have the same slug as a customised one. We don't need to check
        // for `betterdocs` in $template->source here because betterdocs templates won't have been added to $templates
        // if a saved version was found in the db. This only affects saved templates that were saved BEFORE a theme
        // template with the same slug was added.
        return array_values(
            array_filter(
                $templates,
                function ( $template ) use ( $customised_template_slugs ) {
                    // This template has been customised and saved as a post, so return it.
                    return ! ( 'theme' === $template->source && in_array( $template->slug, $customised_template_slugs, true ) );
                }
            )
        );
    }
}
