<?php
    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }
?>

<div class="betterdocs-settings-header">
	<div class="betterdocs-header-full">
		<img src="<?php echo betterdocs()->assets->icon( 'betterdocs-logo.svg', true ); ?>" alt="">
		<div class="betterdocs-header-button">
			<?php
                if ( ! empty( $params['quick_links'] ) ) {
                    foreach ( $params['quick_links'] as $key => $link ) {
                        echo $link;
                    }
                }

                /**
                 * Before Header Button End
                 */
                do_action( 'betterdocs_admin_header_before_end' );
            ?>
		</div>
		<?php
            $_origin_vars = get_defined_vars();
            $view_object->get( 'admin/partials/tabs-nav', array_merge( [
                'url' => add_query_arg( ['page' => 'betterdocs-admin'], admin_url( 'admin.php' ) )
            ], $_origin_vars ) );

            $view_object->get( 'admin/partials/mode-switch', $_origin_vars );
        ?>
	</div>
</div>
