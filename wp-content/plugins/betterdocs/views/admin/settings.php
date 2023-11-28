<div class="betterdocs-settings-wrap">
    <?php do_action( 'betterdocs_settings_header', 'settings' );?>
    <div class="betterdocs-left-right-settings">
        <?php do_action( 'betterdocs_before_settings_left' );?>
        <div class="betterdocs-settings">
            <div class="betterdocs-settings-content">
                <div class="betterdocs-settings-form-wrapper">
                    <div id="betterdocsQuickBuilder"></div>
                </div>
                <?php
                    if ( ! betterdocs()->is_pro_active() ) {
                        betterdocs()->views->get( 'admin/settings/sidebar' );
                    }
                ?>
            </div>
            <?php betterdocs()->views->get( 'admin/settings/blocks' );?>
        </div>
        <?php do_action( 'betterdocs_after_settings_left' );?>
    </div>
</div>
