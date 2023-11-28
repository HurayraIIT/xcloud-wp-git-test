<?php
    if( ! is_singular( 'docs' ) ) {
        return;
    }
?>

<div
    <?php echo $wrapper_attr; ?>>
    <?php
        betterdocs()->views->get( 'templates/parts/print-icon', [
            'enable' => (bool) $enable
        ] );

        // The Content For A Docs
        $view_object->get( 'templates/parts/content', [
            'htags'      => $htags,
            'enable_toc' => $enable_toc
        ] );
    ?>
</div>
