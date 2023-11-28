<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\SearchForm;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CategoryBox;
use WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks\CategoryGrid;

return [
    'categorygrid' => [
        'label'      => __( 'BetterDocs Category Grid', 'betterdocs' ),
        'value'      => 'categorygrid',
        'visibility' => true,
        'object'     => CategoryGrid::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'categorybox'  => [
        'label'      => __( 'BetterDocs Category Box', 'betterdocs' ),
        'value'      => 'categorybox',
        'visibility' => true,
        'object'     => CategoryBox::class,
        'demo'       => '',
        'docs'       => ''
    ],
    'searchform'   => [
        'label'      => __( 'BetterDocs Search Form', 'betterdocs' ),
        'value'      => 'searchform',
        'visibility' => true,
        'object'     => SearchForm::class,
        'demo'       => '',
        'docs'       => ''
    ]
];
