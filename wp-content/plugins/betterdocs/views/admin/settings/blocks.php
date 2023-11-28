<?php
    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

?>

<div class="betterdocs-settings-documentation">
    <div class="betterdocs-settings-row">
        <div class="betterdocs-admin-block betterdocs-admin-block-docs">
            <header class="betterdocs-admin-block-header">
                <div class="betterdocs-admin-block-header-icon">
                    <img src="<?php echo betterdocs()->assets->icon( 'icons/icon-documentation.svg', true ); ?>" alt="betterdocs-documentation">
                </div>
                <h4 class="betterdocs-admin-title"><?php _e( 'Documentation', 'betterdocs' );?></h4>
            </header>
            <div class="betterdocs-admin-block-content">
                <p><?php _e( 'Get started by spending some time with the documentation to get familiar with BetterDocs. Build an awesome Knowledge Base for your customers with ease.', 'betterdocs' );?></p>
                <a rel="nofollow" href="https://betterdocs.co/docs/" class="betterdocs-button" target="_blank"><?php _e( 'Documentation', 'betterdocs' );?></a>
            </div>
        </div>
        <div class="betterdocs-admin-block betterdocs-admin-block-contribute">
            <header class="betterdocs-admin-block-header">
                <div class="betterdocs-admin-block-header-icon">
                    <img src="<?php echo betterdocs()->assets->icon( 'icons/icon-join-community.svg', true ); ?>" alt="betterdocs-contribute">
                </div>
                <h4 class="betterdocs-admin-title"><?php _e( 'Join Our Community', 'betterdocs' );?></h4>
            </header>
            <div class="betterdocs-admin-block-content">
                <p><?php echo esc_html__( 'Join the Facebook community and discuss with fellow developers and users. Best way to connect with people and get feedback on your projects.', 'betterdocs' ) ?></p>
                <a rel="nofollow" href="https://www.facebook.com/groups/wpdeveloper.net/" class="betterdocs-button" target="_blank"><?php _e( 'Join Now', 'betterdocs' );?></a>
            </div>
        </div>
        <div class="betterdocs-admin-block betterdocs-admin-block-need-help">
            <header class="betterdocs-admin-block-header">
                <div class="betterdocs-admin-block-header-icon">
                    <img src="<?php echo betterdocs()->assets->icon( 'icons/icon-need-help.svg', true ); ?>" alt="betterdocs-help">
                </div>
                <h4 class="betterdocs-admin-title"><?php _e( 'Need Help?', 'betterdocs' );?></h4>
            </header>
            <div class="betterdocs-admin-block-content">
                <p><?php _e( 'Stuck with something? Get help from live chat or support ticket.', 'betterdocs' );?></p>
                <a rel="nofollow" href="https://wpdeveloper.com/support" class="betterdocs-button" target="_blank"><?php _e( 'Initiate a Chat', 'betterdocs' );?></a>
            </div>
        </div>
        <div class="betterdocs-admin-block betterdocs-admin-block-community">
            <header class="betterdocs-admin-block-header">
                <div class="betterdocs-admin-block-header-icon">
                    <img src="<?php echo betterdocs()->assets->icon( 'icons/icon-show-love.svg', true ); ?>" alt="betterdocs-commuinity">
                </div>
                <h4 class="betterdocs-admin-title"><?php _e( 'Show Your Love', 'betterdocs' );?></h4>
            </header>
            <div class="betterdocs-admin-block-content">
                <p><?php _e( 'We love to have you in BetterDocs family. We are making it more awesome everyday. Take your 2 minutes to review the plugin and spread the love to encourage us to keep it going.', 'betterdocs' );?></p>
                <a rel="nofollow" href="https://betterdocs.co/wp/review" class="betterdocs-button" target="_blank"><?php _e( 'Leave a Review', 'betterdocs' );?></a>
            </div>
        </div>
    </div>
</div>
