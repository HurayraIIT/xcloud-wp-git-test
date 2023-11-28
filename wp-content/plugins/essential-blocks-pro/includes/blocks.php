<?php
use EssentialBlocks\Pro\blocks\ProForm;
use EssentialBlocks\Pro\blocks\DataTable;
use EssentialBlocks\Pro\blocks\FancyChart;
use EssentialBlocks\Pro\blocks\NewsTicker;
use EssentialBlocks\Pro\blocks\StackedCards;
use EssentialBlocks\Pro\blocks\AdvancedSearch;
use EssentialBlocks\Pro\blocks\TimelineSlider;
use EssentialBlocks\Pro\blocks\WooProductCarousel;
use EssentialBlocks\Pro\blocks\MultiColumnPricingTable;

return [
    'advanced_search'           => [
        'object' => AdvancedSearch::get_instance()
    ],
    'data_table'                => [
        'object' => DataTable::get_instance()
    ],
    'timeline_slider'           => [
        'object' => TimelineSlider::get_instance()
    ],
    'news_ticker'               => [
        'object' => NewsTicker::get_instance()
    ],
    'woo_product_carousel'      => [
        'object' => WooProductCarousel::get_instance()
    ],
    'form'                      => [
        'object' => ProForm::get_instance()
    ],
    'fancy_chart'               => [
        'object' => FancyChart::get_instance()
    ],
    'multicolumn_pricing_table' => [
        'object' => MultiColumnPricingTable::get_instance()
    ],
    'stacked_cards'             => [
        'object' => StackedCards::get_instance()
    ]
];
