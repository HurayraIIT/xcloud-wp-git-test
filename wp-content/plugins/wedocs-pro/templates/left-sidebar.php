<div class="wedocs-hide-mobile <?php echo esc_attr( $right_bar === 'on' ? 'w-[22%]' : 'w-[25%]' ); ?>">
    <?php
    $ancestors = [];
    $root      = $parent = false;

    if ( ! empty( $post->post_parent ) ) {
        $ancestors = get_post_ancestors( $post->ID );
        $root      = count( $ancestors ) - 1;
        $parent    = $ancestors[$root];
    } else {
        $parent = ! empty( $post->ID ) ? $post->ID : '';
    }

    // var_dump( $parent, $ancestors, $root );
    $walker   = new WeDevs\WeDocs\Walker();
    $children = wp_list_pages( [
        'title_li'  => '',
        'order'     => 'menu_order',
        'child_of'  => $parent,
        'echo'      => false,
        'post_type' => 'docs',
        'walker'    => $walker,
    ] );

    // Add necessary tailwind classes for handling ui.
    $children  = str_replace(
        array( "<ul class='children'>", '<a', '<span class="wedocs-caret"' ),
        array(
            '<ul class="children list-none my-4 ml-8 border-l border-solid border-[#ddd]">',
            '<a class="' . $template_name . ' no-underline ' . $nav_icon . ' text-[#333] py-2 pr-2.5 pl-5 flex rounded-md" ',
            '<span style="border-color: ' . $active_nav_bg . '; color: ' . $active_nav_bg . ';" class="wedocs-caret dashicons text-sm flex items-center justify-center ml-auto px-2.5 border border-solid rounded mt-0.5"',
        ),
        $children
    );
    ?>

    <h3 class="widget-title !font-bold !mb-4 pb-4 mt-2 pr-8 pl-5 border-b border-solid border-[#eee]">
        <?php echo wedocs_apply_short_content( get_post_field( 'post_title', $parent, 'display' ), 38 ); ?>
    </h3>

    <?php if ( $children ) { ?>
        <ul class="doc-nav-list list-none ml-0 mr-3">
            <?php echo $children; ?>
        </ul>
    <?php } ?>
</div>
