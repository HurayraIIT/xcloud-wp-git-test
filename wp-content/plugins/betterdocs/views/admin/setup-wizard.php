<div id="wpwrap">
    <div class="betterdocs-settings-wrap">
        <div class="betterdocs-topbar-logo text-center">
            <img width="150" src="<?php echo betterdocs()->assets->icon( 'betterdocs-logo.svg', true ); ?>" alt="logo">
        </div>
        <!-- setup wizard -->
        <div class="betterdocs-setup-wizard">
            <form method="post" action="#">
                <input type="hidden" name="betterdocsqswnonce" value="<?php esc_attr_e( wp_create_nonce( 'betterdocsqswnonce' ) );?>">
                <div class="betterdocs-tabnav-wrap">
                    <ul class="tab-nav">
                        <?php do_action( 'betterdocs_nav_tabs' );?>
                    </ul>
                </div>
                <div class="betterdocs-tab-content-wrap">
                    <?php
                        do_action( 'betterdocs_tabs_content' );
                    ?>
                    <div class="betterdocs-button-wrap">
                        <a id="betterdocs-prev-option" href="#" class="btn betterdocs-prev-option"><?php _e( 'Previous', 'betterdocs' );?></a>
                        <a id="betterdocs-next-option" href="#" class="btn betterdocs-next-option"><?php _e( 'Next', 'betterdocs' );?></a>
                    </div>
                    <div class="bottom-notice-left">
                        <p class="whatwecollecttext"><?php _e( 'We collect non-sensitive diagnostic data and plugin usage information. Your site URL, WordPress & PHP version, plugins & themes and email address to send you the discount coupon. This data lets us make sure this plugin always stays compatible with the most popular plugins and themes. No spam, we promise.', 'betterdocs' )?></p>
                        <button type="button" class="btn-collect"><?php _e( 'What We Collect?', 'betterdocs' );?></button>
                    </div>
                    <div class="bottom-notice">
                        <button type="button" id="betterdocsqswemailskipbutton" class="btn-skip"><?php _e( 'Skip This Step', 'betterdocs' );?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
