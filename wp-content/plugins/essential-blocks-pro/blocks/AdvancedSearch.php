<?php
namespace EssentialBlocks\Pro\blocks;

use EssentialBlocks\Core\Block;
use EssentialBlocks\Pro\Utils\Helper;

class AdvancedSearch extends Block {
    protected $is_pro           = true;
    protected $editor_scripts   = 'essential-blocks-pro-editor-script';
    protected $editor_styles    = 'essential-blocks-pro-editor-style';
    protected $frontend_scripts = ['essential-blocks-pro-adv-search-frontend'];
    protected $frontend_styles  = ['essential-blocks-pro-frontend-style', 'essential-blocks-fontawesome'];

    /**
     * Unique name of the block.
     * @return string
     */
    public function get_name() {
        return 'pro-advanced-search';
    }

    /**
     * Register all other scripts
     * @return void
     */
    public function register_scripts() {
        wpdev_essential_blocks_pro()->assets->register(
            'adv-search-frontend',
            $this->path() . '/frontend/index.js'
        );
    }

    protected static $default_attributes = [
        'preset'                    => 'preset-classic-1',
        'postTypeValue'             => 'all',
        'placeholderText'           => 'Search ....',
        'btnText'                   => 'Search',
        'notFoundText'              => 'No Record Found',
        'displayContentImage'       => true,
        'showSearchIcon'            => true,
        'showTotalResult'           => false,
        'totalResultsText'          => 'Total [post_count] Results',
        'showLoadMore'              => false,
        'loadmoreBtnText'           => 'View All Results',
        'showTaxonomyFilter'        => false,
        'selectedTaxonomy'          => '',
        'selectedTaxonomyItems'     => '[]',
        'taxListText'               => 'All Categories',
        'initialValue'              => '5',
        'buttonType'                => 'btn-type-text',
        'searchBtnIcon'             => 'fas fa-search',
        'showSearchBtn'             => true,
        'filterTerms'               => [],
        'showPopularKeywords'       => false,
        'popularKeywordsLabel'      => 'Popular Keywords',
        'popularKeywordslimit'      => 5,
        'popularKeywordsRank'       => 5,
        'popularKeywordsRankLength' => 4

    ];

    public function get_default_attributes() {
        return self::$default_attributes;
    }

    /**
     * Block render callback.
     *
     * @param mixed $attributes
     * @param mixed $content
     * @return mixed
     */
    public function render_callback( $attributes, $content ) {
        if ( is_admin() ) {
            return;
        }

        $attributes = wp_parse_args( $attributes, $this->get_default_attributes() );

        $className = isset( $attributes["className"] ) ? $attributes["className"] : "";
        $classHook = isset( $attributes['classHook'] ) ? $attributes['classHook'] : '';

        $_search_settings = [
            'post_type'                 => isset( $attributes["postTypeValue"] ) ? $attributes["postTypeValue"] : "",
            'showTotalResult'           => isset( $attributes["showTotalResult"] ) ? $attributes["showTotalResult"] : "",
            'showTaxonomyFilter'        => isset( $attributes["showTaxonomyFilter"] ) ? $attributes["showTaxonomyFilter"] : "",
            'selectedTaxonomy'          => isset( $attributes["selectedTaxonomy"] ) ? $attributes["selectedTaxonomy"] : "",
            'showLoadMore'              => isset( $attributes["showLoadMore"] ) ? $attributes["showLoadMore"] : "",
            'displayContentImage'       => $attributes["displayContentImage"],
            'initialValue'              => $attributes["initialValue"],
            'showPopularKeywords'       => isset( $attributes["showPopularKeywords"] ) ? $attributes["showPopularKeywords"] : "",
            'popularKeywordslimit'      => isset( $attributes["popularKeywordslimit"] ) ? $attributes["popularKeywordslimit"] : 5,
            'popularKeywordsRank'       => isset( $attributes["popularKeywordsRank"] ) ? $attributes["popularKeywordsRank"] : 5,
            'popularKeywordsRankLength' => isset( $attributes["popularKeywordsRankLength"] ) ? $attributes["popularKeywordsRankLength"] : 4
        ];

        ob_start();
        Helper::views(
            'adv-search',
            array_merge( $attributes, [
                'block_object'   => $this,
                'className'      => $className,
                'classHook'      => $classHook,
                'searchSettings' => $_search_settings
            ] )
        );

        return ob_get_clean();
    }
}
